<?php

class AuditTrail extends BaseRecord {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('audittrail');
		$this->hasColumn('userid', 'integer', 11);
		$this->hasColumn('transactiontype', 'string', 50, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('transactiondetails', 'string', 1000);
		$this->hasColumn('transactiondate','timestamp', null, array('notnull' => true, 'notblank' => true, 'default' => date("Y-m-d H:i:s")));
		$this->hasColumn('executedby', 'integer', 11);
		$this->hasColumn('success', 'enum', null, array('values' => array(1 => 'Y', 0 => 'N'), 'default' => 'N'));
		$this->hasColumn('browserdetails', 'string', 100);
		
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		//$this->addDateFields(array()); 
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"userid.notblank" => "Please select member",
       									"transactiontype.notblank" => "Please select transaction type",
       									"transactiondetails.notblank" => "Please enter transaction details",	
       									"transactiondate.notblank" => "Please enter the transaction date",
       									"executedby.notblank" => "Please select member performing transaction"
       								));
       
		parent::construct();		
	}
	
	public function setUp() {
		parent::setUp(); 
		# member being audited
		$this->hasOne('UserAccount as user',
						 array(
								'local' => 'userid',
								'foreign' => 'id'
							)
					); 
	}
	/**
	 * Clean up the post array before populating the values of the object:
	 * - Remove the blank values for the executedby field which will cause a foreign key error
	 *
	 * @param Array $post_array The post array
	 * 
	 * @see BaseRecord::processPost
	 */
	public function processPost($post_array) {
		// remove the executedby field if it is empty
		if (isArrayKeyAnEmptyString('executedby', $post_array)) {
			unset($post_array['executedby']); 
		}
		parent::processPost($post_array); 
	}	
}
?>
