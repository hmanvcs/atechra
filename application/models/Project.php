<?php

class Project extends BaseEntity {
	
	/**
	 * The description of the status value 
	 * @var String 
	 */
	private $typedescriptionvalues = null;
	
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('project');
		$this->hasColumn('clientid','integer', 11);
		$this->hasColumn('title', 'string', 255, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('jobnumber', 'string', 75, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('type', 'integer', 11);
		$this->hasColumn('startdate', 'date');
		$this->hasColumn('enddate', 'date');
		$this->hasColumn('description', 'string', 500);
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// specify the date columns
		$this->addDateFields(array("startdate", "enddate"));
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
			"clientid.notblank" => $this->translate->_("project_client_error"),
			"title.notblank" => $this->translate->_("project_title_error"),
       		"jobnumber.notblank" => $this->translate->_("project_jobnumber_error"),
       		"type.notblank" => $this->translate->_("project_type_error"),
       		"startdate.notblank" => $this->translate->_("project_startdate_error"),
       		"enddate.notblank" => $this->translate->_("project_enddate_error")
		));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		$this->hasOne('Client as client',
							array('local' => 'clientid',
								 	'foreign' => 'id'
							)
						);
		$this->hasMany('ProjectAssignment as projectassignments',
							array('local' => 'id',
								 	'foreign' => 'projectid'
							)
						);	
		$this->hasMany('StatusReport as statusreports',
							array('local' => 'id',
								 	'foreign' => 'projectid'
							)
						);
	}
	public function getTypeDescription(){
		//load the type description values 
		if (is_null($this->typedescriptionvalues)) {
				// load the type description from the database
				$l = new LookupType();
				$l->setName('PROJECT_TYPES'); 
				$this->typedescriptionvalues = $l->getOptionValuesAndDescription(); 
		}
		return $this->typedescriptionvalues[$this->getType()];
	}
}
?>
