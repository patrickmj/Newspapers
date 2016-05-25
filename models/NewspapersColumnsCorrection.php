<?php

class NewspapersColumnsCorrection extends Omeka_Record_AbstractRecord
{
    public $fp_id;
    
    public $np_id;
    
    public $apply_to_np; // apply this correction to all the front pages for this newspaper
    
    public $user_id;
    
    public $notes; // describe the purpose or nature of the correction
    
    public $corrected_columns;
    
    public $original_columns;
    
    public $reported_date;
    
    public $accepted_date;
    
    
    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'reported_date', null);
    }
}
