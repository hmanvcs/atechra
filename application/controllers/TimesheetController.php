<?php

class TimesheetController extends SecureController  {
	
	function getActionforACL() {
		$action = strtolower($this->getRequest()->getActionName()); 
		if ($action == "confirmreject") {
			return ACTION_APPROVE; 
		}
		if ($action == "submitforapproval") {
			return ACTION_CREATE; 
		}
		return parent::getActionforACL();
	}
   /**
	 * Action to approve a Timesheet
	 * 
	 * @see IndexController::approveAction()
	 */
	public function approveAction() {
		$session = SessionWrapper::getInstance(); 
		if (isEmptyString($this->_getParam(URL_FAILURE))) {
			// use the encoded value of the ledgerid
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('timesheet/view/id/'.$this->_getParam('timesheetid'))));
		} 
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the ledgerid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('timesheet/list/l'.HTML_TABLE_COLUMN_SEPARATOR.'status/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '1');
		$this->_setParam(SUCCESS_MESSAGE, 'timesheet_approve_success'); 
		$this->_setParam('timesheetid', decode($this->_getParam('timesheetid')));  
		$this->_setParam('action', ACTION_CREATE); 
		
		// set the status in the timesheet to approved
		$this->_setParam('timesheet', array('status' => 2)); 
		parent::createAction(); 
	}
	
	/**
	 * Action  to confirm rejection of a Timesheet
	 * 
	 * @see IndexController::confirmrejectAction()
	 */
	public function confirmrejectAction() {}
	
	/**
	 * Action to reject a Timesheet 
	 * 
	 * @see IndexController::rejectAction()
	 */
	public function rejectAction() {
		$session = SessionWrapper::getInstance(); 
		if (isEmptyString($this->_getParam(URL_FAILURE))) {
			// use the encoded value of the timesheetid 
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('timesheet/view/id/'.$this->_getParam('timesheetid'))));
		}  
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the timesheetid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('timesheet/list/l'.HTML_TABLE_COLUMN_SEPARATOR.'status/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '0');
		$this->_setParam('timesheetid', decode($this->_getParam('timesheetid'))); 
		$this->_setParam('action', ACTION_CREATE); 
		
		// set the status in the timesheet to rejected
		$this->_setParam('timesheet', array('status' => 3)); 
		parent::createAction(); 
	}
	function viewAction(){
		if (!isEmptyString($this->_getParam('timesheetid'))) {
			// redirect to the view page
			$this->_redirect($this->view->baseUrl("timesheet/view/id/".$this->_getParam('timesheetid'))); 
		}
	}
	/**
     * Action to display the print page 
    */
    public function printAction()  {
        // do nothing 
    }
	/**
	 * Action to submit a Timesheet for approval
	 * 
	 */
	public function submitforapprovalAction() {
		$session = SessionWrapper::getInstance(); 
		
		$timesheet = new Timesheet();
		$timesheet->populate(decode($this->_getParam('id')));
		$timesheet->setStatus(1);
		try {
			$timesheet->save(); 
			$timesheet->sendTimesheetSubmissionEmail(); 
			$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('timesheet_submission_success'));
		} catch (Exception $e){
			$session->setVar(ERROR_MESSAGE, $this->_translate->translate('timesheet_submission_failure').$e->getMessage());
		}
		// redirect to the view page
		$this->_redirect($this->view->baseUrl("timesheet/view/id/".$this->_getParam('id'))); 
	}
	
	function indexAction(){
		if (!isEmptyString($this->_getParam('id'))) {
			$session = SessionWrapper::getInstance();
			
			$timesheet = new Timesheet();
			$timesheet->populate(decode($this->_getParam('id')));
			
			if (!$timesheet->allowUpdate()) {
				// show a message to the user
				$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('timesheet_already_approved_message'));
				// redirect to the view page
				$this->_redirect($this->view->baseUrl("timesheet/view/id/".$this->_getParam('id'))); 
			}
		}
	}
}