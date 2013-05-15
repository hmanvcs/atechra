<?php
/**
 * PHP Calendar Class w/Google v1.2.1
 *
 * An easy to use and robust calendar class.
 * Features:
 * - Output full, mini, event list & mini event list
 * - Auto generated color scheme by passing one color or optionally set all colors individually
 * - Supports unlimited events
 * - Display calendar with as little as 2 lines of code
 * - Uses no images, all css
 * - Include one class file and you are ready to go
 * - Display powered by jquery
 * - lots of display options
 * - Start from specified day
 * - Works in IE 6+, Firefox, Safari, Opera and Chrome
 * - Display events from google calendars
 *
 * @author		Dustin J. Czysz (Klovera, Inc.)
 * @copyright	Copyright (c) 2010 - 2011, Klovera, Inc.
 * @license		http://klovera.com/php-calendar-class 
 * @link		http://klovera.com/php-calendar-class 
 * 
 */

// ---------------------------------------------------------------------------------------

/*
|--------------------------------------------------------------------------
| USEAGE
|--------------------------------------------------------------------------
|
|	1. Include the calendar.class.php file (E.g. include('calendar.class.php'); ) 
|	2. Creating a calendar:
|		
|		Basic Implementation:
|			$cal = new CALENDAR();
|			echo $cal->showcal();
|
|		Set calendar display type: (full,mini,list,mini-list)
|			$cal = new CALENDAR(); // equals 'full' as default
|			$cal = new CALENDAR('full');
|			$cal = new CALENDAR('mini');
|			$cal = new CALENDAR('list');
|			$cal = new CALENDAR('min-list');
|
|		Set start date:
|			$date = 2011-4; // 4 digit year
|			$cal = new CALENDAR('', $date);
|			echo $cal->showcal();
|			
|		Add events:
|		
|		Settings:
|
|		Suggested event/calendar colors: #D6FFDB,#FFF6D6,#D8E5F9,#FFD6D6
|
|
|
*/

