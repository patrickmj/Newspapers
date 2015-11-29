<?php

class Newspapers_CaImport_CaImport extends Omeka_Job_AbstractJob
{
    
    protected $client;
    
    public function perform()
    {
        $this->client = new Zend_Http_Client();
        
        $newspapersUrl = 'http://chroniclingamerica.loc.gov/newspapers.json';
        $newspapers = $this->fetchData($newspapersUrl);
        
        $firstNp = $newspapers['newspapers'][0];
        
        $this->parseNewspaperData($firstNp);
        
        
        
        $firstNpUrl = $firstNp['url'];
        $data = $this->fetchData($firstNpUrl);
        echo '<pre>';
        //print_r($data);
        echo '</pre>';
    }
    
    protected function fetchData($url)
    {
        $this->client->setUri($url);
        $response = $this->client->request();
        return json_decode($response->getBody(), true);
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
        $newspaper->collection_id = 1; // fake!
        $newspaper->ca_import_id = 1; //fake!
        $newspaper->lccn = $newspaperJson['lccn'];
        $newspaper->state = $newspaperJson['state'];
        $newspaper->issues_count = count($newspaperDetailsJson['issues']);
        $newspaper->save();
        
    }
    
    
}