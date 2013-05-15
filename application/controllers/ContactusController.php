<?php

class ContactusController extends IndexController  {
	
	/**
	 * Sends the details of the support form by email 
	 */
	public function confirmationAction() {
		$mail = Zend_Registry::get("mail");
		$default_sender = $mail->getDefaultFrom(); 
		$mail->addTo($default_sender['email']);
		$mail->setSubject($this->_getParam('topic')); 
		$mail->setBodyText("From ".$this->_getParam('names')."(".$this->_getParam('youremail').")\n\n".$this->_getParam('message'));
		
		$mail->send(); 
		
	}
}

