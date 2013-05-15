<?php
/**
 * Class that uses a Zend_View to creat an email template 
 */

class EmailTemplate extends Zend_View {
	
	public function __construct($config = array()) {
		parent::__construct($config = array());
		// add the path to the scripts
		$this->setScriptPath(APPLICATION_PATH."/views/scripts/email/"); 
		$this->appname = $this->translate("appname");

		// default sign off name and email
		$mail = Zend_Registry::get('mail'); 
		$default_sender = $mail->getDefaultFrom(); 
		$this->signoffname = $this->translate('appname')." ".$default_sender['name'];
		$this->signoffemail = $default_sender['email'];  
	}
}

?>