<?php

class TestController extends IndexController  {
	
    function filepermAction(){
    	$session = SessionWrapper::getInstance(); 
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		
    	$filep = new File();
    	debugMessage($filep->toArray());
    	
    }
}

