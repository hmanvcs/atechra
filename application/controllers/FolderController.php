<?php

class FolderController extends SecureController  {
	
	function indexAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		// determine if user has permission to create folders
		if(!$useraccount->canCreateFolders()) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	function viewAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		$currentfolderid = 1;
		if(!isEmptyString($this->_getParam('id'))){
			$currentfolderid = decode($this->_getParam('id'));
		}
		// determine if user has permission to view corporate files and folders
		if(!$useraccount->canViewFilesAndFolders() || ($currentfolderid != 1 && !$useraccount->hasFolderAccess($currentfolderid))) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	function listAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		// determine if user has permission to view corporate files and folders
		if(!$useraccount->canViewFilesAndFolders()) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	
	function createAction() {
		$formvalues = $this->_getAllParams();
		$config = Zend_Registry::get("config"); 
		$this->_translate = Zend_Registry::get("translate"); 
		$session = SessionWrapper::getInstance();  
		// debugMessage($formvalues);
		// base path for uploads
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		if(is_dir($base_path.$formvalues['parentpath'])){
			$base_path = $base_path.$formvalues['parentpath'].DIRECTORY_SEPARATOR;
		}
		
		$haserrors = false;
		// determine if folder exists
		if(file_exists($base_path.$formvalues['name'])){
			$haserrors = true;
			$customerrors = array(sprintf($this->_translate->translate('folder_name_unique_error'), $formvalues['name']));
		}
		// determine if folder has invalid names
		if(preg_match("/^[a-zA-Z0-9 _-]+$/", $formvalues['name']) != 1){ 
			$haserrors = true;
			$customerrors = array(sprintf($this->_translate->translate('folder_name_invalid_error'), $formvalues['name']));
		}
		
		// Throw exceptions for any errors
		if($haserrors){
			$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
			$session->setVar(FORM_VALUES, $this->_getAllParams());
			$this->_redirect(decode($this->_getParam(URL_FAILURE)));
		}
		
		// no errors exist Create the folder		
		if(!$haserrors){
			try {
				mkdir($base_path.$formvalues['name'], 0755);	
			} catch (ErrorException  $e) {
				$customerrors = array($e->getMessage());
				$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
				$session->setVar(FORM_VALUES, $this->_getAllParams());
				$this->_redirect(decode($this->_getParam(URL_FAILURE)));
			}
		}
		
		// exit();
		parent::createAction();
	}
	function editAction() {
		$formvalues = $this->_getAllParams();
		$config = Zend_Registry::get("config"); 
		$this->_translate = Zend_Registry::get("translate"); 
		$session = SessionWrapper::getInstance();
		// debugMessage($formvalues);
		// base path for uploads
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		if(is_dir($base_path.$formvalues['parentpath'])){
			$base_path = $base_path.$formvalues['parentpath'].DIRECTORY_SEPARATOR;
		}

		// determine if folder name has been changed and rename it
		if(($formvalues['name'] != $formvalues['oldfoldername'])){
			$haserrors = false;
			// determine if folder exists
			if(file_exists($base_path.$formvalues['name'])){
				$haserrors = true;
				$customerrors = array(sprintf($this->_translate->translate('folder_name_unique_error'), $formvalues['name'])); exit();
			}
			// determine if folder has invalid names
			if(preg_match("/^[a-zA-Z0-9 _-]+$/", $formvalues['name']) != 1){
				$haserrors = true;
				$customerrors = array(sprintf($this->_translate->translate('folder_name_invalid_error'), $formvalues['name']));
			}
			
			// Throw exceptions for any errors
			if($haserrors){
				$this->_setParam('id', decode($formvalues['id']));
				$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
				$session->setVar(FORM_VALUES, $this->_getAllParams());
				$this->_redirect(decode($this->_getParam(URL_FAILURE)));
			}
			
			// no errors exist Create the folder		
			if(!$haserrors){
				try {
					rename($base_path.$formvalues['oldfoldername'], $base_path.$formvalues['name']);	
				} catch (ErrorException  $e) {
					$this->_setParam('id', decode($formvalues['id']));
					$customerrors = array($e->getMessage());
					$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
					$session->setVar(FORM_VALUES, $this->_getAllParams());
					$this->_redirect(decode($this->_getParam(URL_FAILURE)));
				}
			}
		}
		 	
		// exit();
		parent::createAction();
	}	
	function deleteAction(){		
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		
		$formvalues = $this->_getAllParams();
		$config = Zend_Registry::get("config"); 
		$this->_translate = Zend_Registry::get("translate"); 
		$session = SessionWrapper::getInstance();  
		// the id being deleted
		$id = decode($formvalues['id']);
		
		// populate folder to be deleted
		$folder = new Folder();
		$folder->populate($id);
		
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		if(is_dir($base_path.decode($formvalues['parentpath']))){
			$base_path = $base_path.decode($formvalues['parentpath']);
		}
		
		// debugMessage($base_path); debugMessage($folder->toArray());exit();
		try {
			// delete folder from the upload location
			if(unlinkRecursive($base_path)){
				// delete file from the database
				if($folder->delete()){
					$this->_setParam(SUCCESS_MESSAGE, $this->_translate->translate($this->_getParam(SUCCESS_MESSAGE)));
					$session->setVar(SUCCESS_MESSAGE, $this->_getParam(SUCCESS_MESSAGE));
				} else {
					$session->setVar(ERROR_MESSAGE, "An error occured in deleting the Folder. ".$folder->getErrorStackAsString()); 
				}
			}
		} catch (Exception $e) {
			$session->setVar(ERROR_MESSAGE, "An error occured in deleting the File. ".$e->getMessage());
		}
		
		// return to parent folder
		$this->_redirect(decode($this->_getParam(URL_SUCCESS)));
	}	
}