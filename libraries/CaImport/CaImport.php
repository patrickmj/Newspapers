<?php

class Newspapers_CaImport_CaImport extends Omeka_Job_AbstractJob
{
    
    protected $client;
    
    public function perform()
    {
        $this->client = new Zend_Http_Client();
        
        $newspapersUrl = 'http://chroniclingamerica.loc.gov/newspapers.json';
        $newspapers = $this->fetchData($newspapersUrl);
        
        foreach($newspapers['newspapers'] as $index => $newspaperData) {
            
        //for testing grab every $whatever interval
            if ($index %1000 != 0 ) {
                continue;
            }
            $newspaper = $this->parseNewspaperData($newspaperData);
            $newspaperUrl = $newspaperData['url'];
            $deepNpData = $this->fetchData($newspaperUrl);
            $issuesData = $deepNpData['issues'];
            foreach($issuesData as $issueData) {
                $issueUrl = $issueData['url'];
                $issueJson = $this->fetchData($issueUrl);
            
                $issue = $this->parseIssueData($issueJson, $newspaper);
                $frontPage = $this->parseFrontPageData($issueJson, $issue, $newspaper);
            }
            
        }
    }
    
    protected function fetchData($url)
    {
        usleep(50);
        $this->client->setUri($url);
        $response = $this->client->request();
        return json_decode($response->getBody(), true);
    }
    
    protected function parseIssueData($issueJson, $newspaper)
    {
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
        //I've have not idea why this is needed
        if (! class_exists('Alto2Svg')) {
            include(BASE_DIR . '/plugins/Newspapers/libraries/alto2svg/Alto2Svg.php');
        }
        $frontpage = new NewspapersFrontPage();
        
        $itemElementMetadata = array('Dublin Core' => array(), 'Newspaper Metadata' => array());
        $itemMetadata = array('collection_id' => $newspaper->collection_id, 'public' => true); //fake @todo
        
        $frontpageJson = $this->fetchData($issueJson['pages'][0]['url']);
        $altoUrl = $frontpageJson['ocr'];
        $pdfUrl = $frontpageJson['pdf'];
        $date = $frontpageJson['issue']['date_issued'];
        $title = $issueJson['title']['name'] . ' ' . $date;
        $itemElementMetadata['Dublin Core']['Title'] = array(array('text' => $title, 'html' => false));
        $itemElementMetadata['Dublin Core']['Date'] = array(array('text' => $date, 'html' => false));
        
        $filesMetadata = array('file_transfer_type' => 'Url', 'files' => $altoUrl, 'file_ingest_options' => array());
        $item = insert_item($itemMetadata, $itemElementMetadata, $filesMetadata);
        //@TODO to handle alto2svg conversion, a new Omeka_File_Ingest_AbstractIngest implementation?
        //or just do db churn and processing here?
        $altoDoc = new AltoDoc($altoUrl);
        
        $alto2svg = new Alto2Svg($altoDoc->alto);
        $svg = $alto2svg->process();
        $tempSvgPath = tempnam(sys_get_temp_dir(), 'omeka_plugin_newspapers_');
        $handle = fopen($tempSvgPath, "w");
        fwrite($handle, $svg);
        fclose($handle);
        chmod($tempSvgPath, 777);
        debug('tempsvgpath ' . $tempSvgPath);
        
        
        $builder = new Builder_Item(get_db());
        $builder->setRecord($item);
        $builder->addFiles('Filesystem', $tempSvgPath);
        
        
        unlink($tempSvgPath);
        
        
//begin voodoo
        $bottomTls = $altoDoc->filterTlsByVpos(null, .5);
        $tls = $altoDoc->filterTlsByHeightSd($bottomTls, 1.1);
        $tls = $altoDoc->filterTlsByWidthSd($tls);
        $columnsGuess = $altoDoc->guessColumnsFromTls($tls, .7, null, 1.12);
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
    
    protected function parseNewspaperData($newspaperJson)
    {
        //$newspaperJson comes from entry from newspapers.json
        //fetch second layer of data
        
        $newspaperDetailsJson = $this->fetchData($newspaperJson['url']);
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
        $newspaper->ca_import_id = 1; //fake! @todo
        $newspaper->lccn = $newspaperJson['lccn'];
        $newspaper->state = $newspaperJson['state'];
        $newspaper->issues_count = count($newspaperDetailsJson['issues']);
        $newspaper->save();
        return $newspaper;
    }
    
    
}