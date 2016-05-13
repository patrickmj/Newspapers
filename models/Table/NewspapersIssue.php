<?php

class Table_NewspapersIssue extends Omeka_Db_Table
{
    public function findByItemId($itemId)
    {
        $select = $this->getSelect();
        $select->where("item_id = $itemId");
        return $this->fetchObject($select);
    }
    
    public function findByLocUri($locUri)
    {
        $select = $this->getSelect();
        $select->where("loc_uri = ?", $locUri);
        return $this->fetchObject($select);
    }
    
    public function findItemByFrontPage($frontPage)
    {
        $itemId = $frontPage->item_id;
        return $this->getDb()->getTable('Item')->find($itemId);
    }
    
    public function importExists($locUri)
    {
        $count = $this->count(array('loc_uri' => $locUri));
        return $count !== 0;
    }
}
