<?php

class Pagination {
	
	/**
	 * The columns that are searcheable 
	 * 
	 * @var Array
	 */
	protected $_searchcolumns = array();
	protected $_filtercolumns = array(); 
	
	protected $_defaultsortby = ""; 
	protected $_sortby = ""; 
	protected $_defaultsortorder = "ASC"; 
	protected $_sortorder = ""; 
	protected $_newsortorder = ""; 
	protected $_datefiltercolumns = array(); 
	
	
	protected $_dataarray = array(); 
	
	protected $_searchterm = ""; 
	
	protected $_paginator;

	protected $_currentpagenumber = 1; 
	protected $_itemcountperpage = 10;
	
	protected $_itemcount; 
	
	/**
	 * The Zend_View instance 
	 */
	protected $_view; 
	
	/**
	 * The start and end dates for a date filter
	 * 
	 * @var string 
	 */
	protected $_startdate; 
	protected $_enddate; 
	protected $_startandenddatefiltercolumn;
	
	/*public function __construct($adapter = null) {
		if (is_null($adapter)) {
			parent::__construct(new Zend_Paginator_Adapter_Null());
		} else {
			parent::__construct($adapter); 
		}
		
	}*/
	/**
	 * Set the columns to be used for filtering 
	 *
	 * @param Array $cols
	 */
	function setFilterColumns($cols) {
		$this->_filtercolumns = $cols; 
	}
	/**
	 * Set the columns to be used for searchin 
	 *
	 * @param Array $cols
	 */
	function setSearchColumns($cols) {
		$this->_searchcolumns = $cols; 
	}
	
	/**
	 * @return the default sort column
	 */
	public function getDefaultSortBy() {
		return $this->_defaultsortby;
	}
	
	/**
	 * @param String $_defaultsortby
	 */
	public function setDefaultSortBy($_defaultsortby) {
		$this->_defaultsortby = $_defaultsortby;
	}
	
	/**
	 * @return String 
	 */
	public function getDefaultSortOrder() {
		return $this->_defaultsortorder;
	}
	
	/**
	 * @param String $_defaultsortorder
	 */
	public function setDefaultSortOrder($_defaultsortorder) {
		$this->_defaultsortorder = $_defaultsortorder;
	}
	
	/**
	 * @return String 
	 */
	public function getSortBy() {
		return $this->_sortby;
	}
	
	/**
	 * @param String $_sortby
	 */
	public function setSortBy($_sortby) {
		$this->_sortby = $_sortby;
	}
	
	/**
	 * @return String 
	 */
	public function getSortOrder() {
		return $this->_sortorder;
	}
	
	/**
	 * @param String $_sortorder
	 */
	public function setSortOrder($_sortorder) {
		$this->_sortorder = $_sortorder;
	}
/**
	 * @return String 
	 */
	public function getNewSortOrder() {
		return $this->_newsortorder;
	}
	
	/**
	 * @param String $_sortorder
	 */
	public function setNewSortOrder($_sortorder) {
		$this->_newsortorder = $_sortorder;
	}
	
	public function setStartandenddatefiltercolumn($col) {
		$this->_startandenddatefiltercolumn = $col;
	}

	function processPost($formvalues) {
		$this->_dataarray = $formvalues; 
		
		if (!isArrayKeyAnEmptyString('sortby', $formvalues)) {
			$this->setSortBy($formvalues['sortby']);
		}
		if (!isArrayKeyAnEmptyString('searchterm', $formvalues)) {
			$this->setSearchTerm($formvalues['searchterm']);
		}
		
		if (!isArrayKeyAnEmptyString('sortorder', $formvalues)) {
			$this->setSortOrder($formvalues['sortorder']);
		}
		if (!isArrayKeyAnEmptyString('startdate', $formvalues)) {
			$this->_startdate = $formvalues['startdate'];
		}
		if (!isArrayKeyAnEmptyString('enddate', $formvalues)) {
			$this->_enddate = $formvalues['enddate'];
		}
		if (!isArrayKeyAnEmptyString('page', $formvalues)) {
			$this->_currentpagenumber = $formvalues['page'];
		}
		if (!isArrayKeyAnEmptyString('itemcountperpage', $formvalues)) {
			$this->_itemcountperpage = $formvalues['itemcountperpage'];
		}
		
		// synchronize the default sortby and sortorder with the actual values
		$this->setDefaultSortParameters();
		return true; 
	}
	
	/**
	 * Set the default sort parameters and the new sort orders which will be used in the page headers
	 *
	 */
	function setDefaultSortParameters() {
		if (isEmptyString($this->getSortBy())) {
			$this->setSortBy($this->getDefaultSortBy());
		}
		
		if (isEmptyString($this->getSortOrder())) {
			$this->setSortOrder($this->getDefaultSortOrder());
		}
		
		// set the new sort order, which will be used to generate the sort links
		if ($this->getSortOrder() == "ASC") {
			$this->setNewSortOrder("DESC");
		} else {
			$this->setNewSortOrder("ASC");
		}
	}
	
