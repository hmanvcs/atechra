<?php

class CorporatefileController extends SecureController  {
	/**
	 * @see SecureController::getResourceForACL()
	 */
	public function getResourceForACL() {
		// TODO Auto-generated method stub
		return "Corporate File"; 
	}
	/**
	 * @see SecureController::getActionforACL()
	 * 
	 * @return String
	 */
	function getActionforACL() {
		$action = strtolower($this->getRequest()->getActionName()); 
		if ($action == "browse") {
			return ACTION_EDIT; 
		}
		if ($action == "corporatefilesearch") {
			return ACTION_LIST; 
		}
		return parent::getActionforACL(); 
	}
	function indexAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		$currentfileid = '';
		if(!isEmptyString($this->_getParam('id'))){
			$currentfileid = decode($this->_getParam('id'));
		}
		// determine if user has permission to upload corporate files
		if(!$useraccount->canUploadFiles() || (!$useraccount->hasFileAccess($currentfileid))) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	function viewAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		$currentfileid = '';
		if(!isEmptyString($this->_getParam('id'))){
			$currentfileid = decode($this->_getParam('id'));
		}
		// determine if user has permission to view corporate files and folders
		if(!$useraccount->canViewFilesAndFolders() || (!$useraccount->hasFileAccess($currentfileid))) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	function listAction(){
		$session = SessionWrapper::getInstance(); 
		$useraccount = new UserAccount();
		$useraccount->populate($session->getVar('userid'));
		$currentfolderid = 1;
		if(!isEmptyString($this->_getParam('parentid'))){
			$currentfolderid = $this->_getParam('parentid');
		}
		// determine if user has permission to view corporate files and folders
		if(!$useraccount->canViewFilesAndFolders() || ($currentfolderid != 1 && !$useraccount->hasFolderAccess($currentfolderid))) {
			// redirect to the access denied page 
			$this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	function createAction() {
		$formvalues = $this->_getAllParams();
		// debugMessage($formvalues);
		$allfilesarray = array();
		
		// Only Upload if document for upload exists
		$config = Zend_Registry::get("config"); 
		$this->_translate = Zend_Registry::get("translate"); 
		$session = SessionWrapper::getInstance(); 
		
		// check if post size is exceeded and return to create
		if(intval($_SERVER['CONTENT_LENGTH']) > intval(ini_get("post_max_size")* 1024 * 1024)){
			$customerrors = array(sprintf($this->_translate->translate('global_post_maxsize_error'), formatBytes(intval(ini_get("post_max_size")* 1024 * 1024), 0)));
			$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
			$session->setVar(FORM_VALUES, $this->_getAllParams());
			$this->_redirect(decode($session->getVar('custom_failure_url')));
		}
		 
		// only upload a file if the attachment field is specified		
		$upload = new Zend_File_Transfer();
		$upload->setOptions(array('useByteString' => false));
		
		// File validators
		$upload->addValidator('Extension', false, $config->document->allowedformats);
	 	$upload->addValidator('Size', false, $config->document->maximumfilesize);
		
		// base path for uploaded
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		if(is_dir($base_path.$formvalues['parentpath'])){
			$base_path = $base_path.$formvalues['parentpath'].DIRECTORY_SEPARATOR;
		}
		/*
		// check for validation errors
		$customerrors = array();
		$errorsfields = array();
		if (!$upload->isValid()){
			debugMessage($upload);
			foreach($upload->getFileInfo() as $file => $info) {
				//debugMessage($info);
				foreach ($upload->getMessages() as $key => $val){
					//$errorsfields[$file] = $file;
					if($key == 'fileExtensionFalse'){
						$customerrors[$key] = sprintf($this->_translate->translate('corporatefile_invalid_ext_error'), 'name', $config->document->allowedformats);
					}
					if($key == 'fileSizeTooBig'){
						$customerrors[$key] = sprintf($this->_translate->translate('corporatefile_invalid_size_error'), 'name', formatBytes($config->document->maximumfilesize,0));
					}
				}
			}
			$this->_setParam('id', $formvalues['id']);
			$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
			$session->setVar(FORM_VALUES, $this->_getAllParams());
			$session->setVar('errorfiles', $errorsfields);
			// $this->_redirect(decode($this->_getParam(URL_FAILURE)));
		}*/
		//debugMessage($customerrors); debugMessage($errorsfields); exit();
		
		// debugMessage($base_path);
		// set the package name md5 as the destination for the uploaded files. 
		$upload->setDestination($base_path);
		$counter = 0;
		// loop through available documents and save 
		foreach($upload->getFileInfo() as $file => $info) {
			// debugMessage($file); debugMessage($info);
			$rowcount = $formvalues['row_'.$file];
			// Process each file uploaded
			if(!isEmptyString($info['name'])){
				if($upload->receive($info['name'])){
					$this->_setParam('filename', $info['name']);
					$this->_setParam('title', $formvalues['title_'.$rowcount]);
					$this->_setParam('description', $formvalues['description_'.$rowcount]);
					$this->_setParam('createdby', $session->getVar('userid'));
					$this->_setParam('extension', findExtension($this->_getParam('filename')));
					$this->_setParam('mimetype', $info['type']);
					$this->_setParam('filesize', $info['size']);
					$allfilesarray[] = $this->_getAllParams();
				} else {
					// custom error handling
					$uploaderrors = $upload->getMessages();
					$customerrors = array();
					// debugMessage($info); exit();
					if(!isArrayKeyAnEmptyString('fileExtensionFalse', $uploaderrors)){
						$custom_exterr = sprintf($this->_translate->translate('corporatefile_invalid_ext_error'), $info['name'], $config->document->allowedformats);
						$customerrors['fileExtensionFalse'] = $custom_exterr;
					}
					if(!isArrayKeyAnEmptyString('fileUploadErrorIniSize', $uploaderrors)){
						$custom_exterr = sprintf($this->_translate->translate('corporatefile_invalid_size_error'), $info['name'], formatBytes($config->document->maximumfilesize,0));
						$customerrors['fileUploadErrorIniSize'] = $custom_exterr;
					}
					if(!isArrayKeyAnEmptyString('fileSizeTooBig', $uploaderrors)){
						$custom_exterr = sprintf($this->_translate->translate('corporatefile_invalid_size_error'), $info['name'], formatBytes($config->document->maximumfilesize,0));
						$customerrors['fileSizeTooBig'] = $custom_exterr;
					}
					
					$this->_setParam('id', $formvalues['id']);
					$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
					$session->setVar(FORM_VALUES, $this->_getAllParams());
					// return to index page
					$this->_redirect(decode($this->_getParam(URL_FAILURE)));
				}
			}
			$counter++;
		}
		
		// debugMessage($allfilesarray); exit();
		$customerrors = array();
		$haserrors = false;
		// collection to be used to save files
		$file_collection = new Doctrine_Collection(Doctrine_Core::getTable("CorporateFile"));
		foreach ($allfilesarray as $afile) {
			$file = new CorporateFile();
			$file->processPost($afile);
			
			if($file->isValid()) {
				$file_collection->add($file);
			} else {
				$haserrors = true;
				$customerrors[] = $file->getErrorStackAsString();
			}
		}
		
		// if any errors return to form
		if($haserrors){
			$session->setVar(ERROR_MESSAGE, $customerrors);
			$session->setVar(FORM_VALUES, $this->_getAllParams());
			$this->_redirect(decode($this->_getParam(URL_FAILURE)));
		}
		// save collection if there are no errors
		try {
			$file_collection->save();
			
			// generate array of fileids
			$idsaray = array();
			foreach($file_collection as $file){
				$idsaray[] = $file->getID();
			}
			// redirect to success page
			if(!isEmptyString($this->_getParam(URL_SUCCESS))) {
				$url = decode($this->_getParam(URL_SUCCESS));
				if(count($idsaray) == 1){
					$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('corporatefile_upload_success'));
				} else {
					$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('corporatefile_upload_multiple_success'));
				}
				$this->_redirect($url);
			} else {
				if(count($idsaray) == 1){
					$this->_redirect($this->view->baseUrl('corporatefile/view/id/'.encode($idsaray['0'])));
				} else {
					$this->_redirect($this->view->baseUrl('corporatefile/view/ids/'.encode(implode(',', $idsaray))));
				}
			}
			if (isNotAnEmptyString($this->_getParam(SUCCESS_MESSAGE))) {
    			$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate($this->_getParam(SUCCESS_MESSAGE)));
    		}
		} catch (Exception $e) {
			// handle and errors due from saving
			$session->setVar(ERROR_MESSAGE, $e->getMessage().$file_collection->getErrorStackAsString());
			$session->setVar(FORM_VALUES, $this->_getAllParams());
			$this->_redirect(decode($this->_getParam(URL_FAILURE)));
		}
	}
	function editAction() {
		$formvalues = $this->_getAllParams();
		// debugMessage($formvalues);
		// base path for uploads
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		if(is_dir($base_path.$formvalues['parentpath'])){
			$base_path = $base_path.$formvalues['parentpath'].DIRECTORY_SEPARATOR;
		}
		$formvalues['filename'] = $formvalues['filename'].'.'.findExtension($this->_getParam('currentfile'));
		// determine if folder name has been changed and rename it
		// rename folder
		rename($base_path.$formvalues['currentfile'], $base_path.$formvalues['filename']);
		$this->_setParam('filename', $formvalues['filename']);
		 	
		// exit();
		parent::createAction();
	}
	
	function browseAction() {
		$this->view->layout()->disableLayout();
	    $this->_helper->viewRenderer->setNoRender(true);
	    
	    $fileid = decode($this->_getParam('id')); 
	    $file = new CorporateFile();
		$file->populate($fileid);
		
		$filepath = getAbsoluteFilePath($file->getID());
		$filename = $file->getFileName();
		$full_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
	    $full_path = $full_path.$filepath.DIRECTORY_SEPARATOR.$filename;
	    
		$thepath = $this->view->baseUrl("uploads/".$file->getFolder()->getRelativeFolderPath().'/'.$filename);
		// debugMessage($thepath);
		echo "<img src='".$thepath."' title='".$filename."' />";
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
		
		// populate file to be deleted
		$file = new CorporateFile();
		$file->populate($id);
		
		$base_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
		// check if a parent folder is available and add it to path 
		$base_path = $base_path.decode($formvalues['parentpath']).DIRECTORY_SEPARATOR;
		
		// debugMessage($base_path); debugMessage($file->toArray());exit();
		try {
			// unlink file from the upload location
			if(unlink($base_path.$file->getFileName())){
				// delete file from the database
				if($file->delete()){
					$this->_setParam(SUCCESS_MESSAGE, $this->_translate->translate($this->_getParam(SUCCESS_MESSAGE)));
					$session->setVar(SUCCESS_MESSAGE, $this->_getParam(SUCCESS_MESSAGE));
				} else {
					$session->setVar(ERROR_MESSAGE, "An error occured in deleting the File. ".$file->getErrorStackAsString()); 
				}
			}
		} catch (Exception $e) {
			$session->setVar(ERROR_MESSAGE, "An error occured in deleting the File. ".$e->getMessage());
		}
		
		// return to parent folder
		$this->_redirect(decode($this->_getParam(URL_SUCCESS)));
	}
	/**
     * Redirect payment report searches to maintain the urls as per zend format 
     */
    public function corporatefilesearchAction() {
    	$this->_helper->redirector->gotoSimple("list", "corporatefile", 
    											$this->getRequest()->getModuleName(),
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));
    }
}