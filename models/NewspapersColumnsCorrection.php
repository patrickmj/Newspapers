<?php

class NewspapersColumnsCorrection extends Omeka_Record_AbstractRecord
{
    public $fp_id;
    
    public $user_id;
    
    public $corrected_columns;
    
    public $original_columns;
    
    public $reported_date;
    
    public $accepted_date;
    
    
    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'reported_date', null);
    }
}
