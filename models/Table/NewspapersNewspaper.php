<?php

class Table_NewspapersNewspaper extends Omeka_Db_Table
{
    public function findByCollection($collectionId)
    {
        $select = $this->getSelect();
        $select->where("collection_id = $collectionId");
        return $this->fetchObject($select);
    }
    
    /**
     * 
     * @param unknown_type $params
     * newspaperIds
     * states
     * 
     */
    
    public function getStats($params = array())
    {
        $newspaperIds = array();
        $states = array();
        
        if ( isset($params['newspaperIds']) && is_array($params['newspaperIds'])) {
            $newspaperIds = $params['newspaperIds'];
        }
        
        
        if ( isset($params['states']) && is_array($params['states'])) {
            $states = $params['states'];
        }
        
        $select = new Omeka_Db_Select($this->getDb()->getAdapter());
        
        $db = $this->_db;
        
        $select->from($db->NewspapersNewspaper, '*');
        /*
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_width) as maxPageWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_width) as minPageWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_width) as avgPageWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_width) as stdPageWidth"));
        
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("max({$db->NewspapersFrontPage}.printspace_width) as maxPrintSpaceWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("min({$db->NewspapersFrontPage}.printspace_width) as minPrintSpaceWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.printspace_width) as avgPrintSpaceWidth"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("std({$db->NewspapersFrontPage}.printspace_width) as stdPrintSpaceWidth"));

        
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_height) as maxPageHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_height) as minPageHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_height) as avgPageHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_height) as stdPageHeight"));
        
        
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("max({$db->NewspapersFrontPage}.printspace_height) as maxPrintSpaceHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("min({$db->NewspapersFrontPage}.printspace_height) as minPrintSpaceHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.printspace_height) as avgPrintSpaceHeight"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("std({$db->NewspapersFrontPage}.printspace_height) as stdPrintSpaceHeight"));

        
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("max({$db->NewspapersFrontPage}.columns) as maxColumns"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("min({$db->NewspapersFrontPage}.columns) as minColumns"));
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.columns) as avgColumns"));
        
        */
        /*
        $select->from($db->NewspapersNewspaper,
                    new Zend_Db_Expr("std({$db->NewspapersFrontPage}.columns) as stdColumns"));
        */
        
        
        $select->join($db->NewspapersIssue,
                "{$db->NewspapersIssue}.newspaper_id = {$db->NewspapersNewspaper}.id", array());

        $select->join($db->NewspapersFrontPage, 
                "{$db->NewspapersFrontPage}.issue_id = {$db->NewspapersIssue}.id", 
                array(
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.columns) as stdColumns"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.columns) as avgColumns"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.columns) as minColumns"),
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.columns) as maxColumns"),
        
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.printspace_height) as maxPrintSpaceHeight"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.printspace_height) as minPrintSpaceHeight"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.printspace_height) as avgPrintSpaceHeight"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.printspace_height) as stdPrintSpaceHeight"),
                      
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_height) as maxPageHeight"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_height) as minPageHeight"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_height) as avgPageHeight"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_height) as stdPageHeight"),
                      
                      
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_width) as maxPageWidth"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_width) as minPageWidth"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_width) as avgPageWidth"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_width) as stdPageWidth"),
        
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.printspace_width) as maxPrintSpaceWidth"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.printspace_width) as minPrintSpaceWidth"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.printspace_width) as avgPrintSpaceWidth"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.printspace_width) as stdPrintSpaceWidth"),
                      
                ));
        
        if (! empty($states)) {
            $select->where("{$db->NewspapersNewspaper}.state IN (?)", $states);
        }
        
        if (! empty($newspaperIds)) {
            $select->where("{$db->NewspapersNewspaper}.id IN (?)", $newspaperIds);
        }
        // $select->where("{$db->NewspapersNewspaper}.id = ?", $newspaperIds);
        
debug($select);
$result = $this->_db->fetchAll($select);
print_r($result);


die();        
        return $this->query($select);
        return $this->fetchAll(
                    $this->select()
                        ->from($this, array(new Zend_Db_Expr('max(id) as maxId')))
                    );
        
    }
}
