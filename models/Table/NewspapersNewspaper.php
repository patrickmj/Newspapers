<?php

class Table_NewspapersNewspaper extends Omeka_Db_Table
{
    public function findByCollection($collectionId)
    {
        $select = $this->getSelect();
        $select->where("collection_id = $collectionId");
        return $this->fetchObject($select);
    }
    
    public function findByLccn($lccn)
    {
        $select = $this->getSelect();
        
        $select->where("lccn = ?", $lccn);
        return $this->fetchObject($select);
    }
    
    public function importExists($lccn)
    {
        $count = $this->count(array('lccn' => $lccn));
        return $count !== 0;
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
        
        $select->join($db->NewspapersIssue,
                "{$db->NewspapersIssue}.newspaper_id = {$db->NewspapersNewspaper}.id", array());

        $select->join($db->NewspapersFrontPage, 
                "{$db->NewspapersFrontPage}.issue_id = {$db->NewspapersIssue}.id", 
                array(
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.columns) as stdColumns"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.columns) as avgColumns"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.columns) as minColumns"),
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.columns) as maxColumns"),
        
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_height) as maxPageHeight"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_height) as minPageHeight"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_height) as avgPageHeight"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_height) as stdPageHeight"),
                      
                      new Zend_Db_Expr("max({$db->NewspapersFrontPage}.page_width) as maxPageWidth"),
                      new Zend_Db_Expr("min({$db->NewspapersFrontPage}.page_width) as minPageWidth"),
                      new Zend_Db_Expr("avg({$db->NewspapersFrontPage}.page_width) as avgPageWidth"),
                      new Zend_Db_Expr("std({$db->NewspapersFrontPage}.page_width) as stdPageWidth"),
                ));
        
        if (! empty($states)) {
            $select->where("{$db->NewspapersNewspaper}.state IN (?)", $states);
        }
        
        if (! empty($newspaperIds)) {
            $select->where("{$db->NewspapersNewspaper}.id IN (?)", $newspaperIds);
        }
        
        $result = $this->_db->fetchAll($select);
        return $result[0];
        
        
    }
}
