<?php

	# This class require_onces functions to access and use the different drop down lists within
	# this application

	/**
	 * function to return the results of an options query as array. This function assumes that
	 * the query returns two columns optionvalue and optiontext which correspond to the corresponding key
	 * and values respectively. 
	 * 
	 * The selection of the names is to avoid name collisions with database reserved words
	 * 
	 * @param String $query The database query
	 * 
	 * @return Array of values for the query 
	 */
	function getOptionValuesFromDatabaseQuery($query) {
		$conn = Doctrine_Manager::connection(); 
		$result = $conn->fetchAll($query);
		$valuesarray = array();
		foreach ($result as $value) {
			$valuesarray[$value['optionvalue']]	= htmlentities($value['optiontext']);
		}
		return decodeHtmlEntitiesInArray($valuesarray);
	}
        # function to generate months
	function getAllMonths() {
		$months = array(
		"January" => "January",		
		"February" => "February",
		"March" => "March",
		"April" => "April",
		"May" => "May",		
		"June" => "June",
		"July" => "July",
		"August" => "August",
		"September" => "September",		
		"October" => "October",
		"November" => "November",
		"December" => "December"	
		);
		return $months;
	}
	
	# function to generate months
	function getAllMonthsAsNumbers() {
		$months = array(
		"1" => "January",		
		"2" => "February",
		"3" => "March",
		"4" => "April",
		"5" => "May",		
		"6" => "June",
		"7" => "July",
		"8" => "August",
		"9" => "September",		
		"10" => "October",
		"11" => "November",
		"12" => "December"	
		);
		return $months;
	}
	
	function getMonthName($number) {
		$months = getAllMonthsAsNumbers();
		return $months[$number];
	}
	
	# function to generate years
	function getAllYearsForMonthlyTimeReport() {				
		$aconfig = Zend_Registry::get("config"); 
		$years = array(); 
		$start_year = date("Y") - intval($aconfig->monthlytimesheetreport->previousyearcount);
		
		$end_year = date("Y") + intval($aconfig->monthlytimesheetreport->nextyearcount);
		for($i = $start_year; $i <= $end_year; $i++) {
			$years[$i] = $i; 
		}		
		//return the years in descending order from the last year to the first and add true to maintain the array keys
		return array_reverse($years, true);
	}
	
	# get the first day of a month
	function getFirstDayOfMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month,1,$year));
	}
	# get the last day of a month
	function getLastDayOfMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month+1,0,$year));
	}
	# get the first day of current month
	function getFirstDayOfNextMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month,2,$year));
	}
	# get the last day of the next month
	function getLastDayOfNextMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month+2,0,$year));
	}
	# get the first day of last month
	function getFirstDayOfLastMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month,-1,$year));
	}
	# get the last day of the last month
	function getLastDayOfLastMonth($month,$year) {
		return date("Y-m-d", mktime(0,0,0, $month-1,0,$year));
	}
	
	/**
	 * Return an array containing the 2 digit US state codes and names of the states
	 *
	 * @return Array Containing 2 digit state codes as the key, and the name of a US state as the value
	 */
	function getStates() {
		$state_list = array('AL'=>"Alabama",  
			'AK'=>"Alaska",  
			'AZ'=>"Arizona",  
			'AR'=>"Arkansas",  
			'CA'=>"California",  
			'CO'=>"Colorado",  
			'CT'=>"Connecticut",  
			'DE'=>"Delaware",  
			'DC'=>"District Of Columbia",  
			'FL'=>"Florida",  
			'GA'=>"Georgia",  
			'HI'=>"Hawaii",  
			'ID'=>"Idaho",  
			'IL'=>"Illinois",  
			'IN'=>"Indiana",  
			'IA'=>"Iowa",  
			'KS'=>"Kansas",  
			'KY'=>"Kentucky",  
			'LA'=>"Louisiana",  
			'ME'=>"Maine",  
			'MD'=>"Maryland",  
			'MA'=>"Massachusetts",  
			'MI'=>"Michigan",  
			'MN'=>"Minnesota",  
			'MS'=>"Mississippi",  
			'MO'=>"Missouri",  
			'MT'=>"Montana",
			'NE'=>"Nebraska",
			'NV'=>"Nevada",
			'NH'=>"New Hampshire",
			'NJ'=>"New Jersey",
			'NM'=>"New Mexico",
			'NY'=>"New York",
			'NC'=>"North Carolina",
			'ND'=>"North Dakota",
			'OH'=>"Ohio",  
			'OK'=>"Oklahoma",  
			'OR'=>"Oregon",  
			'PA'=>"Pennsylvania",  
			'RI'=>"Rhode Island",  
			'SC'=>"South Carolina",  
			'SD'=>"South Dakota",
			'TN'=>"Tennessee",  
			'TX'=>"Texas",  
			'UT'=>"Utah",  
			'VT'=>"Vermont",  
			'VA'=>"Virginia",  
			'WA'=>"Washington",  
			'WV'=>"West Virginia",  
			'WI'=>"Wisconsin",  
			'WY'=>"Wyoming");
		
		return $state_list; 
	}
	/**
	 * Return full name of a US state
	 *
	 * @return String Name of state
	 */
	function getFullStateName($state) {
		$statesarray = getStates();
		return $statesarray[$state];
	}
	/**
	 * Return an array of the week ending dates to be used on the timesheet page, based on the current date
	 * 
	 * @return Array of dates
	 */
	# function to generate week ending dates
	function getWeekendingDates() {				
		$aconfig = Zend_Registry::get("config"); 
		$weekendingdates = array();
		$startdate = new DateTime();
		$enddate = new DateTime();
		$startdate->modify("friday ".$aconfig->dateandtime->mindate." weeks ago");
		for($i=0; $i <= $aconfig->dateandtime->mindate; $i++) {
			$startdate->modify("+1 week");
			$weekendingdates[$startdate->format($aconfig->dateandtime->mediumformat)] = $startdate->format($aconfig->dateandtime->mediumformat); 
		}	
		//return the week ending dates in descending order from the last year to the first and add true to maintain the array keys
		return array_reverse($weekendingdates, true);
	}
	/**
	 * Generate an array containing a list of active projects assigned to the current user 
	 * 
	 * @param Integer $userid
	 */
	function getActiveProjectAssignments($userid) {
		// TODO: Add a restriction for the end date of the project assignment 
		$valuesquery = "SELECT c.id as optionvalue, c.name as optiontext FROM client c INNER JOIN projectassignment pa ON c.id = pa.clientid WHERE pa.employeeid  = '".$userid."' AND (TO_DAYS(NOW()) BETWEEN TO_DAYS(pa.startdate) AND TO_DAYS(pa.enddate))";
		return getOptionValuesFromDatabaseQuery($valuesquery);
	}
	# function to fetch all users
	function getAllSystemUsers() {
		$session = SessionWrapper::getInstance();
		$userid = $session->getVar('userid');
		$valuesquery = "SELECT u.id AS optionvalue, concat(u.firstname,' ',u.lastname) as optiontext FROM useraccount as u WHERE u.id <> '' ORDER BY optiontext";
		// debugMessage($valuesquery);
		return getOptionValuesFromDatabaseQuery($valuesquery);
	}
	# function to fetch all users
	function getAllSystemUsersWithFlag($ignoreid) {
		$valuesquery = "SELECT u.id AS optionvalue, concat(u.firstname,' ',u.lastname) as optiontext FROM useraccount as u WHERE u.id <> '".$ignoreid."' ORDER BY optiontext";
		// debugMessage($valuesquery);
		return getOptionValuesFromDatabaseQuery($valuesquery);
	}
	# function to fetch all userids as an array
	function getUserIDArray() {
		$users = getAllSystemUsers();
		$user_array = array();
		foreach ($users as $key => $value){
			$user_array[] = $key;
		}
		return $user_array;
	}
	
	/**
	 * Generate an array containing a list of projects assigned to the current user 
	 * 
	 * @param Integer $userid
	 */
	function getProjectAssignments($userid) {
		$valuesquery = "SELECT p.id as optionvalue, p.title as optiontext FROM project p INNER JOIN projectassignment pa ON p.id = pa.projectid WHERE pa.employeeid  = '".$userid."'";
		return getOptionValuesFromDatabaseQuery($valuesquery);
	}
	/**
	 * Generate an array containing a list of clients and projects assigned to the current user 
	 * 
	 * @param Integer $userid
	 */
	function getClientsForProjectAssignments($userid) {
		$valuesquery = "SELECT c.id as optionvalue, c.name as optiontext FROM client c INNER JOIN projectassignment pa ON c.id = pa.clientid WHERE pa.employeeid  = '".$userid."'";
		return getOptionValuesFromDatabaseQuery($valuesquery);
	}
	/**
	 * Determine the absolute path of the current folder
	 */
	function generateFolderBreadcrumb($currentfolderid) {
			$conn = Doctrine_Manager::connection();
			$link = "";
			
			$folder = new Folder();
			$folder->populate($currentfolderid);
			// if browing root 
			if(isEmptyString($folder->getParentID())){
				$view = new Zend_View();
				return '<a href="'.$view->baseUrl('corporatefile/list/parentid/'.$folder->getID()).'" title="Browse Folder">'.$folder->getFolderName().' ></a>';
			}
			$pathlist = str_replace('/', ',', $folder->getPath());
			
			$concatquery = "SELECT group_concat(id separator '/') as ids, group_concat(name separator '/') as names FROM folder WHERE id IN($pathlist)";
			$result = $conn->fetchRow($concatquery);
			$keys = explode('/', $result['ids']);
			$values = explode('/', $result['names']);
			// debugMessage($keys); debugMessage($values);
			$foldersarray = array_combine($keys, $values);
			// sort the keys
			ksort($foldersarray);
			$folderswithlinks = array();
			$foldersarray['1'] = 'Home';
			foreach ($foldersarray as $key => $value){
				$view = new Zend_View();
				$folderswithlinks[$key] = '<a href="'.$view->baseUrl('corporatefile/list/parentid/'.$key).'" title="Browse Folder">'.$value.'</a>';
			}
			// debugMessage($foldersarray);
			return implode(' > ', $folderswithlinks);
	}
	/**
	 * Determine the absolute path of the current folder
	 */
	function getAbsoluteFilePath($fileid) {
		$conn = Doctrine_Manager::connection();
		$link = "";
		
		$file = new CorporateFile();
		$file->populate($fileid);
		if(isEmptyString($file->getFolder()->getPath())){
			return $file->getFolder()->getName();
		} 
		
		$pathlist = str_replace('/', ',', $file->getFolder()->getPath());
		$concatquery = "SELECT group_concat(id separator '/') as ids, group_concat(name separator '/') as names FROM folder WHERE id IN($pathlist)";
		$result = $conn->fetchRow($concatquery);
		$keys = explode('/', $result['ids']);
		$values = explode('/', $result['names']);

		// debugMessage($keys); debugMessage($values);
		$foldersarray = array_combine($keys, $values);
		// sort the keys
		ksort($foldersarray);
		// debugMessage($foldersarray);
		return implode(DIRECTORY_SEPARATOR, $foldersarray);
	}
	/**
	 * Determine the absolute path of the current folder
	 */
	function getAbsoluteFolderPath($folderid) {
		$conn = Doctrine_Manager::connection();
		$link = "";
		
		$folder = new Folder();
		$folder->populate($folderid);
		if(isEmptyString($folder->getPath())){
			return $folder->getName();
		} 
		
		$pathlist = str_replace('/', ',', $folder->getPath());
		$concatquery = "SELECT group_concat(id separator '/') as ids, group_concat(name separator '/') as names FROM folder WHERE id IN($pathlist)";
		$result = $conn->fetchRow($concatquery);
		$keys = explode('/', $result['ids']);
		$values = explode('/', $result['names']);

		// debugMessage($keys); debugMessage($values);
		$foldersarray = array_combine($keys, $values);
		// sort the keys
		ksort($foldersarray);
		// debugMessage($foldersarray);
		return implode(DIRECTORY_SEPARATOR, $foldersarray);
	}
	/**
	 * Determine the relative path of the current folder
	 */
	function getRelativeFilePath($fileid) {
		$conn = Doctrine_Manager::connection();
		$link = "";
		
		$file = new CorporateFile();
		$file->populate($fileid);
		if(isEmptyString($file->getFolder()->getPath())){
			return $file->getFolder()->getName();
		} 
		
		$pathlist = str_replace('/', ',', $file->getFolder()->getPath());
		$concatquery = "SELECT group_concat(id separator '/') as ids, group_concat(name separator '/') as names FROM folder WHERE id IN($pathlist)";
		$result = $conn->fetchRow($concatquery);
		$keys = explode('/', $result['ids']);
		$values = explode('/', $result['names']);

		// debugMessage($keys); debugMessage($values);
		$foldersarray = array_combine($keys, $values);
		// sort the keys
		ksort($foldersarray);
		// debugMessage($foldersarray);
		return implode('/', $foldersarray);
	}
	/**
	 * Determine the relative path of the current folder
	 */
	function getRelativeFolderPath($folderid) {
		$conn = Doctrine_Manager::connection();
		$link = "";
		
		$folder = new Folder();
		$folder->populate($folderid);
		if(isEmptyString($folder->getPath())){
			return $folder->getName();
		} 
		
		$pathlist = str_replace('/', ',', $folder->getPath());
		$concatquery = "SELECT group_concat(id separator '/') as ids, group_concat(name separator '/') as names FROM folder WHERE id IN($pathlist)";
		$result = $conn->fetchRow($concatquery);
		$keys = explode('/', $result['ids']);
		$values = explode('/', $result['names']);

		// debugMessage($keys); debugMessage($values);
		$foldersarray = array_combine($keys, $values);
		// sort the keys
		ksort($foldersarray);
		// debugMessage($foldersarray);
		return implode('/', $foldersarray);
	}
?>