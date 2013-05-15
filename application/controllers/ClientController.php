<?php

class ClientController extends SecureController  {
	
	/**
	 * @see SecureController::getActionforACL()
	 * 
	 * The dashboard can only be viewed, however the default is create for the index.phtml file. 
	 *
	 * @return String
	 */
	function getActionforACL() {
		return ACTION_VIEW; 
	}
	
	public function createAction(){
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		//debugMessage($this->_getAllParams());
		if (count($this->_getParam("emailaddresses")) != 0){
			$emailaddresses = array();
			foreach ($this->_getParam("emailaddresses") as $email){
				//debugMessage($email);
				if (isNotAnEmptyString($email['emailaddress'])){
					$emailaddresses[] = array(
											'emailaddress' => $email['emailaddress']
										);
				}
				
			}
			//unset($this->_getParam("emailaddresses"));
			$this->_setParam("emailaddresses", $emailaddresses);
		}
		//debugMessage($emailaddresses);
		//debugMessage($this->_getAllParams());
		//exit;
		parent::createAction();
	}
	public function editAction(){
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		//debugMessage($this->_getAllParams());
		if (count($this->_getParam("emailaddresses")) != 0){
			$emailaddresses = array();
			foreach ($this->_getParam("emailaddresses") as $email){
				//debugMessage($email);
				if (isNotAnEmptyString($email['emailaddress'])){
					$emailaddresses[] = array(
											'clientid' => decode($this->_getParam('id')),
											'emailaddress' => $email['emailaddress']
										);
				}
				
			}
			//unset($this->_getParam("emailaddresses"));
			$this->_setParam("emailaddresses", $emailaddresses);
		}
		//debugMessage($emailaddresses);
		//debugMessage($this->_getAllParams());
		//exit;
		// delete the current email addresses
		$client = new Client();
		$client->populate(decode($this->_getParam('id')));
		$client->deleteCurrentEmailAddresses();
		parent::editAction();
	}
}