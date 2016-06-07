<?php

class Newspapers_CorrectionsController extends Omeka_Controller_AbstractActionController
{
    
    protected $corrections;
    
    public function init()
    {
        $this->_helper->db->setDefaultModelName('NewspapersColumnsCorrection');
    }
    
    public function browseAction()
    {
        if ($this->getRequest()->isPost()) {
            $acceptCorrectionIds = $this->getParam('accept');
            $acceptCorrectionNpIds = $this->getParam('accept-np');
            $acceptCorrectionNpIds = array(); //temp @todo
            foreach($acceptCorrectionNpIds as $correctionId) {
                $correction = $this->getCorrection($correctionId);
                $frontPageId = $correction->fp_id;
                $frontPage = $this->_helper->db->getTable('NewspapersFrontPage')->find($frontPageId);
                //$newspaper = $this->_helper->db->getTable('NewspapersNewspaper')->find($correctionId);
                $this->_helper->db->getTable('NewspapersFrontPage')->findOthersInNewspaper($frontPage);
                //die();
                // $this->acceptNpCorrection($correction);
            }
            foreach($acceptCorrectionIds as $correctionId) {
                $correction = $this->getCorrection($correctionId);
                
                
                
                
                
                
                
                $this->acceptCorrection($correction);
            }
            
            
            parent::browseAction();
        } else {
            parent::browseAction();
        }
    }
    
    public function correctAction()
    {
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
    
    protected function acceptCorrection($correction)
    {
        $correction->accepted_date = date(Mixin_Timestamp::DATE_FORMAT);
        $frontPage = $this->_helper->db->getTable('NewspapersFrontPage')->find($correction->fp_id);
        $frontPage->columns = $correction->corrected_columns;
        $correction->save();
        $frontPage->save();
    }
    
    protected function getCorrection($correctionId) 
    {
        if (isset($this->corrections[$correctionId])) {
            return $this->corrections[$correctionId];
        }
        $correction = $this->_helper->db->getTable('NewspapersColumnsCorrection')->find($correctionId);
        $this->corrections[$correctionId] = $correction;
        return $correction;
    }
}