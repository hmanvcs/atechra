<?php

class AclResource extends BaseEntity implements Zend_Acl_Resource_Interface {
	# constructor function	
	public function setTableDefinition() {
		parent::setTableDefinition();
		$this->setTableName('aclresource');
		$this->hasColumn('name', 'string', 50, array('notnull' => true, 'notblank' => true, 'unique' => true));
		$this->hasColumn('description', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('create', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('edit', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('view', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('list', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('delete', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('approve', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('export', 'enum', null, array('values' => array(0 => '0', 1 => '1'), "default" => '0'));
		$this->hasColumn('acclist', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"name.unique" => $this->translate->_("resource_name_unique_unique_error"),
       									"name.notblank" => $this->translate->_("resource_name_error"),
       									"description.notblank" => $this->translate->_("resource_description_error")
       								)); 
	}
	/**
	 * @see Zend_Acl_Resource_Interface::getResourceId()
	 *
	 * @return string The name of the resource
	 */
	public function getResourceId() {
		return strtolower($this->getName());
	}
	/**
	 * Return an array containing the ids and names of all parent resources for this resource
	 *
	 * @return Array an array of all parent resources, the key is the id of the resource while the value is the name of the resource
	 */
	function getAllParentResources() {
		$parentquery = "SELECT r.name AS optiontext, r.id AS optionvalue FROM aclresource AS r ORDER BY optiontext";
		
		if (! isEmptyString($this->getID())) {
			$parentquery .= " AND id <> '" . $this->getID() . "'";
		}
		return getOptionValuesFromDatabaseQuery($parentquery);
	}
	
	function setUp() {
		parent::setUp();
		// each resource can have many permissions
		$this->hasMany('AclPermission as permissions', 
								array(
									'local' => 'id',
									'foreign' => 'resourceid',
								)
						);
	}
}
?>