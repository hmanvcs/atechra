<?php
/**
 * Collection of functions to handle application events
 */
/**
 * Constants defining the available events within the application
 */
define("USER_LOGIN", "user.login"); 
define("USER_LOGOUT", "user.logout");
define("USER_CHANGE_EMAIL", "user.change_email");  
define("USER_CHANGE_PASSWORD", "user.change_password"); 
define("USER_RECOVER_PASSWORD", "user.recover_password");
define("USER_SIGNUP", "user.signup");
define("USER_DEACTIVATE", "user.deactivate"); 

/**
 * Initialize and Configure an SFEventDispatcher instance
 *
 * @return sfEventDispatcher A configured event dispatcher instance
 */
function initializeSFEventDispatcher() {
   $eventdispatcher = new sfEventDispatcher(); 
   $eventdispatcher->connect(USER_LOGIN, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_LOGOUT, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_CHANGE_EMAIL, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_CHANGE_PASSWORD, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_RECOVER_PASSWORD, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_SIGNUP, "auditTransactionEventHandler");
   $eventdispatcher->connect(USER_DEACTIVATE, "auditTransactionEventHandler");
   
   return $eventdispatcher; 
}
/**
 * Handle the events
 *
 * @param sfEvent $event The event being handled
 * @return bool TRUE if the audit trail for the transaction is saved sucessfully, FALSE otherwise
 */

function auditTransactionEventHandler($event) {
	$audit_trail = new AuditTrail();
	$audit_trail->processPost($event->getParameters());
	try {
		$audit_trail->save();
	} catch (Exception $e) {
		$logger = Zend_Registry::get("logger");
		$logger->err($e->getMessage()); 
		return false; 
	}
	
	return true;
}
?>