	function getSearchColumns() {
		return $this->_searchcolumns; 
	}
	
	function getFilterColumns() {
		return $this->_filtercolumns; 
	}
	
	/**
	 * SQL Limit clause for MySQL from the collection items
	 *
	 * @return String The SQL Limit clause for the items in the collection
	 */
	function getSQLLimit() {
		if (!is_numeric($this->getItemCountPerPage())) {
			return ''; 
		}
		return ' LIMIT ' . ($this->getCurrentPageNumber() * $this->getItemCountPerPage() - $this->getItemCountPerPage()) . ', ' . $this->getItemCountPerPage();
	}
	
	/**
	 * Set the count of the number of items to the number of rows returned by the specified query
	 * If there are errors executing the query, the item count will be set to zero
	 * 
	 * @param String $query The SQL query
	 * 
	 */
	function setItemCountFromSQLQuery($query) {
		$conn = Doctrine_Manager::connection(); 
		$result = $conn->fetchAll($query);
		if (! $result) {
			$this->setItemCount(0);
		} else {
			$this->setItemCount(count($result));
		}
	}
	/**
	 * Generate SQL that searches for the search parameter in columns specified in the array $searchcolumns
	 * The function loops through the array and generates code for each column e.g for status, the code would be
	 * " 0R status LIKE '%Liz'%" where the search parameter is 'Liz'
	 * 
	 * If the search term is empty, it returns an empty string
	 * 
	 * @return String 
	 */
	function getSearchAndFilterSQL() {
		$searchcolumns = $this->getSearchColumns();
		$searchsql = array();
		$searchterm = $this->getSearchTerm();
		if (isEmptyString($this->getSearchTerm())) {
			return $this->getFilterSQL();
		}
		foreach ($searchcolumns as $column ) {
			$searchsql[] = $column . " LIKE '%" .$searchterm. "%' ";
		}
		return " AND (" . implode("  OR ", $searchsql). ") ".$this->getFilterSQL();
	}
	/**
	 * Generate SQL for the sortorder and fields to be sorted by
	 * 
	 * @return String The SQL having the field to be used for sortby and the sort order
	 */
	function getSortSQL() {
		$sortsql = " ORDER BY " . $this->getSortBy() . " " . $this->getSortOrder() . " ";
		return $sortsql;
	}
	
	function getDataArray() {
		return $this->_dataarray; 
	}
	
	function getStartDate() {
		return $this->_startdate; 
	}
	function getEndDate() {
		return $this->_enddate; 
	}
	
	function getStartAndEndDateFilterColumn(){
		return $this->_startandenddatefiltercolumn; 
	}
	/**
	 * Build a filter for a start and end date fields. No filter is returned if the startdate, enddate or startandenddatefiltercolumn fields are not specified, or if the startdate is after the enddate
	 *
	 * @return String WHERE condition specifiying that the startandenddate filter column is between the start and end dates
	 */
	function getStartAndEndDateFilter() {
		// all three parameters have to be defined
		if (isEmptyString($this->getStartDate()) || isEmptyString($this->getEndDate()) || isEmptyString($this->getStartAndEndDateFilterColumn())) {
			return "";
		}
		// the start date must be after the end date
		if (strtotime($this->getStartDate()) > strtotime($this->getEndDate())) {
			return ""; 
		}
		return " AND (TO_DAYS(".$this->getStartAndEndDateFilterColumn().") BETWEEN TO_DAYS('".changeDateFromPageToMySQLFormat($this->getStartDate())."') AND TO_DAYS('".changeDateFromPageToMySQLFormat($this->getEndDate())."')) ";
	}
	
	/**
	 * Return an SQL statement for the Filter Columns
	 * 
	 * @return String The Filter SQL statement
	 */
	function getFilterSQL() {
		$filtercolumns = $this->getFilterColumns(); 
		$datefiltercolumns = $this->getDateFilterColumns(); 
		$request_data = $this->getDataArray(); 
		// the default filter is the start and end date filter
		$filtersql = $this->getStartAndEndDateFilter();
		// loop through each of the filters and only add those which have data
		if (count($filtercolumns) == 0) {
			return $filtersql;
		}
		foreach ( $filtercolumns as $columnname ) {
			$request_variable_name = str_replace(".",HTML_TABLE_COLUMN_SEPARATOR, $columnname); 
			if (!isArrayKeyAnEmptyString($request_variable_name, $request_data)) {
				// the value to be applied as a filter
				$filtervalue = $request_data[$request_variable_name]; 
				// if the value is a date change it into MYSQL format
				// change the name of the $value to remove any separators for HTML
				if (in_array($columnname, $datefiltercolumns)) {
					$filtervalue = changeDateFromPageToMySQLFormat($filtervalue); 
				}
				$filtersql .= " AND " . $columnname . " = '" .$filtervalue . "'";
			}
		}
		return $filtersql;
	}
	
