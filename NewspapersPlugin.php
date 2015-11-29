<?php

class NewspapersPlugin extends Omeka_Plugin_AbstractPlugin
{
    public $_hooks = array(
            'install'
            );
    
    public $_filters = array(
            
            );
    
    public function hookInstall()
    {
        $this->installElementSets();
    }
    
    
    protected function installElementSets()
    {
        $elSetData = array(
                'name'        => 'Newspaper Metadata',
                'description' => 'Data about newspapers from Chronicling America'
                );
        
        $elementsData = array(
                array('name' => 'place_of_publication'),
                array('name' => 'lccn'),
                array('name' => 'start_year'),
                array('name' => 'end_year'),
                array('name' => 'url'),
                array('name' => 'place'),
                );
        
        insert_element_set($elSetData, $elementsData);
    }
}
