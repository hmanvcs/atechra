<?php
require_once 'commonfunctions.php';

/**
 * Simple excel generating from PHP5
 * 
 * This is one of my utility-classes.
 * 
 * The MIT License
 * 
 * Copyright (c) 2007 Oliver Schwarz
 * 
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package Utilities
 * @author Oliver Schwarz <oliver.schwarz@gmail.com>
 * @version 1.0
 */

/**
 * Generating excel documents on-the-fly from PHP5
 * 
 * Uses the excel XML-specification to generate a native
 * XML document, readable/processable by excel.
 * 
 * @package Utilities
 * @subpackage Excel
 * @author Oliver Schwarz <oliver.schwarz@vaicon.de>
 * @version 1.0
 *
  * @todo Add error handling (array corruption etc.)
 * @todo Write a wrapper method to do everything on-the-fly
 */

class ExcelXML {

    /**
     * Header of excel document (prepended to the rows)
     * 
     * Copied from the excel xml-specs.
     * 
     * @access private
     * @var string
     */
    private $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?\>
<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"
 xmlns:x=\"urn:schemas-microsoft-com:office:excel\"
 xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"
 xmlns:html=\"http://www.w3.org/TR/REC-html40\">";
    
    /**
     * The first row contains the column headings
     *
     * @access protected
     * @var String
     */
    protected $headerrow = "";
     /**
     * The styles for the spreadsheet
     *
     * @var String
     */
    private $styles = '
    <Styles>
    <Style ss:ID="Default" ss:Name="Normal">
      <Alignment ss:Vertical="Top"/>
      <Borders/>
      <Font/>
      <Interior/>
      <NumberFormat/>
      <Protection/>
    </Style>
    <Style ss:ID="sheaderrow">
      <Font ss:Color="#0B55C4" ss:Bold="1"/>
    </Style>
    <Style ss:ID="snumber">
      <NumberFormat ss:Format="0.00"/>
    </Style>
    <Style ss:ID="scurrency">
      <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
    </Style>
    </Styles>'; 

    /**
     * Footer of excel document (appended to the rows)
     * 
     * Copied from the excel xml-specs.
     * 
     * @access private
     * @var string
     */
    private $footer = "</Workbook>";

    /**
     * Document lines (rows in an array)
     * 
     * @access protected
     * @var array
     */
    protected $rows = array ();

    /**
     * Worksheet title
     *
     * Contains the title of a single worksheet
     *
     * @access private 
     * @var string
     */
    private $worksheet_title = "Table1";

	# constructor function	
	function __construct($subclass_attributes = array()){
		$attributes = array(
			"startingcolumn"=> 0
		);	
	
		# merge the attibutes of the parent and the subclass
      $newattributes = array_merge($attributes, $subclass_attributes);
		 
      # load the attributes by calling the parent constructor. This is to prevent changing
      # attributes at run time
      //parent::__construct($newattributes);
	}
	
/**
	 * Execute getters and setters on the instance if they do not exist
	 *
	 * @param String $method The method to execute
	 * @param Array $args The method arguments
	 * @return unknown
	 */
	function __call($method, $args){
		$attribute = strtolower(substr($method, 3, 1).substr($method, 4));
		$prefix = substr($method, 0, 3);

		# echo "<br><br>The attribute is ".$attribute;
		switch ($prefix)  {
			 case "get":
				# return the value of the attribute
				return $this->vars[$attribute];
				break;
			 case "set": 
			 	# set the value of the attribute
            	$this->vars[$attribute] = $args[0];
            break; 
         default:
         	# do nothing
            break;
		}
	}
	
	/**
	 * Set the number of columns to ignore at the beginning of the data to be processed
	 *
	 * @param Integer $newcolumncheck The number of columns to ignore at the beginning of the data
	 */

