<?php

class Table_NewspapersFrontPage extends Omeka_Db_Table
{
    public function findByItemId($itemId)
    {
        $select = $this->getSelect();
        $select->where("item_id = $itemId");
        return $this->fetchObject($select);
    }
    
    public function findItemByFrontPage($frontPage)
    {
        $itemId = $frontPage->item_id;
        return $this->getDb()->getTable('Item')->find($itemId);
        
    }
}