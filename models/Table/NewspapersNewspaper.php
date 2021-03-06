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
        
        if (isset($params['states'])) {
            $select->where("{$db->NewspapersNewspaper}.state IN (?)", $params['states']);
        }
        
        if (isset($params['newspaperIds'])) {
            $select->where("{$db->NewspapersNewspaper}.id IN (?)", $params['newspaperIds']);
        }
        
        if (isset($params['columns'])) {
            $select->where("{$db->NewspapersFrontPage}.columns = ?", $params['columns']);
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
        
        $result = $this->_db->fetchAll($select);
        return $result[0];
        
        
    }
}
