<?php

define('NEWSPAPERS_PLUGIN_DIR', PLUGIN_DIR . '/Newspapers');

define('NEWSPAPERS_MAX_HEIGHT', 52220);
define('NEWSPAPERS_MAX_WIDTH', 45024);
define('NEWSPAPERS_MIN_HEIGHT', 4964);
define('NEWSPAPERS_MIN_WIDTH', 3128);

define('NEWSPAPERS_AVG_HEIGHT', 27927.8056 );
define('NEWSPAPERS_AVG_WIDTH', 20439.5667 );


class NewspapersPlugin extends Omeka_Plugin_AbstractPlugin
{
    
    public $_hooks = array(
            'install',
            'uninstall',
            'initialize',
            'public_head',
            'items_browse_sql',
            'collections_browse_sql',
            'admin_collections_show_sidebar',
            'upgrade',
            );
    
    public $_filters = array(
            'admin_navigation_main'
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
      `np_id` int(11) NULL,
      `apply_to_np` tinyint(1) DEFAULT NULL,
      `notes` text COLLATE utf8_unicode_ci,
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
        queue_js_file('corrections');
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
        
        if(isset($params['columns_greater_than'])) {
            $select->where("{$db->NewspapersFrontPage}.columns > ", $params['columns']);
        }
        
        if(isset($params['columns_less_than'])) {
            $select->where("{$db->NewspapersFrontPage}.columns < ", $params['columns']);
        }
        
        //precision is iffy, so include a range
        if(isset($params['width'])) {
            $floor = $params['width'] - 500;
            $ceil = $params['width'] + 500;
            $select->where("{$db->NewspapersFrontPage}.page_width BETWEEN $floor AND $ceil");
        }
        
        if(isset($params['height'])) {
            $floor = $params['height'] - 500;
            $ceil = $params['height'] + 500;
            $select->where("{$db->NewspapersFrontPage}.page_height BETWEEN $floor AND $ceil");
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
        
        if(isset($params['newspaper_id'])) {
            if (! $select->hasJoin($db->NewspapersIssues)) {
                $select->join($db->NewspapersIssues,
                    "{$db->NewspapersFrontPage}.issue_id = {$db->NewspapersIssue}.id", array());                
            }
            
            if (! $select->hasJoin($db->NewspapersNewspaper)) {
                $select->join($db->NewspapersNewspaper,
                    "{$db->NewspapersNewspaper}.id = {$db->NewspapersIssue}.newspaper_id", array());
                
            }
            
            $select->where("{$db->NewspapersNewspaper}.id = ? ", $params['newspaper_id']);
        }
    }
    
    public function hookCollectionsBrowseSql($args)
    {
        $select = $args['select'];
        $params = $args['params'];
        $db = get_db();
        
        
        $collectionsTable = $db->getTable('Collection');


        //make all sorting go by date in public
        if (! is_admin_theme() ) {
            //when SELECT arrives here, it's already ordered by added, so kill that
            $select->reset($select::ORDER);
            $collectionsTable->applySorting(
                    $select,
                    "Newspaper Metadata,start_year",
                    "ASC"
            );
        }

        $select->join($db->NewspapersNewspaper,
            "{$db->NewspapersNewspaper}.collection_id = collections.id", array());
            
        
        if ( isset($params['states'])) {
            $states = $params['states'];
        }

        if (! empty($states)) {
            $select->where("{$db->NewspapersNewspaper}.state IN (?)", $states);
        }

        if (isset($params['advanced'])) {
            $terms = $params['advanced'];
        } else {
            $terms = array();
        }
        
        
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
                case 'contains':
                    $predicate = "LIKE " . $db->quote('%'.$value .'%');
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
    
    public function hookAdminCollectionsShowSidebar($args)
    {
        $collection = $args['collection'];
        $db = $this->_db;
        $np = $db->getTable('NewspapersNewspaper')->findByCollection($collection->id);
        $html  = "<div class='newspapers panel'>";
        $html .= "<h4>Total issues</h4>";
        $html .= "<p>" . $np->issues_count . "</p>";
        $html .= "</div>";
        echo $html;
    }
    
    public function filterAdminNavigationMain($nav)
    {
        $nav['Newspapers_Rerun'] = array('label' => 'Rerun Import', 'uri' => url('newspapers/import/import'));
        $nav['Newspapers_Fixit'] = array('label' => 'Manage Corrections', 'uri' => url('newspapers/corrections/browse'));
        return $nav;
    }
    
    public function hookUpgrade($args)
    {
        if ($args['old_version'] == '0.1') {
            $fpTable = $this->_db->getTable('NewspapersFrontPage');
            $page = 0;
            while(count($frontPages != 0)) {
                debug('upgrade page ' . $page);
                $params = array('date' => '0000-00-00');
                $frontPages = $fpTable->findBy($params, 10000, $page);
                
                foreach ($frontPages as $frontPage) {
                    if ($frontPage->date == '0000-00-00') {
                        continue;
                    }
                    $item = $fpTable->findItemByFrontPage($frontPage);
                    $date = metadata($item, array('Dublin Core', 'Date'), array('no_filter' => true));
                    $frontPage->date = $date;
                    $frontPage->save();
                }
                $page++;
            }
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
