<?php

class Newspapers_CaImport_CaImport extends Omeka_Job_AbstractJob
{
    
    protected $client;
    
    protected $newspapersTable;
    
    protected $issuesTable;
    
    protected $frontPagesTable;
    
    public function perform()
    {
        $this->client = new Zend_Http_Client();
        ini_set('max_execution_time', 30000); //hope this doesn't get me into trouble, but some of those ocrs take a while. sorry, LoC!
        $db = get_db();
        $this->newspapersTable = $db->getTable('NewspapersNewspaper');
        $this->issuesTable = $db->getTable('NewspapersIssue');
        $this->frontPagesTable = $db->getTable('NewspapersFrontPage');
        
        $newspapersUrl = 'http://chroniclingamerica.loc.gov/newspapers.json';
        try {
            $newspapers = $this->fetchData($newspapersUrl);
        } catch (Exception $e) {
            debug($e->getMessage());
        }
        
        
        foreach($newspapers['newspapers'] as $index => $newspaperData) {
            
            if ( ! ( $index >= 126 && $index <= 200) ) {
                continue;
            }
            debug("Begin index $index");
            $newspaper = $this->parseNewspaperData($newspaperData);
            $newspaperUrl = $newspaperData['url'];

            
            //skip the Memphis Appeal, which returns piles of no xml, and slows it all down
            if ($newspaperUrl == 'http://chroniclingamerica.loc.gov/lccn/sn83045160.json') {
                debug('skipping Memphis Appeal');
                continue;
            }

            $deepNpData = $this->fetchData($newspaperUrl);
            if (! $deepNpData) {
                debug("skipping $newspaperUrl for no data");
                continue;
            }
            $issuesData = $deepNpData['issues'];
            foreach($issuesData as $issueData) {
                $issueUrl = $issueData['url'];
                $issueJson = $this->fetchData($issueUrl);
                if(! $issueJson) {
                    continue;
                }
                try {
                    $issue = $this->parseIssueData($issueJson, $newspaper);
                    $frontPage = $this->parseFrontPageData($issueJson, $issue, $newspaper);
                } catch(Exception $e) {
                    debug($e->getMessage());
                    debug(print_r($issueJson, true));
                }
            }
            debug("Done index $index");
        }

    }
    
    protected function fetchData($url)
    {
        usleep(10);
        
        $this->client->setUri($url);
        try {
            $response = $this->client->request();
        } catch (Exception $e) {
            debug($url);
            debug($e->getMessage());
            return false;
        }
        return json_decode($response->getBody(), true);
    }
    
    protected function parseIssueData($issueJson, $newspaper)
    {
        $issue = $this->issuesTable->findByLocUri($issueJson['url']);
        if ($issue) {
            return $issue;
        }
        $issue = new NewspapersIssue();
        $issue->loc_uri = $issueJson['url'];
        $issue->pages = count($issueJson['pages']);
        $issue->newspaper_id = $newspaper->id;
        $issue->date_issued = $issueJson['date_issued'];
        $issue->save();
        return $issue;
        
    }
    
