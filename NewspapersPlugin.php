<?php

class NewspapersPlugin extends Omeka_Plugin_AbstractPlugin
{
    public $_hooks = array(
            'install',
            'uninstall',
            'initialize',
            'public_head'
            );
    
    public $_filters = array(
            
            );
    
    public function hookInitialize()
    {
        add_file_fallback_image('application/xml', 'file-xml.png');
    }
    
    public function hookInstall()
    {
        $db = $this->_db;
        $this->installElementSets();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->NewspapersFrontPages` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `item_id` int(10) unsigned NOT NULL,
          `loc_uri` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `issue_id` int(10) unsigned NOT NULL,
          `page_height` int(11) NOT NULL,
          `page_width` int(11) NOT NULL,
          `printspace_height` float NOT NULL,
          `printspace_width` float NOT NULL,
          `printspace_vpos` float NOT NULL,
          `printspace_hpos` float NOT NULL,
          `columns` int(11) NOT NULL,
          `columns_confidence` tinytext COLLATE utf8_unicode_ci,
          `pdf_url` tinytext COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `item_id` (`item_id`),
          KEY `issue_id` (`issue_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ";
        $db->query($sql);
        
    $sql = "
    CREATE TABLE IF NOT EXISTS `$db->NewspapersIssues` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `newspaper_id` int(10) unsigned NOT NULL,
      `date_issued` date NOT NULL,
      `pages` int(10) unsigned NOT NULL,
      `loc_uri` tinytext COLLATE utf8_unicode_ci NOT NULL,
      PRIMARY KEY (`id`),
      KEY `date_issued` (`date_issued`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ";
        $db->query($sql);

    $sql = "
    CREATE TABLE IF NOT EXISTS `$db->NewspapersNewspapers` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `collection_id` int(10) unsigned NOT NULL,
      `lccn` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
      `state` tinytext COLLATE utf8_unicode_ci NOT NULL,
      `issues_count` int(10) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ";
    $db->query($sql);
    }
    
    public function hookUninstall()
    {
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->NewspapersNewspaper`";
        $db->query($sql);
        
        $sql = "DROP TABLE IF EXISTS `$db->NewspapersIssues`";
        $db->query($sql);
        
        $sql = "DROP TABLE IF EXISTS `$db->NewspapersFrontPages`";
        $db->query($sql);
    }
    
    public function hookPublicHead($args)
    {
        queue_css_file('sotn');
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
                array('name' => 'columns'),
                array('name' => 'state'),
                );
        
        insert_element_set($elSetData, $elementsData);
    }
}
