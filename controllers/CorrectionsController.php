<?php

class Newspapers_CorrectionsController extends Omeka_Controller_AbstractActionController
{
    public function correctAction()
    {
        $db = get_db();
        $params = $this->_getAllParams();
        $user = current_user();
        $userId = $user ? $user->id : null;
        $frontPageId = $params['frontPageId'];
        $correctedColumns = $params['correctedColumns'];
        $originalColumns = $params['originalColumns'];
        $newspaperId = $params['newspaperId'];
        
        $correction = new NewspapersColumnsCorrection();
        $correction->fp_id = $frontPageId;
        $correction->user_id = $userId;
        $correction->np_id = $newspaperId;
        $correction->original_columns = $originalColumns;
        $correction->corrected_columns = $correctedColumns;
        $correction->save();
        
        if ($userId == 1) {
            $this->acceptCorrection($correction);
        }
        $jsonResponse = array();
        $this->_helper->jsonApi($jsonResponse);
    }
    
    public function acceptAction()
    {
        $correctionId = $this->_getParam('correction');
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