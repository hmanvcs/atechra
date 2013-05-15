<?php

/**
 * Enables the download of uploaded files 
 * 
 */

class DownloadController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		// automatic file mime type handling
		$filename = decode($this->_getParam('filename')); 
		
		$directory = ""; 
		if (!isEmptyString($this->_getParam('dir'))) {
			// add the directory separator 
			$directory = $this->_getParam('dir').DIRECTORY_SEPARATOR; 
		}
		$full_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.decode($this->_getParam('taskid')).DIRECTORY_SEPARATOR.$directory.$filename; 
		
		// file headers to force a download
	    header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    // to handle spaces in the file names 
	    header("Content-Disposition: attachment; filename=\"$filename\"");
	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    header('Pragma: public');
	    readfile($full_path);
 
	    // disable layout and view
	    $this->view->layout()->disableLayout();
	    $this->_helper->viewRenderer->setNoRender(true);
	}
	/**
	 * The default action - show the home page
	 */
	public function fileAction() {
		$this->view->layout()->disableLayout();
	    $this->_helper->viewRenderer->setNoRender(true);
	    
	    $filename = decode($this->_getParam('name'));
	    $filepath = decode($this->_getParam('path'));

	    $full_path = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.PUBLICFOLDER.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR;
	    $full_path = $full_path.$filepath.DIRECTORY_SEPARATOR.$filename;
	    // debugMessage($full_path);
	    
	    // file headers to force a download
	    header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    // to handle spaces in the file names 
	    header("Content-Disposition: attachment; filename=\"$filename\"");
	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    header('Pragma: public');
	    readfile($full_path);
	    
	}	
}
