<?php

class TaskcommentController extends SecureController {
	function getResourceForACL() {
		return "Task";
	}
	/**
	 * @see SecureController::getActionforACL()
	 * 
	 * @return String
	 */
	function getActionforACL() {
		return ACTION_VIEW; 
	}
	function viewAction(){
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();	  
	}
	function createAction(){
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();	
	    $this->_helper->viewRenderer->setNoRender(TRUE);  

	    // check that there is an attachment
		if (isNotAnEmptyString($_FILES['name']['name'])) {
		// debugMessage($this->_getAllParams());
			$config = Zend_Registry::get("config"); 
			$this->_translate = Zend_Registry::get("translate"); 
			$session = SessionWrapper::getInstance(); 
			
			// only upload a file if the attachment field is specified		
			$upload = new Zend_File_Transfer_Adapter_Http();
			$upload->setOptions(array('useByteString' => false));
			
			// File validators
			$upload->addValidator('Extension', false, $config->document->allowedformats);
		 	$upload->addValidator('Size', false, $config->document->maximumfilesize);
			
			// the document file object before upload
			$file = $upload->getFileInfo('name');
			$oldfilename = $file['name']['name'];
			
			// base path for uploaded
		 	$destination_path = APPLICATION_PATH."/../".PUBLICFOLDER."/uploads/";
		 	//debugMessage($destination_path);
		 	
			// determine if matter has a research folder. If none, create the folder
			if(!is_dir($destination_path.$this->_getParam('taskid'))){
				// no folder exits. Create the folder
				mkdir($destination_path.$this->_getParam('taskid'), 0755);
			}
			
		 	// the destination for the uploaded file. Add the taskid to the path
			$upload->setDestination($destination_path.$this->_getParam('taskid'));
			
			// rename the base document file name
			$cur_timestamp = mktime();
			$newfilename = $cur_timestamp.".".findExtension($oldfilename); 
			//debugMessage($newfilename);
			
			// rename the file to be uploaded using the set timestamp 
			$upload->addFilter('Rename',  array('target' => $destination_path.$this->_getParam('taskid')."/".$newfilename, 'overwrite' => true));
			
			// process the file upload
			if($upload->receive()){
				// the profile image info before upload
				$file = $upload->getFileInfo('name');
				
				// set document object data items for saving
				// file has been uploaded sucessfully 
				$this->_setParam('newfilename', $upload->getFileName(null, false));
				$this->_setParam('originalfilename', $oldfilename);
			} else {
				debugMessage("There were errors while uploading");
				$uploaderrors = $upload->getMessages();
				debugMessage($uploaderrors);
				$customerrors = array();
				if(!isArrayKeyAnEmptyString('fileExtensionFalse', $uploaderrors)){
					$custom_exterr = sprintf($this->_translate->translate('document_invalid_ext_error'), $oldfilename, $config->document->allowedformats);
					$customerrors['fileExtensionFalse'] = $custom_exterr;
				}
				if(!isArrayKeyAnEmptyString('fileSizeTooBig', $uploaderrors)){
					$customerrors['fileSizeTooBig'] = $uploaderrors['fileSizeTooBig'];
				}
				
				$session->setVar(ERROR_MESSAGE, 'The following errors occured <ul><li>'.implode('</li><li>', $customerrors).'</li></ul>');
				$session->setVar(FORM_VALUES, $this->_getAllParams());
				
				// return to index page
				$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
			}
		}
	    parent::createAction();
	}
}

