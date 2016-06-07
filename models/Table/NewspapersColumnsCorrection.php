<?php

class Table_NewspapersColumnsCorrection extends Omeka_Db_Table
{
    public function applySearchFilters($select, $params)
    {
        $select->where("accepted_date IS NULL");
    }
}