/*
|--------------------------------------------------------------------------
| DEBUG
|--------------------------------------------------------------------------
|
|	Uncomment the following 2 lines to allow error reporting.
|
*/
	//error_reporting(E_ALL ^ E_NOTICE);
	//ini_set('display_errors','1');
	
	$cal_ID = 0; // the ID of the first instance of the calendar, autoincrements if more than one per page

	class Calendar {
	/*
	|--------------------------------------------------------------------------
	| CONFIGURATION
	|--------------------------------------------------------------------------
	|
	|	Default values for each instance. Can be set as defaults here or 
	|	when the calendar is created...
	|
	|	E.g. 
	|	$cal = new CALENDAR();
	|	$cal->jqueryinclude = false; //-- sets the $jqueryinclude value for the $cal instance of the calendar
	|	echo $cal->showcal();
	|
	*/
	
		var $css = 'default';					// path to css file ('default' = default css, 'disable' = no css)
		var $js = 'default';					// path to js file ('default' = default js, 'disable' = no js)
		var $weekstartday = 0; 					// week start day (0-6 e.g. 0 = Sunday, 1 = Monday, etc.)
		var $jqueryinclude = false;				// includes jquery from google (true,false)
		var $monthselector = true;				// month/year select box (true=show selector,false=show month name)
		var $yearoffset = 5;					// monthselector range of years (int)
		var $weeknumbers = false;				// adds a column for week numbers (left,right,false)
		var $weeknumrotate = true;				// rotate weeknumbers 90 degrees *currently only firefox is supported* (true,false)
		var $weeknames = 2;						// controls how weekdays are displayed. (1=full,2=abbrev,3=single char)
		var $monthnames = 1;					// controls how months are displayed. (1=full,2=abbrev)
		var $basecolor = '7D9AC0';				// base color for color scheme (hex)
		var $minicalwidth = '240px';			// width of mini calendar (any css measurement e.g. px,%,em,etc.)
		var $minilistwidth = '200px';			// width of minilist (any css measurement e.g. px,%,em,etc.)
		var $minilinkbase = '';					// base url for links on mini calendar (blank=disabled)
		var $tipwidth = '150px';				// width of the minical hover tip (any css measurement e.g. px,%,em,etc.)
		var $basesize = '10px';					// all sizes scale on this value (any css measurement e.g. px,%,em,etc.)
		var $eventlistbg = 'ffffff';			// event list view bg color (hex)
		var $eventemptytext = 'No additional details for this event.';	// default text in event view when details is empty (string)
		var $dateformat = 'F j, Y';				// default date format (passed to php date() function)
		var $timeformat = 'g:ia';				// default time format (passed to php date() function)
		var $font = '"Arial",sans-serif';	// font used to display the calendar (any css supported value)
		var $linktarget = 'parent';				// link target frame or window (e.g. 'parent.frameName'. Use '_blank' for new window/tab)
		var $listlimit = false;					// limit the number of events in list and mini-list (false or int e.g. 10)
		var $listtitle = 'Event List';			// Title shown when displaying full event list
		var $ajax = false;						// Enable/Disable ajax mode 

	//--------------------------------------------------------------------------------------------
	// Weekday names/abbreviations (array must start with Sunday=0)
	//--------------------------------------------------------------------------------------------
		
		var $weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
		var $abbrevweekdays = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
		var $weekdayschar = array("S", "M", "T", "W", "H", "F", "S");

	//--------------------------------------------------------------------------------------------
	// Other vars used. (No need to edit below here)
	//--------------------------------------------------------------------------------------------
		
		var $jd, $year, $month, $day, $displaytype, $numdaysinmonth, $monthstartday, $monthname, 
		$previousmonth, $nextmonth;
		var $linkcolor, $calgradient, $nodatecolor, $nodatetextcolor, 
		$weekdaycolor1, $weekdaycolor2, $currentdaycolor1, $currentdaycolor2, 
		$weekendcolor1, $weekendcolor2, $bordercolor, $datetextcolor, $headingtextcolor, $headingcolor1,
		$headingcolor2, $shadowcolor;
		var $events = array();
		
		// Zend View instance to enable formatting of urls
		var $view; 

	//--------------------------------------------------------------------------------------------
	// Class methods:
	//--------------------------------------------------------------------------------------------
		function Calendar($type='full',$date='') { // types: mini,full,list,mini-list (default=full)
			
			// the view
			$this->view = new Zend_View(); 
			// the type of display to use, trying to avoid using the request since this is problematic for Zend Framework projects
			if (isEmptyString($type)) {
				$this->displaytype = "full";
			} else { 
				$this->displaytype = $type;
			}
			
			// only use the date passed in the constructor
			// default to today
			$ts = mktime(); 
			// check if the date is a timestamp 
			if (trim($date) != "") {
				if (is_numeric($date)) {
					// use the timestamp which may come in handy elsewhere
					$ts = $date; 
				} else {
					// TODO: strtotime only works for American Date formats, so this may need a fix 
					$ts = strtotime($date); 
				}
			} 
			
			$this->month = date("n", $ts); 
			$this->year = date("Y", $ts); 
			$this->day = date("j", $ts); 
	
			// find out the number of days in the month
			$this->numdaysinmonth = cal_days_in_month( CAL_GREGORIAN, $this->month, $this->year );
	
			// create a calendar object
			$this->jd = cal_to_jd( CAL_GREGORIAN, $this->month,date( 1 ), $this->year );
	
			// get the month start day as an int (0 = Sunday, 1 = Monday, etc)
			$this->monthstartday = jddayofweek( $this->jd , 0 );
	
			// get the month as a name
			$this->monthname = jdmonthname( $this->jd, 1 );
			
		}

		// generates all the hex codes for the calendar color scheme
		function color_scheme() {
			
			if(!$this->linkcolor) $this->linkcolor = $this->colourBrightness($this->basecolor,-0.4);
			if(!$this->calgradient) $this->calgradient = $this->colourBrightness($this->basecolor,-0.8);
			if(!$this->bordercolor) $this->bordercolor = $this->colourBrightness($this->basecolor,0.7);
			if(!$this->shadowcolor) $this->shadowcolor = $this->colourBrightness($this->basecolor,-0.01);
			if(!$this->datetextcolor) $this->datetextcolor = $this->colourBrightness($this->basecolor,-0.3);
			if(!$this->headingtextcolor) $this->headingtextcolor = $this->colourBrightness($this->basecolor,0.01);
			if(!$this->nodatecolor) $this->nodatecolor = $this->colourBrightness($this->basecolor,0.1);
			if(!$this->nodatetextcolor) $this->nodatetextcolor = $this->colourBrightness($this->basecolor,0.4);
			if(!$this->weekdaycolor1) $this->weekdaycolor1 = $this->colourBrightness($this->basecolor,0.38);
			if(!$this->weekdaycolor2) $this->weekdaycolor2 = $this->colourBrightness($this->basecolor,0.15);
			if(!$this->currentdaycolor1) $this->currentdaycolor1 = $this->basecolor;
			if(!$this->currentdaycolor2) $this->currentdaycolor2 = $this->colourBrightness($this->basecolor,-0.9);
			if(!$this->weekendcolor1) $this->weekendcolor1 = $this->colourBrightness($this->basecolor,0.5);
			if(!$this->weekendcolor2) $this->weekendcolor2 = $this->colourBrightness($this->basecolor,0.3);
			if(!$this->headingcolor1) $this->headingcolor1 = $this->colourBrightness($this->basecolor,-0.8);
			if(!$this->headingcolor2) $this->headingcolor2 = $this->colourBrightness($this->basecolor,-0.6);
			
		}
		
		// css for all displaytypes
		function set_styles() {
			global $cal_ID;
			
			$html = '';
			
			// generate the color scheme
			$this->color_scheme();

			$cal_ID++; // increment the calendar id so we can assign css/js to the corresponding calendar
			
			if($this->css!='disable' && $this->css!='default') { // load css file defined in properties
				$html = '<link rel="stylesheet" href="'.$this->css.'" type="text/css" media="screen"/>';
			}
			else if($this->css!='disable') { // load default css
				// universal css
				$html .= '
				<style tyle="text/css">
				#cal_'.$cal_ID.'.calendar {
					width:100%;
					color:#'.$this->datetextcolor.';
					border:none;
					background:#'.$this->basecolor.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->basecolor.'", endColorstr="#'.$this->calgradient.'"); 
					background: -webkit-gradient(linear, left top, right bottom, from(#'.$this->basecolor.'), to(#'.$this->calgradient.')); 
					background: -moz-linear-gradient(right,  #'.$this->basecolor.', #'.$this->calgradient.'); 
					margin:5px 0;
					-moz-border-radius: 8px 8px 0px 0px; 
					-webkit-border-radius: 8px 8px 0px 0px; 
					border-radius: 8px 8px 0px 0px;
					font-family: '.$this->font.';
					font-size:'.$this->basesize.';
				}
				#cal_'.$cal_ID.'.calendar a {
					color:#'.$this->linkcolor.';	
				}
				#cal_'.$cal_ID.'.calendar .view-buttons {
					float:right;
					margin:0;
					position:relative;
				}
				#cal_'.$cal_ID.'.calendar .view-buttons a {
					-moz-border-radius: 3px; 
					-webkit-border-radius: 3px; 
					border-radius: 3px;
					display:block;
					float:left;
					margin-left:4px;
					background:'.$this->calgradient.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->basecolor.'", endColorstr="#'.$this->calgradient.'"); 
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->basecolor.'), to(#'.$this->calgradient.')); 
					background: -moz-linear-gradient(top,  #'.$this->basecolor.', #'.$this->calgradient.'); 
					border:1px solid #'.$this->bordercolor.';
					color:#'.$this->headingtextcolor.';
					padding: 6px;
					text-decoration: none;
					-webkit-box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					-moz-box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
				}
				#cal_'.$cal_ID.'.calendar .view-buttons a.selected {
					background:'.$this->basecolor.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->calgradient.'", endColorstr="#'.$this->basecolor.'"); 
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->calgradient.'), to(#'.$this->basecolor.')); 
					background: -moz-linear-gradient(top,  #'.$this->calgradient.', #'.$this->basecolor.'); 
				}
				#cal_'.$cal_ID.'.calendar .calendar-heading {
					text-align:center;
					padding:4px 3px 3px;
					height:3.25em;
					color:#'.$this->headingtextcolor.';
					text-shadow: 0 1px 1px rgba('.$this->hex2RGB($this->shadowcolor,true).',.3);
				}
				#cal_'.$cal_ID.'.full .calendar-heading span {
				}
				#cal_'.$cal_ID.'.calendar .event-metalabel {
					font-weight:bold;	
				}';
				if(in_array($this->displaytype,array('full','mini'))) { // css for full and mini displaytypes 
				$html .= '
				table#cal_'.$cal_ID.'.calendar #month_selector {
					width:auto;	
					float:left;
					padding:0 1em;
					margin:0;
					margin-left: 150px; 
				}
				table#cal_'.$cal_ID.'.calendar select {
					padding:6px;
					width:auto;
					color:#'.$this->headingtextcolor.';
					font-weight:bold;
					-moz-border-radius: 3px; 
					-webkit-border-radius: 3px; 
					border-radius: 3px;
					background:'.$this->calgradient.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->nodatecolor.'", endColorstr="#'.$this->nodatecolor.'");
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->basecolor.'), to(#'.$this->calgradient.'));
					background: -moz-linear-gradient(top,  #'.$this->basecolor.', #'.$this->calgradient.');
					border:1px solid #'.$this->bordercolor.';
					outline:none;
					text-shadow: 0 1px 1px rgba('.$this->hex2RGB($this->shadowcolor,true).',.3);
					-webkit-box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					-moz-box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					box-shadow: 0 1px 2px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
				}
				table#cal_'.$cal_ID.'.calendar select option {
					font-weight:normal;
					background:#'.$this->nodatecolor.';
					text-shadow:none;
					filter:none;
					color:#'.$this->datetextcolor.';
				}
				table#cal_'.$cal_ID.'.calendar td, table#cal_'.$cal_ID.'.calendar th {
					margin:0;
					padding:5px;
					border-right:1px solid #'.$this->bordercolor.';
					border-bottom:1px solid #'.$this->bordercolor.';
				}
				table#cal_'.$cal_ID.'.calendar .normal-day-heading, table#cal_'.$cal_ID.'.calendar .weekend-heading {
					background:#'.$this->headingcolor1.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->headingcolor1.'", endColorstr="#'.$this->headingcolor2.'");
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->headingcolor1.'), to(#'.$this->headingcolor2.'));
					background: -moz-linear-gradient(top,  #'.$this->headingcolor1.', #'.$this->headingcolor2.');
					color:#'.$this->headingtextcolor.';
					font-size:1.4em;
					height:1.2em;
					line-height:1.2em;
					text-align:center;
					text-shadow: 0 1px 1px rgba('.$this->hex2RGB($this->shadowcolor,true).',.3);
				}
				table#cal_'.$cal_ID.'.calendar .day-with-date, table#cal_'.$cal_ID.'.calendar .open-details {
					vertical-align:text-top;
					text-align:left;
					background:#'.$this->weekdaycolor1.';
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->weekdaycolor1.'), to(#'.$this->weekdaycolor2.'));
					background: -moz-linear-gradient(top,  #'.$this->weekdaycolor1.', #'.$this->weekdaycolor2.');
					height:8em;';
				if($this->weeknumbers && $this->displaytype=='mini') { // adjust cell widths to accomodate weeknumbers column
				$html .= 
					'width:12.5%;';
				} else if($this->weeknumbers) {
				$html .= 
					'width:12.2%;';
				} else {
				$html .= 
					'width:14.3%;';
				}
				$html .= '
					font-size:1.4em;
					text-shadow: 0 1px 1px rgba('.$this->hex2RGB($this->shadowcolor,true).',.3);
					overflow:auto;
				}
				table#cal_'.$cal_ID.'.calendar .current-day .open-details {
					background:#'.$this->currentdaycolor1.';
					filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#'.$this->currentdaycolor1.'", endColorstr="#'.$this->currentdaycolor2.'"); 
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->currentdaycolor1.'), to(#'.$this->currentdaycolor2.')); 
					background: -moz-linear-gradient(top,  #'.$this->currentdaycolor1.',  #'.$this->currentdaycolor2.'); 
				}
				table#cal_'.$cal_ID.'.calendar .close-details {
					color: #'.$this->datetextcolor.';
					font-size: 0.6em;
					position: absolute;
					right: 7px;
					text-decoration: underline;
					top: 3px;
				}
				table#cal_'.$cal_ID.'.calendar .open-details {
					border:4px solid #'.$this->headingcolor1.';	
					-webkit-box-shadow: 2px 3px 8px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					-moz-box-shadow: 2px 3px 8px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					box-shadow: 2px 3px 8px rgba('.$this->hex2RGB($this->shadowcolor,true).',.2);
					-moz-border-radius: 4px; 
					-webkit-border-radius: 4px; 
					border-radius: 4px;
					padding:5px 5px 10px 5px;
				}
				table#cal_'.$cal_ID.'.calendar .open-details li {
					padding-top:5px;
					padding-bottom:0px;
					font-size:0.7em;	
				}
				table#cal_'.$cal_ID.'.calendar .no-events {
					cursor:default;
				}
				table#cal_'.$cal_ID.'.calendar .has-events {
					cursor:pointer;
				}
				table#cal_'.$cal_ID.'.calendar .week-start {
					border-left:1px solid #'.$this->bordercolor.';	
				}
				table#cal_'.$cal_ID.'.calendar .week-number {
					border-bottom:none;
					border-right:none;
					vertical-align:middle;
					padding:2px;
					font-size:1.2em;
					font-weight:bold;
					text-shadow: 0 1px 1px rgba('.$this->hex2RGB($this->shadowcolor,true).',.3);
					filter: Shadow(Color=#'.$this->shadowcolor.', Direction=135, Strength=1);
					';
					if($this->weeknumbers && $this->displaytype=='mini') { // adjust cell widths for mini/full
					$html .= '
					width:8.9%;
					';
					} else if($this->weeknumbers) { 
					$html .= '
					width:2.3%;
					';
					} 
					$html .= '
				}
				table#cal_'.$cal_ID.'.calendar td ul li {
					cursor: pointer;
					list-style: none outside none;
					overflow: hidden;
					padding: 3px 6px 4px;
					font-size:0.8em;
					text-shadow:none;
					-moz-border-radius: 4px; 
					-webkit-border-radius: 4px; 
					border-radius: 4px;
					margin-top:2px;
					line-height:1.2em;
				}
				table#cal_'.$cal_ID.'.calendar td ul li span.event-time {
					font-weight:bold;
					font-size:0.8em;	
				}
				table#cal_'.$cal_ID.'.calendar .week-number span {
					color:#'.$this->headingtextcolor.';';
				if($this->weeknumbers=='left' && $this->weeknumrotate===true) // rotate weeknumbers left
				$html .= '
					-webkit-transform: rotate(-90deg); 
					-moz-transform: rotate(-90deg);
					';
				if($this->weeknumbers=='right' && $this->weeknumrotate===true) // rotate weeknumbers right
				$html .= '
					-webkit-transform: rotate(90deg); 
					-moz-transform: rotate(90deg);
					';
				$html .= '
				}
				table#cal_'.$cal_ID.'.calendar .event-details {
					display:none;	
					font-size:0.85em;
					line-height:1.4em;
					margin:5px 5px 6px;
				}
				table#cal_'.$cal_ID.'.calendar .event-details div {
					word-wrap: break-word;
					overflow: hidden;
					white-space: normal;
				}
				table#cal_'.$cal_ID.'.calendar .day-without-date {
					background:#'.$this->nodatecolor.';
					color:#'.$this->nodatetextcolor.';
					vertical-align:middle;
				}
				table#cal_'.$cal_ID.'.calendar .day-without-date div {
					text-align:center;
				}
				table#cal_'.$cal_ID.'.calendar .weekend { 
					background:#'.$this->weekendcolor1.';
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->weekendcolor1.'), to(#'.$this->weekendcolor2.'));
					background: -moz-linear-gradient(top,  #'.$this->weekendcolor1.', #'.$this->weekendcolor2.');
				}
				table#cal_'.$cal_ID.'.calendar .current-day {
					text-align:left;
					vertical-align:text-top;
					background:#'.$this->currentdaycolor1.';
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->currentdaycolor1.'), to(#'.$this->currentdaycolor2.')); 
					background: -moz-linear-gradient(top,  #'.$this->currentdaycolor1.',  #'.$this->currentdaycolor2.'); 
				}
				table#cal_'.$cal_ID.'.calendar .current-day .day-number {
					color:#'.$this->headingtextcolor.';	
				}
				table#cal_'.$cal_ID.'.calendar td ul { 
					margin:0;
					padding:2px 0;
					text-align:left; 
				}
				table#cal_'.$cal_ID.'.calendar td div, table#cal_'.$cal_ID.'.calendar th div { 
					position:relative; 
				}
				table#cal_'.$cal_ID.'.calendar .previous, table#cal_'.$cal_ID.'.calendar .next {
					 position:relative;
					 width:auto;
					 margin-top:0.6em;
				}
				table#cal_'.$cal_ID.'.calendar .previous {
					float:left; 
				} 
				table#cal_'.$cal_ID.'.calendar .next {
					float:right; 
				} 
				table#cal_'.$cal_ID.'.calendar .previous span, table#cal_'.$cal_ID.'.calendar .next span { 
					font-size:1.2em;
					line-height:0.5em; 
				}
				table#cal_'.$cal_ID.'.calendar .previous a, table#cal_'.$cal_ID.'.calendar .next a { 
					color:#'.$this->headingtextcolor.'; 
					text-decoration:none; 
				}';
				if($this->displaytype=='mini') { // css specific to mini displaytype
				$html .= '
				table#cal_'.$cal_ID.'.mini { 
					width:'.$this->minicalwidth.'; 
					margin:5px 0; 
				}
				table#cal_'.$cal_ID.'.mini .calendar-heading { 
					padding:0 0 2px 0; 
					font-size:1.2em;
					height:1.2em;
				}
				table#cal_'.$cal_ID.'.mini .week-number {
					font-size:1.2em;
				}
				table#cal_'.$cal_ID.'.mini td {
					list-style: none outside none;
					overflow: hidden;
					padding: 2px 6px;
					font-size:1.3em !important;
					text-shadow:none;
				}
				table#cal_'.$cal_ID.'.mini td ul li {
					list-style: none outside none;
					overflow: hidden;
					padding: 2px 6px;
					font-size:0.8em;
					text-shadow:none;
					-moz-border-radius: 0; 
					-webkit-border-radius: 0; 
					border-radius: 0;
					margin-top:0;
				}
				table#cal_'.$cal_ID.'.mini .previous, table#cal_'.$cal_ID.'.mini .next { 
					top:-3px;
					position:absolute;
					margin-top:0;
					float:none; 
				}
				table#cal_'.$cal_ID.'.mini .previous span, table#cal_'.$cal_ID.'.mini .next span { 
					font-size:1em;
					line-height:22px; 
				}
				table#cal_'.$cal_ID.'.mini .previous { 
					left:0px; 
				}
				table#cal_'.$cal_ID.'.mini .next { 
					right:0px; 
				}
				table#cal_'.$cal_ID.'.mini .normal-day-heading, table#cal_'.$cal_ID.'.mini .weekend-heading {
					font-size:1.2em;
					height:1em;
					line-height:1em;
				}
				table#cal_'.$cal_ID.'.mini .current-day {
					color:#'.$this->headingtextcolor.';
					background:#'.$this->currentdaycolor1.' !important;
					background: -webkit-gradient(linear, left top, left bottom, from(#'.$this->currentdaycolor1.'), to(#'.$this->currentdaycolor2.')) !important; 
					background: -moz-linear-gradient(top,  #'.$this->currentdaycolor1.',  #'.$this->currentdaycolor2.') !important;	
				}
				table#cal_'.$cal_ID.'.mini td ul {
					margin-left:20px;
					border:4px solid #'.$this->headingcolor1.';
					background:#'.$this->headingcolor1.';
					padding:0;
					-moz-border-radius: 4px; 
					-webkit-border-radius: 4px; 
					border-radius: 4px; 	
				}
				table#cal_'.$cal_ID.'.mini .day-without-date, table#cal_'.$cal_ID.'.mini .day-with-date {
					height:2em;	
					text-align:center;
					white-space:nowrap;
					padding-bottom:3px !important;
					vertical-align:middle;
				}
				table#cal_'.$cal_ID.'.mini td ul.events { 
					display:none; 
					width:'.$this->tipwidth.'; 
					position:absolute; 
					z-index:9; 
					top:0px; 
					right:0px; 
				}
				table#cal_'.$cal_ID.'.mini td ul.events li {
					color:#'.$this->datetextcolor.' !important;	
				}';
				} // end mini
				} // end mini/full
				if(in_array($this->displaytype,array('list','mini-list'))) { // css for list and mini-list displaytypes
				$html .= '
				#cal_'.$cal_ID.'.list h2.list-title {
					position:absolute;
					top:0.3em;
					left:2px;
					font-size:2.4em;
					margin:0;
					padding:0;
					line-height:1.4em;
				}
				#cal_'.$cal_ID.'.list, #cal_'.$cal_ID.'.mini-list {
					width:100%;
					padding:0 0 8px 0;
					position:relative;
				}
				div#cal_'.$cal_ID.'.list, div#cal_'.$cal_ID.'.mini-list {
					background:none;
					filter:none;	
				}
				div#cal_'.$cal_ID.'.list .view-buttons {
					float: right;
					margin: 0.5em 0;
					padding-bottom: 2px;
				}
				div#cal_'.$cal_ID.' ul.event-list {
					margin:0;
					padding:0;
					clear:both;
				}
				#cal_'.$cal_ID.'.list .event-metalabel {
					width:6.5em;	
				}
				div#cal_'.$cal_ID.' ul.event-list li {
					color:#'.$this->datetextcolor.';
					margin:0 0 8px;
					padding:0 0 7px;
					-moz-border-radius: 4px; 
					-webkit-border-radius: 4px; 
					border-radius: 4px;
					font-size:1.2em;
					clear:both;
					width:100%;
					float:left;
					list-style:none;
				}
				div#cal_'.$cal_ID.'.mini-list ul.event-list li {
					padding:5px 7px 7px;
					float:none;
					width:auto;
					margin:0 0 4px;
				}
				div#cal_'.$cal_ID.' .event-link {
					cursor:pointer;
				}
				div#cal_'.$cal_ID.'.mini-list ul.event-list li .event-title {
					font-weight:bold;
					font-size:1.1em;
				}
				div#cal_'.$cal_ID.' ul.event-list li h3.event-title {
					font-size:1.2em;
					margin:0 0 4px 0;
					-moz-border-radius: 4px 4px 0 0; 
					-webkit-border-radius: 4px 4px 0 0; 
					border-radius: 4px 4px 0 0;
					padding:4px 8px;
				}
				div#cal_'.$cal_ID.' ul.event-list li div.event-content {
					float:left;
					width:61%;
					padding:3px 8px;
				}
				div#cal_'.$cal_ID.'.mini-list ul.event-list li div.event-content {
					float:none;
					padding:2px 1px 0;
				}
				div#cal_'.$cal_ID.' ul.event-list li div.event-meta {
					font-size:1em;
					width:35%;
					float:right;
					padding-top:0.4em;	
				}
				div#cal_'.$cal_ID.'.mini-list ul.event-list li div.event-meta {
					font-size:1em;
					width:auto;
					padding-top:0.4em;	
					float:none;
				}
				';
				} // end css for list and mini-list
				$html .= '
				</style>
				';
			}
			
			return $html;
		}
		
		// js for all displaytypes
		function set_js() {
			global $cal_ID;

			$html = '';
			
			if($this->jqueryinclude && $cal_ID==1) // only include once if multiple calendars 
			$html = '
			<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js?ver=1.4.3"></script>
			<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js?ver=1.7.2"></script>
			';
			if($this->js!='disable' && $this->js!='default') { // load js file defined in properties
				$html = '<script type="text/javascript" src="'.$this->js.'"></script>';
			}
			else if($this->js!='disable') { // load default js
				$html .= '
				<script type="text/javascript">
				$(function() {
					function caljs'.$cal_ID.'() {
					if($.browser.msie) {
					$("#month_selector").css("margin-top","5px");	
					}
					$("table#cal_'.$cal_ID.' td.has-events").hover(function() {
						var swidth = $(this).outerWidth();
						var sheight = $(this).outerHeight();
						var position = $(this).position();
						$(this).append("<div class=\"hover-box\" />");
						var box = $(this).find(".hover-box");
						if($.browser.msie) {
							box.css({
								"position":"absolute",
								"top":position.top-1,
								"left":position.left-1,
								"border":"3px solid #'.$this->headingcolor1.'",
								"display":"none"
							});
							box.width(swidth-3.5);
							box.height(sheight-3);
						} else {
							box.css({
								"position":"absolute",
								"top":position.top-2,
								"left":position.left-2.5,
								"border":"3px solid #'.$this->headingcolor1.'",
								"display":"none"
							});
							box.width(swidth-2.5);
							box.height(sheight-2.5);
						}
						box.fadeIn();
					},function() {
						$(this).find(".hover-box").stop(true,true).fadeOut();
						$(this).find(".hover-box").remove();
					});';
					if($this->displaytype=='full' && $this->monthselector!==false) {// js for monthselector
					$html .= '
					$("table#cal_'.$cal_ID.' #calendar_month,table#cal_'.$cal_ID.' #calendar_year").change(function() {
						var jump_month = $("table#cal_'.$cal_ID.' #calendar_month").val();
						var jump_year = $("table#cal_'.$cal_ID.' #calendar_year").val();
						var calendar_displaytype = $("table#cal_'.$cal_ID.' #calendar_displaytype").val();';
					if($this->ajax===true) {
						$html .= '
						$("#cal_'.$cal_ID.'").fadeTo(0,0.6);
						$.ajax({
							type: "GET",
							url: window.location.pathname,
							data: "m="+jump_month+"&y="+jump_year+"&t="+calendar_displaytype,
							success: function(data) {
								data = $(data).find("#cal_'.$cal_ID.'");
								$("#cal_'.$cal_ID.'").replaceWith(data);
								caljs'.$cal_ID.'();
							}
						});
						
						';
					} else {
						$html .= '$("table#cal_'.$cal_ID.' #month_selector").submit();';
					}
					$html .= '	
					});';
					} // end monthselector js
					if($this->displaytype=='full') { // js for full displaytype
					$html .= '
					$("table#cal_'.$cal_ID.'.full td").click(function() {
						$("table#cal_'.$cal_ID.'.full .open-details").remove();
					});
					$("table#cal_'.$cal_ID.'.full td.has-events").click(function() {
						
						if($(this).find("table#cal_'.$cal_ID.' .open-details").length<=0) {
						
							$(this).find(".hover-box").remove();
							var swidth = $(this).outerWidth();
							var sheight = $(this).outerHeight();
							var contents = $(this).html();
							var position = $(this).position();
							$("table#cal_'.$cal_ID.'.full .open-details").remove();
							if($("#details_'.$cal_ID.'").length <= 0)
							$(this).append("<div id=\"details_'.$cal_ID.'\" class=\"open-details\" />");
							var details = $("#details_'.$cal_ID.'");
							details.css({
								"position":"absolute",
								"top":position.top,
								"left":position.left,
								"z-index":"9",
								"opacity":"0"
							});
							details.html("<a href=\"#\" class=\"close-details\">close</a>"+contents);
							details.width(swidth);
							details.height(sheight);
							$("#details_'.$cal_ID.', #details_'.$cal_ID.' .close-details").click(function() {
								$(this).closest(".has-events").find(".hover-box").remove();
								$("#details_'.$cal_ID.'").remove();
								return false;
							});
							$("#details_'.$cal_ID.' a").click(function() {
								window.location = $(this).attr("href");
								return false;
							});
							var wwidth = $(window).width();
							var nright = position.left+swidth*1.5;
							var nleft = position.left-swidth/2;
							if(nright<=wwidth) {
								if(nleft <= 0) {
									moveleft = "+=0"; /* left */
								} else {
									moveleft = "-="+swidth/1.58; /* normal */
								}
							} else {
								moveleft = "-="+swidth*1.135; /* right */
							}
							details.find(".event-time").hide();
							details.find("ul.events").hide();
							details.animate({
								opacity:"1",
								top:"-="+sheight/2,
								left:moveleft,
								height:"+="+sheight,
								width:"+="+swidth
							}, {
								duration: 500,
								easing: "easeOutQuint" 
							});
							details.find("ul.events").delay(400).show(0);
							details.find(".event-details").show();
						}
						});';
						} // end full js
						if($this->displaytype=='mini') { // js for mini displaytype
						$html .= ' 
						$("table#cal_'.$cal_ID.'.mini td").hover(function() {
							$("table#cal_'.$cal_ID.'.mini td").find("ul").hide();
							$(this).find("ul").fadeIn(200);
						},function() {
							$("table#cal_'.$cal_ID.'.mini td").find("ul").stop(true,true).hide();
						}).mousemove(function(e){
							if(e.pageX+40+'.str_replace('px','',$this->tipwidth).'>$("body").width()) {
								var newmargin = '.str_replace(array('px','%','em'),'',$this->tipwidth).'+20;
								$(this).find("ul").css("margin-left",-newmargin+"px");	
							}
							$(this).find("ul").css({top:e.pageY+"px",left:e.pageX+"px"});
						});';
						} // end mini js
						if($this->ajax===true) { // enable ajax
						$html .= '
						$("#cal_'.$cal_ID.' .view-buttons a").live("click", function() {
							$("#cal_'.$cal_ID.'").fadeTo(0,0.6);
						});
						$("#cal_'.$cal_ID.' .next a,#cal_'.$cal_ID.' .previous a").live("click", function() {
							$("#cal_'.$cal_ID.'").fadeTo(0,0.6);
							$.ajax({
							 	type: "GET",
							  	url: $(this).attr("href"),
							  	success: function(data) {
    								data = $(data).find("#cal_'.$cal_ID.'");
									$("#cal_'.$cal_ID.'").replaceWith(data);
									caljs'.$cal_ID.'();
  								}
							});
							return false;
						});
						
						';
						} // end ajax
						$html .= '
					}
					caljs'.$cal_ID.'();
				});
				</script>
				';
			}
						
			return $html;
				
		}
		
		// header area for all displaytypes
		function calendar_head($content='') {
			global $cal_ID;
			
			$html = '';
			
			if(!in_array($this->displaytype,array("list","mini-list"))) { // mini and full cal
				$html = '
				<table id="cal_'.$cal_ID.'" class="calendar '.$this->displaytype.'" cellpadding="0" cellspacing="0" border="0">
				<tr>';
				// render week number on left
				if($this->weeknumbers=='left') {
					$html .= '<td rowspan="2" class="week-number">&nbsp;</td>';	
				}
				$html .= '
					<td colspan="7" style="border-right:none;">
						<div class="calendar-heading">';
				$html .= $this->cal_previous(); // previous month link
				if($this->displaytype == 'full' && $this->monthselector === true) {
					$html .= $this->month_selector(); // month selector
				} else {
					$html .= '<span>'.$this->monthname.' '.$this->year.'</span>';
				}
				$html .= $this->cal_next(); // next month link
				if($this->displaytype!='mini') 
				$html .= $this->cal_viewmode(); // viewmode buttons
				$html .= '	
						</div>
					</td>';
				// render week number on right
				if($this->weeknumbers=='right') {
					$html .= '<td rowspan="2" class="week-number">&nbsp;</td>';	
				}
				$html .= '
				</tr>
				<tr>';
				if($this->weeknames == 1) {
					$weekdays = $this->weekdays; // full
				} else if($this->weeknames == 3 || $this->displaytype=='mini') { 
					$weekdays = $this->weekdayschar; // single char
				} else {
					$weekdays = $this->abbrevweekdays; // 3 char
				}
				for($i = 0; $i < count($weekdays); $i++) {
					$di = ($i + $this->weekstartday) % 7;
					$weekday = $weekdays[$di];
					if($i==0) $thisclass = 'normal-day-heading week-start';
					else $thisclass = 'normal-day-heading';
					$html .= $this->calendar_cell($weekday, $thisclass); // calendar cells for full & mini
				}
				$html .= '
				</tr>
				';
			} else { // event list
				$html .= '<div id="cal_'.$cal_ID.'" class="calendar '.$this->displaytype.'">';
				if($this->displaytype=='list') {// don't show viewmode buttons for mini-list
					if($this->listtitle!='')
					$html .= '<h2 class="list-title">'.$this->listtitle.'</h2>';
					$html .= $this->cal_viewmode();
				}
			}

			return $html;
		}
		
		// viewmode buttons
		function cal_viewmode() {
			/*$html = '
			<div class="view-buttons">
				<a href="'.$this->view->url(array("t" => "list")).'" class="events-view'.($this->displaytype=="list"?" selected":"").'">Event List</a> 
				<a href="'.$this->view->url(array("t" => "full")).'" class="calendar-view'.($this->displaytype=="full"?" selected":"").'">Calendar</a>
			</div>';
			return $html;*/
			return ''; 
		}
		
		// next month link
		function cal_next() {
			global $cal_ID;
			$html = '';
			$next = $this->calcDate($this->year.'-'.$this->month, '+1', 'month');
			$this->nextmonth = ($this->getMonth($next['year'].'-'.$next['month'],$this->monthnames).' '.$next['year']);
			if($this->displaytype == 'full') {
				$divider = $this->monthselector===false?'&nbsp;|&nbsp;':'';
				$nexttext = $divider.$this->nextmonth.' <span>&raquo;</span>';
			} else {
				$nexttext = '<span>&raquo;</span>';
			}
			$html = '<div class="next"><a href="'.$this->view->url(array('date' => $next['year'].'-'.$next['month'], 't' => $this->displaytype)).'">'.$nexttext.'</a></div>';
			return $html;
		}
		
		// previous month link
		function cal_previous() {
			global $cal_ID;
			$previous = $this->calcDate($this->year.'-'.$this->month, '-1', 'month');
			$this->previousmonth = ($this->getMonth($previous['year'].'-'.$previous['month'],$this->monthnames).' '.$previous['year']);
			if($this->displaytype == 'full') {
				$nexttext = '<span>&laquo;</span> '.$this->previousmonth;
			} else {
				$nexttext = '<span>&laquo;</span>';
			}
			$html = '<div class="previous"><a href="'.$this->view->url(array('date' => $previous['year'].'-'.$previous['month'], 't' => $this->displaytype)).'">'.$nexttext.'</a></div>';
			return $html;
		}
		
		// month/year select box
		function month_selector() {
			$html = '
			<form method="get" id="month_selector">
			<input type="hidden" name="t" id="calendar_displaytype" value="'.$this->displaytype.'" />
			<select name="m" id="calendar_month">';
			$calinfo = cal_info(0);
			foreach($calinfo['months'] as $k=>$v) {
				$html .= '<option value="'.$k.'" '.($this->month==$k?'selected="selected"':'').'>'.$v.'</option>';
			}
			$html .= '
			</select> 
			<select name="y" id="calendar_year">';
			$startyear = $this->year - $this->yearoffset;
			$endyear = $this->year + $this->yearoffset;
			for($y = $startyear; $y <= $endyear; $y++) {
            	$html .= '<option value="'.$y.'" '.($this->year==$y?'selected="selected"':'').'>'.$y.'</option>';
			}
			$html .= '</select>';
			if($this->js == 'disable') {
				$html .= '<input type="submit" value="Go" id="month_go" />';
			}
			$html .= '</form>';
		
			return $html;	
		}

		// calendar cells for mini and full displaytypes
		function calendar_cell($day, $class, $date = '', $style = '') {
			global $cal_ID;
			
			$addclass = '';
			
			if(strpos($class,'normal-day-heading')!==false) $tag = 'th';
			else $tag = 'td';
			
			if($day != '') {
				$bgColor = '';
				$cellevents = array();
				
				if(is_array($this->events)) { // events array populated from addEvent()
					foreach($this->events as $event) {
						$color = $event['color'];
						$title = $event['title'];
						$link = $event['link'];
						$eventdate = $event['date'];
						$from = strtotime($event['from']);
						$to = strtotime($event['to']);
						$starttime = $event['starttime'];
						$endtime = $event['endtime'];
						$details = $event['details'];
						$location = $event['location'];
						if($date == date('Y-n-j',strtotime($eventdate))) {
							$cellevents[] = array(
								'color'=>$color, 
								'title'=>$title, 
								'link'=>$link, 
								'date'=>$eventdate, 
								'from'=>$from, 
								'to'=>$to,
								'starttime'=>$starttime,
								'endtime'=>$endtime,
								'details'=>$details,
								'location'=>$location
							);
						}
						if($this->displaytype == 'mini' && $date == date('Y-n-j',strtotime($eventdate))) { // mini calendar
							$style .= 'background:' . $color . ';'; // shows the color for the last event listed for the day
						}
					}
					
				}
				
				// sort by starttime for the cell
				if(count($cellevents)>0){
					$cellevents = $this->arraySort($cellevents,'starttime');
				}
				
				if($date == date('Y-n-j')) {
					$addclass .= ' current-day'; // if processing the current day
				}
				
				if(in_array($this->getDay($date),array('Saturday','Sunday'))) {
					$addclass .= ' weekend'; // if a weekend
				}
				
				if($this->getDay($date,0)==$this->weekstartday) {
					$addclass .= ' week-start';	// if the weekstartday
				}
								
				if($this->arraySearch($date,$cellevents)!==false) {
					$addclass .= ' has-events';	// if the date has events
				} else if(strpos($class,'normal-day-heading')===false) {
					$addclass .= ' no-events';	// no events
				} 
				
				$combinedclass = $class.$addclass; // combine all classes
				
				$html = '<'.$tag.' class="' . $combinedclass .'"';
				
				// check/set links for mini calendar
				if($this->minilinkbase!='' && $this->displaytype=='mini') {
					if($this->linktarget=='_blank')
					$html .= ' onClick="window.open(\''.$this->minilinkbase.'?date='.$date.'\', \'_blank\')"';
					else
					$html .= ' onClick="'.$this->linktarget.'.location=\''.$this->minilinkbase.'?date='.$date.'\'"';
				}
			}
			if($style) $html .= ' style="' . $style . '"';
			if($this->displaytype == 'mini') {
				$content = $day; // day number or prev/next month cell content
			} else {
				$content = '<div class="day-number">'.$day.'</div>'; // day number or prev/next month cell content
			}
			if(count($cellevents) > 0) {
				$content .= '<ul class="events">';
				$eventcontent = '';
				foreach($cellevents as $cellevent) {
					$cellevent['color2'] = $this->colourBrightness($cellevent['color'],-0.89); // set gradient color based on event color
					$eventcontent .= '<li style="
						background-color:' . $cellevent['color'] . ';';
					if($this->displaytype != 'mini') { // do not use gradient for mini
						$eventcontent .= '
						background-image: -webkit-gradient(linear, left top, left bottom, from('.$cellevent['color'].'), to('.$cellevent['color2'].'));
						background-image: -moz-linear-gradient(top,  '.$cellevent['color'].', '.$cellevent['color2'].');
						';
					}
					$eventcontent .= '">';
					if($cellevent['starttime']!='') // add starttime to the event label if available
					$eventcontent .= '<span class="event-time">'.str_replace(array('am','pm'),array('a','p'),date($this->timeformat,$cellevent['starttime']));
					$eventcontent .= '</span> ';
					$eventcontent .= $cellevent['title'];
					$eventcontent .= '<div class="event-details">';
					if($cellevent['starttime']!='') { // event details - hidden until clicked (full)
						$eventtime = '<div><span class="event-metalabel">Time:</span> '.date($this->timeformat,$cellevent['starttime']);
						if($cellevent['endtime']!='') $eventtime .= "-".date($this->timeformat,$cellevent['endtime']);
						$eventtime .= '</div>';
						$eventcontent .= $eventtime;
					}
					if($cellevent['location']!='') $eventcontent .= '<div><span class="event-metalabel">Location:</span> <span style="white-space:nowrap">'.$cellevent['location'].'</style></div>';
					if($cellevent['link']!='') $eventcontent .= '<div><span class="event-metalabel">Link:</span> <a href="'.$cellevent['link'].'">'.$cellevent['link'].'</a></div>';
					if($cellevent['details']!='') $eventcontent .= '<div><span class="event-metalabel">Details:</span><br/>'.$cellevent['details'].'</div>';
					$eventcontent .= '</div>';
					$eventcontent .= '</li>';
					}
					$content .= $eventcontent;	
					$content .= '</ul>';
			}
			$html .= '>' . $content . '</td>';

			return $html;
		}
		
		// calendar footer for all displaytypes
		function calendar_foot($content='') {
			if(in_array($this->displaytype,array('full','mini'))) {
				$html = '</table>';
			} else {
				$html = '</div>';	
			}
			return $html;	
		}

		// add an event to the events array
		function addEvent($arr) {
			
			$from = array_key_exists('from',$arr)?$arr['from']:''; 
			$to = array_key_exists('to',$arr)?$arr['to']:$arr['from'];
			$starttime = array_key_exists('starttime',$arr)?strtotime($arr['starttime']):'';
			$endtime = array_key_exists('endtime',$arr)?strtotime($arr['endtime']):'';
			$color = array_key_exists('color',$arr)?$arr['color']:''; 
			$title = array_key_exists('title',$arr)?$arr['title']:''; 
			$details = array_key_exists('details',$arr)?$arr['details']:'';
			$location = array_key_exists('location',$arr)?$arr['location']:''; 
			$link = array_key_exists('link',$arr)?$arr['link']:'';
			
			if(!in_array($this->displaytype,array("list","mini-list"))) { // mini and full cal
				$eventdays = $this->dateDiff($from,$to); // get the difference in days between the two dates
				if($eventdays < 0) return; // return if $to < $from
				for( $i=0; $i <= $eventdays; $i++ ) { 
					$date = strtotime(date("Y-m-d", strtotime($from)) . " +".$i." day");
					$date = date("Y-n-j",$date);
					if($from >= strtotime($this->year.'-'.$this->month) || strtotime($to) >= strtotime($this->year.'-'.$this->month)) {
						$this->events[] = array(
							'color'=>$color, 
							'title'=>$title, 
							'link'=>$link, 
							'date'=>$date, 
							'from'=>$from, 
							'to'=>$to,
							'starttime'=>$starttime,
							'endtime'=>$endtime,
							'details'=>$details,
							'location'=>$location);
					}
				}
			} else { // event list
				$date = $from;
				// only add events that start or end today or in the future
				if($from >= strtotime($this->year.'-'.$this->month.'-'.$this->day) || strtotime($to) >= strtotime($this->year.'-'.$this->month.'-'.$this->day)) {
					$this->events[] = array( // for event list it will display in the order events are added
						'color'=>$color, 
						'title'=>$title, 
						'link'=>$link, 
						'date'=>$date, 
						'from'=>strtotime($from), 
						'to'=>strtotime($to),
						'starttime'=>$starttime,
						'endtime'=>$endtime,
						'details'=>$details,
						'location'=>$location);
				}
			}
			
		}
		
		// add an event to the events array
		function addGoogleCalendar($arr) {
			
			$feed = str_replace('basic','full',$arr['xmlfeed']); // make sure we are getting the full feed
			$start_min = array_key_exists('startdate',$arr)?$arr['startdate']:'';
			$start_max = array_key_exists('enddate',$arr)?$arr['enddate']:'';
			$color = $arr['color']; 
			$link = array_key_exists('link',$arr)?$arr['link']:'';
			
			// prepare a few values for the google feed link
			if(in_array($this->displaytype,array('full','mini'))) {
				$startdate = $this->year.'-'.date('m',strtotime($this->year.'-'.$this->month.'-01')).'-'.date('d',strtotime($this->year.'-'.$this->month.'-01'));
				$enddate_parts = $this->calcDate($startdate,'+1','month');
				$enddate = date('Y-m-d',strtotime($enddate_parts['year'].'-'.$enddate_parts['month'].'-'.$enddate_parts['day']));
			} else { // list
				$startdate = $this->year.'-'.date('m',strtotime($this->year.'-'.$this->month.'-'.$this->day)).'-'.date('d',strtotime($this->year.'-'.$this->month.'-'.$this->day));
				$enddate_parts = $this->calcDate($startdate,'+12','months');
				$enddate = date('Y-m-d',strtotime($enddate_parts['year'].'-'.$enddate_parts['month'].'-'.$enddate_parts['day']));
			}
			
			// modified google feed link
			$feed = $feed.'/?start-min='.$startdate.'&singleevents=true&recurrence-expansion-start='.$startdate.'&start-max='.$enddate.'&recurrence-expansion-end='.$enddate;
			
			// use cURL to get the feed into a PHP string variable
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$feed);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$xml = new SimpleXMLElement(curl_exec($ch));
			curl_close($ch);
			
			// loop through the google calendar xml and assing to array
			foreach ($xml->entry as $cal){
				$ns_gd = $cal->children('http://schemas.google.com/g/2005');
				
				$startTime = explode('T',strval($ns_gd->when->attributes()->startTime));
				$startDate = $startTime[0];
				$startTime = array_key_exists(1,$startTime)&&$startTime[1]!=''?date($this->timeformat,strtotime($startTime[1])):'';
				$endTime = explode('T',strval($ns_gd->when->attributes()->endTime));
				$endDate = $endTime[0];
				$endTime = array_key_exists(1,$endTime)&&$endTime[1]!=''?date($this->timeformat,strtotime($endTime[1])):'';
				
				$events[] = array(                            
					'id'=>strval($cal->id),
					'published'=>strval($cal->published),
					'updated'=>strval($cal->updated),
					'title'=>strval($cal->title),
					'content'=>nl2br(strval($cal->content)),
					'where'=>strval($ns_gd->where->attributes()->valueString),
					'link'=>strval($cal->link->attributes()->href),
					'authorName'=>strval($cal->author->name),
					'authorEmail'=>strval($cal->author->email),
					'startTime'=> $startTime,
					'endTime'=> $endTime,
					'startDate'=> strtotime($startDate),
					'endDate'=> strtotime($endDate)
				);
            }
			
			if(count($events)>0) {
				// loop through google events array and add to our calendar
				foreach ($events as $event) {
					
					// fix full day events enddate
					if($event['startTime']=='') {
						$tmpdate = $this->calcDate(date($this->dateformat,$event['endDate']), '-1', 'day');	
						$event['endDate'] = strtotime($tmpdate['year'].'-'.$tmpdate['month'].'-'.$tmpdate['day']);
					}
					
					$this->addEvent(
						array(
							"from"=>date('Y-n-j',$event['startDate']),
							"to"=>date('Y-n-j',$event['endDate']),
							"starttime"=>$event['startTime'],
							"endtime"=>$event['endTime'],
							"color"=>$color,
							"title"=>$event['title'],
							"details"=>$event['content'],
							"location"=>$event['where'],
							"link"=>$link
						)
					);
				}
			}
			
		}
		
		// pulls everything together and returns the calendar for all displaytypes
		function showcal() {
			global $cal_ID;
			
			$html = '';

			$html .= $this->set_styles(); 	// load css
			$html .= $this->set_js();		// load js
			$html .= $this->calendar_head();	// set table head
			
			if(!in_array($this->displaytype,array("list","mini-list"))) { // mini and full cal
				$html .= '
				<tr>';
				
				// render week number on left
				if($this->weeknumbers=='left' && $this->monthstartday!=$this->weekstartday) {
					$html .= '<td class="week-number"><span>'.date('W',strtotime($this->year.'-'.$this->month)).'</span></td>';	
				}
				
				// render previous month cells
				$emptycells = 0;
			
				for($counter=0;$counter<$this->monthstartday-$this->weekstartday;$counter++) {
					if($counter == 0) $thisclass = 'day-without-date week-start'; // only on first
					else $thisclass = 'day-without-date';
					if($this->displaytype == 'full') {
						$html .= $this->calendar_cell($this->previousmonth, $thisclass);
					} else {
						$html .= $this->calendar_cell('&nbsp;', $thisclass);
					}
					$emptycells ++;
				}
				
				// render days
				$rowcounter = $emptycells;
				$numinrow = 7;
				$weeknumadjust = $numinrow - ($this->monthstartday-$this->weekstartday);
			
				for($counter=1;$counter<=$this->numdaysinmonth;$counter++) {
					$date = $this->year.'-'.$this->month.'-'.$counter;
					// render week number on left
					if($this->weeknumbers=='left' && $this->weekstartday==$this->getDay($date,0)) {
						$adjustweek = $this->calcDate($date,'+'.$weeknumadjust,'day');
						$adjustweek = $adjustweek['year'].'-'.$adjustweek['month'].'-'.$adjustweek['day'];
						$html .= '<td class="week-number"><span>'.date('W',strtotime($adjustweek)).'</span></td>';	
					}
					$rowcounter ++;
					$html .= $this->calendar_cell($counter, 'day-with-date', $date);
					if( $rowcounter % $numinrow == 0 ) {
						// render week number on right
						if($this->weeknumbers=='right') {
							$html .= '<td class="week-number"><span>'.date('W',strtotime($date)).'</span></td>';	
						}
						$html .=  "</tr>";
						if( $counter < $this->numdaysinmonth ) {
							$html .=  "<tr>";
						}
						$rowcounter = 0;
					}
				}
			
				// render next month cells
				$numcellsleft = $numinrow - $rowcounter;
				if( $numcellsleft != $numinrow ) {
					for( $counter = 0; $counter < $numcellsleft; $counter ++ ) {
						if($this->displaytype == 'full')
						$html .= $this->calendar_cell($this->nextmonth, 'day-without-date');
						else
						$html .= $this->calendar_cell('&nbsp;', 'day-without-date');
						$emptycells ++;
					}
				}
				
				// render week number on right
				if($this->weeknumbers=='right' && $numcellsleft!=7) {
					$html .= '<td class="week-number" style="border-bottom:1px solid #'.$this->bordercolor.';"><span>'.date('W',strtotime($date)).'</span></td>';	
				}
				 
				$html .= '
					</tr>';
				
			} else { // event list
				$html .= '<ul class="event-list"';
				if($this->displaytype=='mini-list') // set mini list width
				$html .= ' style="width:'.$this->minilistwidth.'"';
				$html .= '>';
				// sort the array by date
				if(count($this->events)>0){
					$this->events = $this->arraySort($this->events,'from');
				}
				// splice the array after the sort if listlimit is defined
				if($this->listlimit!==false) {
					$this->events = array_splice($this->events,0,$this->listlimit);
				}
				foreach($this->events as $event) {
					$event['color2'] = $this->colourBrightness($event['color'],-0.89);	
					if($this->displaytype=='list') { // full event list
						$html.= '<li style="background:#'.$this->eventlistbg.'">';
						$html.= '<h3 class="event-title" style="background:'.$event['color'].';">'.$event['title'].'</h3>';	
						$html.= '<div class="event-content">'.($event['details']?$event['details']:$this->eventemptytext).'</div>';	
						$html.= '<div class="event-meta">';
						$html.= '<table>';
						if($event['from']!='') $html.= '<tr><td><div class="event-metalabel">Start:</div></td><td>'.date($this->dateformat,$event['from']).'</td></tr>';
						if($event['to']!='' && $event['from']!=$event['to']) $html.= '<tr><td><div class="event-metalabel">End:</div></td><td>'.date($this->dateformat,$event['to']).'</td></tr>';	
						if($event['starttime']!='') $html.= '<tr><td><div class="event-metalabel">Time:</div></td><td>'.date($this->timeformat,$event['starttime']);
						if($event['endtime']!='') $html.= '-'.date($this->timeformat,$event['endtime']);
						if($event['starttime']!='') $html.= '</td></tr>';
						if($event['location']!='') $html.= '<tr><td><div class="event-metalabel">Location:</div></td><td>'.$event['location'].'</td></tr>';
						if($event['link']!='') $html.= '<tr><td><div class="event-metalabel">Link:</div></td><td><a href="'.$event['link'].'">'.$event['link'].'</a></td></tr>';
						$html.= '</table></div>';	
					} else { // mini event list
						$html.= '<li style="background:'.$event['color'].';"';
						if($this->minilinkbase!==false) { // enable link (good for linking to full calendar)
							$html .= ' class="event-link"';
							if($this->linktarget=='_blank')
							$html .= ' onClick="window.open(\''.$this->minilinkbase.'\', \'_blank\')"';
							else
							$html .= ' onClick="'.$this->linktarget.'.location=\''.$this->minilinkbase.'\'"';
						} else if($event['link']!='') { // enable link (links to event url)
							$html .= ' class="event-link"';
							if($this->linktarget=='_blank') 
							$html .= 'onClick="window.open(\''.$event['link'].'\', \'_blank\')"';
							else
							$html .= ' onClick="'.$this->linktarget.'.location=\''.$event['link'].'\'"';
						}
						$html.= '>';
						$html.= '<div class="event-title">'.$event['title'].'</div>';
						$html.= '<div class="event-meta">';
						if($event['from']!='') $html.= ''.date($this->dateformat,$event['from']);
						if($event['to']!='') $html.= '-'.date($this->dateformat,$event['to']);	
						$html.= '</div>';	
					}
					$html.= '</li>';
				}
				if(count($this->events)<=0) { // if events array is empty
					$html.= '<li style="background:#'.$this->eventlistbg.'">';
					$html.= '<div class="event-content">No Upcoming Events.</div>';
					$html.= '</li>';
				}
				$html .= '</ul>';
			}
			$html .= $this->calendar_foot();
		
			// remove tabs, line breaks, vertical tabs, null-byte
			$html = $this->stripWhitespace($html);
			
			return $html;
		}
		
		//--------------------------------------------------------------------------------------------
		// Helper Functions
		//--------------------------------------------------------------------------------------------
		
		// returns day of week from passed date (string), $type: 0=number,1=full(Monday,Tuesday,etc),2=abbreviation(Mon,Tue,etc)
		function getDay($date,$type=1) {
			$date = date('Y-n-j',strtotime($date));
			$date_parts=explode('-', $date);
			$jd=cal_to_jd(CAL_GREGORIAN,$date_parts[1],$date_parts[2],$date_parts[0]);
			return jddayofweek($jd,$type);	
		}
		
		// returns month from passed date (string), $type: 0=number,1=full(January,February,etc),2=abbreviation(Jan,Feb,etc)
		function getMonth($date,$type=1) {
			$date = date('Y-n-j',strtotime($date));
			$date_parts=explode('-', $date);
			$jd=cal_to_jd(CAL_GREGORIAN,$date_parts[1],$date_parts[2],$date_parts[0]);
			return jdmonthname($jd,$type);	
		}
		
		// returns # of days (int) between 2 dates (string)
		function dateDiff($beginDate, $endDate) {
			if($endDate=='') return 0;
			$fromDate = date('Y-n-j',strtotime($beginDate));
			$toDate = date('Y-n-j',strtotime($endDate));
			$date_parts1=explode('-', $beginDate);
			$date_parts2=explode('-', $endDate);
			$start_date=gregoriantojd($date_parts1[1], $date_parts1[2], $date_parts1[0]);
			$end_date=gregoriantojd($date_parts2[1], $date_parts2[2], $date_parts2[0]);
			return $end_date - $start_date;
		}
		
		// add/subtract day,week,month,days from startdate
		function calcDate($startdate,$increment,$unit) { 
			$date = date("Y-n-j",strtotime(date("Y-n-j", strtotime($startdate)) . " ".$increment." ".$unit));
			$date = explode('-',$date);
			$newdate = array('year'=>$date[0],'month'=>$date[1],'day'=>$date[2]);
			return $newdate;
		}
		
		// recursive array search returns the key of occurance (int) or false if not found
		function arraySearch($needle, $haystack, $index = null) {
			$aIt = new RecursiveArrayIterator($haystack);
			$it = new RecursiveIteratorIterator($aIt);
			while($it->valid()) {       
				if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) {
					return $aIt->key();
				}
				$it->next();
			}
			return false;
		} 
		
		function colourBrightness($hex, $percent) {
			// remove hash
			$hash = '';
			if (stristr($hex,'#')) {
				$hex = str_replace('#','',$hex);
				$hash = '#';
			}
			// change hex to rgb
			$rgb = array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
			// calculate
			for ($i=0; $i<3; $i++) {
				// check if brighter or darker
				if ($percent > 0) {
					// lighter
					$rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1-$percent));
				} else {
					// darker
					$positivePercent = $percent - ($percent*2);
					$rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1-$positivePercent));
				}
				// don't allow values greater than 255
				if ($rgb[$i] > 255) {
					$rgb[$i] = 255;
				}
			}
			// change rgb back to hex
			$hex = '';
			for($i=0; $i < 3; $i++) {
				// convert the decimal digit to hex
				$hexDigit = dechex($rgb[$i]);
				// add a leading zero if necessary
				if(strlen($hexDigit) == 1) {
					$hexDigit = "0" . $hexDigit;
				}
				// append to the hex string
				$hex .= $hexDigit;
			}
		return $hash.$hex; // with hash back in string
		}
		
		function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
			$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // check hex string
			$rgbArray = array();
			if (strlen($hexStr) == 6) { // if a proper hex code e.g. #RRGGBB
				$colorVal = hexdec($hexStr);
				$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
				$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
				$rgbArray['blue'] = 0xFF & $colorVal;
			} elseif (strlen($hexStr) == 3) { // if shorthand notation e.g #RGB
				$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
				$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
				$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
			} else {
				return false; // invalid hex color code
			}
			// returns the rgb string or the associative array
			return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; 
		}
		
		// Removes tabs, line breaks, vertical tabs, null-byte. Everything but a regular space.
		function stripWhitespace($c) {
			$c = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $c);	
			return trim($c);
		}
		
		// sorts an associative array by values of passed key
		function arraySort($a,$subkey) {
			foreach($a as $k=>$v) {
				$b[$k] = strtolower($v[$subkey]);
			}
			asort($b);
			foreach($b as $key=>$val) {
				$c[] = $a[$key];
			}
			return $c;
		}

	} // end class