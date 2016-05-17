<?php

class Newspapers_CorrectionsController extends Omeka_Controller_AbstractActionController
{
    public function correctAction()
    {
        $db = get_db();
        $params = $this->getParams();
        $fp = $db->getTable('NewspapersFrontPage')->find($params['fp_id']);
        $user = current_user();
        $userId = $user ? $user->id : null;
        $originalColumns = $fp->columns;
        $correctedColumns = $params['corrected_columns'];
        
        $correction = new NewspapersColumnsCorrection();
        $correction->fp_id = $fp->id;
        $correction->user_id = $userId;
        $correction->original_columns = $originalColumns;
        $correction->corrected_columns = $correctedColumns;
        
        $correction->save();
        
        if ($userId == 1) {
            $this->acceptCorrection($correction);
        }
    }
    
    public function acceptAction()
    {
        $correctionId = $this->getParam('correction');
        $db = get_db();
        $correction = $db->getTable('NewspapersColumnsCorrection')>find($correctionId);
        $this->acceptCorrection($correction);
    }
    
    
    protected function acceptCorrection($correction)
    {
        $correction->accepted_date = date(Mixin_Timestamp::DATE_FORMAT);
        $correction->save();
    }
}