	function getDateFilterColumns() {
		return $this->_datefiltercolumns; 
	}
	function setDateFilterColumns($newdatefiltercolumns) {
		$this->_datefiltercolumns = $newdatefiltercolumns; 
	}
	
/**
     * Returns the total number of items available.
     *
     * @return Integer
     */
    public function getItemCount() {
        return $this->_itemcount; 
    }
    public function setItemCount($count) {
    	$this->_itemcount = $count; 
    }
    
    /**
     * The text from the search 
     *
     * @return String 
     */
    function getSearchTerm() {
    	return $this->_searchterm; 
    }
    function setSearchTerm($term) {
    	$this->_searchterm = $term;
    }
    
	
/**
	 * Generate a sort by link for a column
	 *
	 * @param String $column The name of the column, $title The title of the column to be displayed, 
	 * 
	 * @return String The column title having the sort link href concactenated by the sort image
	 */
	function getSortLinkForColumn($column, $title) {
		$options = $this->_dataarray; 
		$options["sortby"] =  $column; 
		// $options['module'] = $this->get
		
		// check if the column is the same as the current sorting column
		if (strtolower($column) == strtolower($this->getSortBy()) ) {
			// use the new sortorder
			$options['sortorder'] =  $this->getNewSortOrder();
		} else {
			// use the current sort order
			$options['sortorder'] =  $this->getSortOrder();
		}
		// make sure to put back the ? between the link href and the query string which was removed when using the query string functionality
		return '<a href="'.$this->getView()->url($options).'" title="Click to sort by '.$title.'">'.$title.$this->getSortArrow($column).'</a>';
		return $title; 
	}
	
	/**
	 * Return the sort arrow for the column
	 *
	 * @param String $columnname The name of the column
	 * @return String The HTML code for the image for the arrow for the column
	 */
	function getSortArrow($columnname) {
		$thesortorder = "";
		// check if the column is the one being sorted
		if (strtolower($this->getSortBy()) == strtolower(trim($columnname))) {
			$thesortorder = $this->getSortOrder();
		}
		if ($thesortorder == "ASC") {
			return '<img src="'.$this->getView()->baseUrl('images/arrowup.png').'" class="sortimage">';
		} else if ($thesortorder == "DESC") {
			return '<img src="'.$this->getView()->baseUrl('images/arrowdown.png').'" class="sortimage">';
		}
		return "";
	}
	
	function getCurrentPageNumber() {
		return $this->_currentpagenumber;
	}
	
	function getItemCountPerPage() {
		return $this->_itemcountperpage; 
	}
	function setItemCountPerPage($count) {
		$this->_itemcountperpage = $count; 
	}
	
	function getView() {
		return $this->_view; 
	}
	
	function setView($view) {
		$this->_view = $view; 
	}
	
	function getPaginationLinks() {
		if (!is_numeric($this->getItemCountPerPage())) {
			return ''; 
		}
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($this->getItemCount())); 
		$paginator->setCurrentPageNumber($this->getCurrentPageNumber());
		$paginator->setItemCountPerPage($this->getItemCountPerPage()); 
		return $this->getView()->paginationControl($paginator); 

	}
	
/**
	 * Return the starting item number for the current page.
	 *
	 * @return Integer The index of the first item on the current page
	 */
	function getPageStartIndex(){
		return Zend_Locale_Format::toNumber((($this->getCurrentPageNumber() * $this->getItemCountPerPage()) - $this->getItemCountPerPage()) + 1);
	}
	/**
	 * Return the ending item number for the current page.
	 *
	 * @return Integer The index of the last itme on the page
	 */
	function getPageEndIndex() {
		return Zend_Locale_Format::toNumber(min($this->getCurrentPageNumber() * $this->getItemCountPerPage(), $this->getItemCount())) ;
	}
	/**
	 * Return the text for the counter of a list of the form
	 * Viewing (index of first item on page) - (index of last item on page) of (total number of items in the list)
	 * for example Viewing 1 - 5 of 10
	 * 
	 * @return String 
	 *
	 */
	function getItemCounterText() {
		if (!is_numeric($this->getItemCountPerPage())) {
			return  "Viewing ".$this->getPageStartIndex()." - ".$this->getItemCount(). " of ".$this->getItemCount();
		}
		return "Viewing ".$this->getPageStartIndex()." - ".$this->getPageEndIndex(). " of ".$this->getItemCount(); 
	}
	/*
	 * Return a Pagination Alphabet string. for example A to Z
	 */
	function getAlphabetString(){
		$alphabet_link_array = array();
		
		$alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		foreach ($alphabet as $letter) {
			$alphabet_link_array[] = '<a href="#" title="">'.$letter.'</a>';
		} 
		return implode('&nbsp;|&nbsp;',$alphabet_link_array);
	}
	
}

?>