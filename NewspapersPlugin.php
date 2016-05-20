<?php

define('NEWSPAPERS_PLUGIN_DIR', PLUGIN_DIR . '/Newspapers');

class NewspapersPlugin extends Omeka_Plugin_AbstractPlugin
{
    
    public $_hooks = array(
            'install',
            'uninstall',
            'initialize',
            'public_head',
            'items_browse_sql',
            'collections_browse_sql'
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
    
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `$db->NewspapersColumnsCorrection` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `fp_id` int(11) NOT NULL,
      `user_id` int(11) DEFAULT NULL,
      `corrected_columns` int(11) NOT NULL,
      `original_columns` int(11) NOT NULL,
      `reported_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `accepted_date` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `fp_id` (`fp_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
    
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
        
        $sql = "DROP TABLE IF EXISTS `$db->NewspapersColumnsCorrection`";
        $db->query($sql);
    }
    
    public function hookPublicHead($args)
    {
        queue_css_file('sotn');
    }
    
    public function hookItemsBrowseSql($args)
    {
        $select = $args['select'];
        $params = $args['params'];
        
        $db = $this->_db;

        $select->join($db->NewspapersFrontPage,
            "{$db->NewspapersFrontPage}.item_id = items.id", array());
        
        if(isset($params['columns'])) {
            $select->where("{$db->NewspapersFrontPage}.columns = ? ", $params['columns']);
        }
        
        if(isset($params['state'])) {
            $select->join($db->NewspapersIssues,
                "{$db->NewspapersFrontPage}.issue_id = {$db->NewspapersIssue}.id", array());

            $select->join($db->NewspapersNewspaper,
                "{$db->NewspapersNewspaper}.id = {$db->NewspapersIssue}.newspaper_id", array());
            
            
            $select->where("{$db->NewspapersNewspaper}.state = ? ", $params['state']);
        }
        
        if(isset($params['pages'])) {
            if (! $select->hasJoin($db->NewspapersIssues)) {
                $select->join($db->NewspapersIssues,
                    "{$db->NewspapersFrontPage}.issue_id = {$db->NewspapersIssue}.id", array());                
            }
            $select->where("{$db->NewspapersIssue}.pages = ?", $params['pages']);
        }
    }
    
    public function hookCollectionsBrowseSql($args)
    {
        $select = $args['select'];
        $params = $args['params'];
        $db = get_db();
        
        if (! isset($params['advanced'])) {
            return;
        }
        
        $terms = $params['advanced'];
        
        
        
        $advancedIndex = 0;
        foreach ($terms as $v) {
            // Do not search on blank rows.
            if (empty($v['element_id']) || empty($v['type'])) {
                continue;
            }
            
            $value = isset($v['terms']) ? $v['terms'] : null;
            $type = $v['type'];
            $elementId = (int) $v['element_id'];
            $alias = "_advanced_{$advancedIndex}";

            //copied from Item advanced search filter, and limited to what I
            //use in the modified SearchByMetadata
            
            $inner = true;
            $extraJoinCondition = '';
            // Determine what the WHERE clause should look like.
            switch ($type) {
                case 'is exactly':
                    $predicate = ' = ' . $db->quote($value);
                    break;
                default:
                    throw new Omeka_Record_Exception(__('Invalid search type given!'));
            }

            // Note that $elementId was earlier forced to int, so manual quoting
            // is unnecessary here
            $joinCondition = "{$alias}.record_id = collections.id AND {$alias}.record_type = 'Collection' AND {$alias}.element_id = $elementId";
            if ($extraJoinCondition) {
                $joinCondition .= ' ' . $extraJoinCondition;
            }
            if ($inner) {
                $select->joinInner(array($alias => $db->ElementText), $joinCondition, array());
            } else {
                $select->joinLeft(array($alias => $db->ElementText), $joinCondition, array());
            }
            $select->where("{$alias}.text {$predicate}");

            $advancedIndex++;
        }
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
