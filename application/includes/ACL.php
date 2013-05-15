<?php
require_once 'Zend/Acl.php';
require_once 'Zend/Exception.php';

class ACL extends Zend_Acl   {
	private $availableGroups; 
	
	public function __construct($auserid = "") {
		// do not proceed if no user is defined
		if (isEmptyString($auserid)) {
			return;
		}
		
		$conn = Doctrine_Manager::connection();
		
		// initialize the array of available groups
		$this->availableGroups = array();
		
		// the available actions
		// get the groups from the database for the specified user
		$groups = $conn->fetchAll("SELECT groupid FROM aclusergroup WHERE userid = '".$auserid."'");
		// get the resources from the database
		$resources = $conn->fetchAll("SELECT id FROM aclresource");
		// get the permissions for the specified user
		// TODO: HM -  Remove the need for the c_aclpermission view
		$permissions = $conn->fetchAll("SELECT `p`.`groupid` AS `groupid`,  LOWER(`re`.`name`) AS `resource`,  `p`.`create` AS `create`, `p`.`edit` AS `edit`, `p`.`export` AS `export`,`p`.`approve` AS `approve`,  `p`.`view` AS `view`, `p`.`delete` AS `delete`, `p`.`list` AS `list`, `p`.`acclist` AS `acclist` FROM ((`aclpermission` `p` JOIN `aclresource` `re`) LEFT JOIN `aclusergroup` `ur` ON ((`p`.`groupid` = `ur`.`groupid`))) WHERE ((`p`.`resourceid` = `re`.`id`) AND ur.userid = '".$auserid."')");
		
		// add the groups to the ACL
		foreach ($groups as $value) {
			$group = new AclGroup();
			// load the details of the user group
			$group->populate($value['groupid']);
			$this->addRole($group); 
			
			// add the group to the array of available groups
			$this->availableGroups[] = $group;
		}
		
		// add the resources to the ACL, the name of the resource and its parent are what are used as identifiers for the resource in the ACL
		foreach ($resources as $value) {
			$ares = new AclResource();
			$ares->populate($value['id']);
			$this->add($ares);
		}
		// process the permissions for all the actions
		$allactions = self::getActions();
				
		// add the permissions to the ACL
		foreach ($permissions as $value) {
			foreach ($allactions as $theaction) {
				
				if ($value[$theaction] == '1') {
					// the name of the resource is used as a key while the id of the group is used as a key
					$this->allow($value['groupid'], $value['resource'], $theaction); 
				} 
			}
		}
	}
	
	/**
	 * Checl whether the user can execute the action on the named resource
	 *
	 * @param String $resourceName The name of the resource
	 * @param String $action The action to be executred
	 */
	function checkPermission($resourceName, $action) {
		// make the resourcenmae lower case to match the ones which were loaded
		$resourceName = strtolower($resourceName); 
		if (!$this->availableGroups) {
			// there are no groups loaded
			return false; 
		}
		foreach ($this->availableGroups as $group) {
			try {	
				// check if the action can be executed on the resource for the specific role
				// use the parent method since we are dealing with one role at a time here
				if (parent::isAllowed($group, $resourceName, $action)) {
					// action is allowed on the resource 
					return true; 
				}
			} catch (Zend_Exception $ze){
				// either a resource or a group is not defined in the ACL. Deny access to it
				error_log($ze->__toString());
				return false; 
			}
		}
		// by default the action is denied on the resource
		return false; 
	}
	/**
	 * Return an array containing the defined actions
	 *
	 * @return Array Array containing the defined actions
	 */
	static function getActions() {
		return array(ACTION_CREATE, ACTION_EDIT, ACTION_VIEW, ACTION_LIST, ACTION_DELETE, ACTION_APPROVE, ACTION_EXPORT, ACTION_ACCOUNT_LIST);
	}
	/**
	 * Overidden method from Zend_ACL to enable processing of multiple roles
	 *
	 * @param Zend_Acl_Role_Interface|string|array $group This parameter is ignored
	 * @param Zend_Acl_Resource_Interface|string|array $resource
	 * @param string $action
	 * @return Whether or not the action can be executed on the resource 
	 */
	public function isAllowed($group, $resource, $action) {
		return $this->checkPermission($resource, $action); 
	}
	
}
?>