    protected function parseFrontPageData($issueJson, $issue, $newspaper)
    {
        $frontpageJson = $this->fetchData($issueJson['pages'][0]['url']);
        $frontPage = $this->frontPagesTable->findByLocUri($issueJson['pages'][0]['url']);
        
        if (! $frontPage) {
            $frontpage = new NewspapersFrontPage();
            
            $itemElementMetadata = array('Dublin Core' => array(), 'Newspaper Metadata' => array());
            $itemMetadata = array('collection_id' => $newspaper->collection_id, 'public' => true); //fake @todo
            
            
            $altoUrl = $frontpageJson['ocr'];
            $pdfUrl = $frontpageJson['pdf'];
            $date = $frontpageJson['issue']['date_issued'];
            $title = $issueJson['title']['name'] . ' ' . $date;
            $itemElementMetadata['Dublin Core']['Title'] = array(array('text' => $title, 'html' => false));
            $itemElementMetadata['Dublin Core']['Date'] = array(array('text' => $date, 'html' => false));
            
            
            try {
                $item = insert_item($itemMetadata, $itemElementMetadata);
            } catch (Exception $e) {
                debug($e->getMessage());
                $item = false;
            }
            
            if ($item) {
                $altoDoc = new AltoDoc($altoUrl);
                //begin voodoo
                    try {
                        $bottomTls = $altoDoc->filterTlsByVpos(null, .5);
                        $tls = $altoDoc->filterTlsByHeightSd($bottomTls, 1.1);
                        $tls = $altoDoc->filterTlsByWidthSd($tls);
                        $columnsGuess = $altoDoc->guessColumnsFromTls($tls, .7, null, 1.12);
                    } catch(Exception $e) {
                        echo 'bad xml in ';
                        echo $issueJson['pages'][0]['url'];
                    }
                //end voodoo
                
                $frontpage->columns = $columnsGuess;
                $frontpage->item_id = $item->id;
                $frontpage->issue_id = $issue->id;
                $frontpage->ca_import_id = 1; //fake @todo
                $frontpage->page_height = $altoDoc->pageLayout['page']['height'];
                $frontpage->page_width = $altoDoc->pageLayout['page']['width'];
                $frontpage->printspace_height = $altoDoc->pageLayout['printSpace']['height'];
                $frontpage->printspace_width = $altoDoc->pageLayout['printSpace']['width'];
                $frontpage->printspace_vpos = $altoDoc->pageLayout['printSpace']['hpos'];
                $frontpage->printspace_hpos = $altoDoc->pageLayout['printSpace']['hpos'];
                $frontpage->loc_uri = $issueJson['pages'][0]['url'];
                $frontpage->pdf_url = $pdfUrl;
                
                $frontpage->save();
            }
        }
    }
    
    protected function parseNewspaperData($newspaperJson)
    {
        //$newspaperJson comes from entry from newspapers.json
        //fetch second layer of data

        $newspaperDetailsJson = $this->fetchData($newspaperJson['url']);
        $lccn = $newspaperJson['lccn'];
        
        $newspaper = $this->newspapersTable->findByLccn($lccn);
        if ($newspaper) {
            return $newspaper;
        }
        
        //set element set data
        //need an element set for Newspaper
        $metadata = array('Dublin Core' => array(), 'Newspaper Metadata' => array());

        //switch around hanlding of each data field
        foreach($newspaperDetailsJson as $key => $values) {
            switch($key) {
                case 'place_of_publication':
                case 'lccn':
                    $metadata['Dublin Core']['Identifier'] = array(
                        array('html' => false, 'text' => $values)
                    );
                case 'start_year':
                case 'end_year':
                case 'url':
                    $metadata['Newspaper Metadata'][$key] = array(
                        array('html' => false, 'text' => $values),
                    );
                break;

                case 'place':
                    $placesArray = array();
                    foreach($values as $value) {
                        $placesArray[] = array('html' => false, 'text' => $value);
                    }
                    $metadata['Newspaper Metadata']['place'] = $placesArray;
                break;

                case 'name':
                    // dc:title
                    $metadata['Dublin Core']['Title'] = array(
                        array('html' => false, 'text' => $values),
                    );
                break;

                case 'publisher':
                    // dc:publisher
                    $metadata['Dublin Core']['Publisher'] = array(
                        array('html' => false, 'text' => $values),
                    );
                break;
                case 'issues':

                break;

                case 'subject':
                    $subjectsArray = array();
                    foreach($values as $value) {
                        $subjectsArray[] = array('html' => false, 'text' => $value);
                    }
                    $metadata['Dublin Core']['Subject'] = $subjectsArray;
                break;
            }

        }
        //insert collection
        $collection = insert_collection(array('public' => true), $metadata);
        //set NewspapersNewspaper data
        $newspaper = new NewspapersNewspaper();
        //$newspaper->collection_id = $collection->id;
        $newspaper->collection_id = $collection->id;
        //$newspaper->ca_import_id = 1; //fake! @todo
        $newspaper->lccn = $newspaperJson['lccn'];
        $newspaper->state = $newspaperJson['state'];
        $newspaper->issues_count = count($newspaperDetailsJson['issues']);
        $newspaper->save();
        return $newspaper;
    }
    
    
}
