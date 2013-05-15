<?php
/**
 * BaseEntity contains the most common fields for application object
 * 
 */
abstract class BaseEntity extends BaseRecord {
	
	public function setTableDefinition() {
		parent::setTableDefinition();
		$this->hasColumn('id', 'integer', 11, array('primary' => true, 'autoincrement' => true));
		$this->hasColumn('createdby', 'integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('lastupdatedby', 'integer', 11);
		
	}
	
	public function setUp() {
		parent::setUp(); 
		// automatically set timestamp column values for datecreated and lastupdatedate 
		$this->actAs('Timestampable', 
						array('created' => array(
												'name' => 'datecreated',    
											),
							 'updated' => array(
												'name'     =>  'lastupdatedate',    
												'onInsert' => false,
												'options'  =>  array('notnull' => false)
											)
						)
					);
		// created by				
		$this->hasOne('UserAccount as creator', 
								array(
									'local' => 'createdby',
									'foreign' => 'id',
								)
						);
	}
	
	public function construct() {
		parent::construct();
		// add the custom error messages for the fields in the base entity
       	$this->addCustomErrorMessages(array(
       									"createdby.notblank" => "Please provide the user"
       								));
       
				
	}
}
?>