	function setColumnCheck($newcolumncheck) {
		$this->vars['columncheck'] = (int) abs($newcolumncheck);
	}
    /**
     * Add a single row to the $document string
     * 
     * @access private
     * @param array 1-dimensional array
     * @todo Row-creation should be done by $this->addArray
     */
    function addRow ($row_data) {
        // initialize all cells for this row
        $cells = "";

        $startingcolumn = $this->getStartingColumn(); 
        $array_length = count($row_data); 
        // foreach key -> write value into cells
        $counter = 0; 
        foreach ($row_data as $key => $value) { 
        		if ($counter < $startingcolumn) {
        			// do nothing, ignore this column's data
        		} else {
        			$cells .= "<Cell><Data ss:Type=\"String\">" . utf8_encode($value) . "</Data></Cell>\n"; 
        		} 
        		// move the counter to the next row
        		$counter++;
        } 
        // transform $cells content into one row
        $this->rows[] = "<Row>\n" . $cells . "</Row>\n";

    }

    /**
     * Add an array to the document
     * 
     * This should be the only method needed to generate an excel
     * document.
     * 
     * @access public
     * @param array 2-dimensional array
     * @todo Can be transfered to __construct() later on
     */
    public function addArray ($array) {
        // run through the array and add them into rows
        foreach ($array as $k => $v):
            $this->addRow ($v);
        endforeach;

    }

    /**
     * Set the worksheet title
     * 
     * Checks the string for not allowed characters (:\/?*),
     * cuts it to maximum 31 characters and set the title. Damn
     * why are not-allowed chars nowhere to be found? Windows
     * help's no help...
     *
     * @access public
     * @param string $title Designed title
     */
    public function setWorksheetTitle ($title) {
			if (isEmptyString($title)) {
				$title = "Sheet 1"; 
			}

        // strip out special chars first
        $title = preg_replace ("/[\\\|:|\/|\?|\*|\[|\]]/", "", $title);

        // now cut it to the allowed length
        $title = substr ($title, 0, 31);

        // set title
        $this->worksheet_title = $title;

    }

    /**
     * Generate the excel file
     * 
     * Finally generates the excel file and uses the header() function
     * to deliver it to the browser.
     * 
     * @access public
     * @return String The XML for the contents in MS Excel
     */
    function generateXML ()  {
    	$xml_string = "";
        // print out document to the browser
        // need to use stripslashes for the damn ">"
        $xml_string.= stripslashes ($this->header);
        // add the styles
        $xml_string.= $this->styles;
        $xml_string.= "\n<Worksheet ss:Name=\"" . $this->worksheet_title . "\">\n<Table>\n";
        $xml_string.= "<Column ss:Width=\"220\" />\n";
        // add the header row
        $xml_string.= "\n".$this->headerrow;
        // add the data rows
        $xml_string.= implode ("\n", $this->rows);
        $xml_string.= "</Table>\n</Worksheet>\n";
       $xml_string.= $this->footer;
       
       return $xml_string;

    }
    /**
     * Generate the Excel XML from an SQL query
     *
     * @param String $query The query for the results to be returned
     * @return String The XML string for the query
     */
    function generateXMLFromQuery($query) {
    	$conn = Doctrine_Manager::connection(); 
    	$result = $conn->fetchAll($query); 
    	if (!$result) {
    		// an error occured
    		return false; 
    	}
    	$this->generateHeaderRowXML($result);
    	
    	// browse through the results and add them to the data array
    	foreach ($result as $line) {
    		$this->addRow($line);
    	}
    	return $this->generateXML();
    }
    
    /**
     * Generate the XML string for the header row and add it to the headerrow property
     *
     * @param mysql_result $result The resultset from executing the query
     */
    function generateHeaderRowXML($result) {
    	$row = $result[0]; 
    	$column_names = array_keys($row); 
    	// add the header row
    	$columnCount = count($column_names);
    	// start from the specified column
    	$i = $this->getStartingColumn(); 
    	# generate the row for the column headers
    	$this->headerrow = "<Row>\n";
    	while ( $i < $columnCount ) {
    		$this->headerrow .= '<Cell  ss:StyleID="sheaderrow"><Data ss:Type="String">'.utf8_encode($column_names[$i]).'</Data></Cell>\n';  
    		$i ++;
    	}
    	// close the header row
    	$this->headerrow .= "</Row>\n";
    	}
    }
?>