<?php

class Newspapers_NewspapersController extends Omeka_Controller_AbstractActionController
{
    
    public function showAction()
    {
        //get the newspaper, and stats via front pages 
        $newspaperId = 2;
        $table = get_db()->getTable('NewspapersNewspaper');
        $stats = $table->getStats($newspaperId);
    }
    
    public function browseAction()
    {
        //the main interface?
        //needs search filters for state, dates via NewspapersIssue, and stats via front pages
    }
    
}
