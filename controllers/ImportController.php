<?php
class Newspapers_ImportController extends Omeka_Controller_AbstractActionController
{
    public function importAction()
    {
        include('/var/www/ChroniclingAmerica/plugins/Newspapers/libraries/CaImport/CaImport.php');
        Zend_Registry::get('bootstrap')->getResource('jobs')
                            ->send('Newspapers_CaImport_CaImport');
    }
}