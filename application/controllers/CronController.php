<?php
/**
 * Controller that processes cron jobs 
 *
 */
class CronController extends IndexController   {
	
	/**
	 * Backs up the database with an option of sending the backup via email 
	 *
	 */
	function backupAction(){
		// disable rendering of the view and layout so that we can just echo the AJAX output
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		// the config object instance
		$config = Zend_Registry::get('config'); 
		
		 // get the database connection parameters 
		$db_params = Zend_Controller_Front::getInstance()->getParam("bootstrap")->getPluginResource('db')->getParams();
		
		#  configure your database variables below:
		$host_array = explode(":", $db_params['host']); 
		$dbhost = $host_array[0]; #  Server address of your MySQL Server
		$dbuser = $db_params['username']; #  Username to access MySQL database
		$dbpass = $db_params['password']; #  Password to access MySQL database
		$dbname = $db_params['dbname']; #  Database Name
		$dbport = isArrayKeyAnEmptyString(1, $host_array) ? "3306" : "3356"; 
		
		# Optional Options You May Optionally Configure
		$use_gzip = $config->backup->usegzip;  #  Set to No if you don't want the files sent in .gz format
		$remove_sql_file = $config->backup->removesqlfile; #  Set this to yes if you want to remove the .sql file after gzipping. Yes is recommended.
		$remove_gzip_file = $config->backup->removegzipfile; #  Set this to yes if you want to delete the gzip file also. I recommend leaving it to "no"
		
		# Configure the path that this script resides on your server.
		$savepath = APPLICATION_PATH.$config->backup->scriptfolder; #  Full path to this directory. Do not use trailing slash!
		$send_email = $config->backup->sendemail;  #  Do you want this database backup sent to your email? Fill out the next 2 lines
		
		# attachment mime type - default for a text attachment 
		$attachment_mime_type = "text/plain"; 
		
		# set the maximum execution time to ensure that the backup is completed 
		ini_set("max_execution_time", 600);
		
		$date = date("dMy-Hi");
		# sql backup filename
		$sqlattachmentname = $dbname."-".$date.".sql";
		# zipped backup filename
		$gzipattachmentname = $dbname."-".$date.".tar.gz";
		# sql backup path
		$sqlscriptpath = $savepath.DIRECTORY_SEPARATOR.$sqlattachmentname;
		# zipped backup path
		$zipfilepath = $savepath.DIRECTORY_SEPARATOR.$gzipattachmentname;
		
		$backupcommand = "mysqldump -R --add-drop-table --complete-insert --add-locks --quote-names --lock-tables -h ".$dbhost." -P ".$dbport." -u ".$dbuser." -p".$dbpass." ".$dbname.' -q > "'.$sqlscriptpath.'"';
		passthru($backupcommand);
		debugMessage("backup completed to ".$sqlattachmentname);	
		
		# create tar archive
		if($use_gzip=="yes"){		
			$zipline = "tar czf ".$zipfilepath." ".$sqlscriptpath;
			shell_exec($zipline);
			debugMessage("Gzip of backup completed");
		}
		# set email attachment name and path depending on weather to form zip or not
		if($use_gzip=="yes"){
			$attachmentpath = $zipfilepath;
			$attachmentname = $gzipattachmentname;
			$attachment_mime_type = "application/gzip"; 
		} else {
			$attachmentpath = $sqlscriptpath;
			$attachmentname = $sqlattachmentname;
		}
		
		# send an email with a copy of the backup	
		if($send_email == "yes" ){
			
			$mail = Zend_Registry::get('mail');
			# build the mailer class 
			$mail->addTo($config->get(APPLICATION_ENV)->get("databasebackupemail"));
			
			$mail->setSubject(sprintf($this->_translate->_("database_backup_subject"), date("j F Y h:i:s A"))); #  Subject in the email to be sent.
			$mail->setBodyText($this->_translate->_("database_backup_body")); #  Brief Message.
			
			# attachmentpath is the full path to the file and attachmentname is the name of the file
			$at = new Zend_Mime_Part(file_get_contents($attachmentpath));
			$at->filename = $attachmentname; 
			$at->disposition = Zend_Mime::DISPOSITION_INLINE;
			$at->encoding = Zend_Mime::ENCODING_BASE64;
			$at->type = $attachment_mime_type; 
			$mail->addAttachment($at);
			$mail->send(); 
			debugMessage("backup sent via email");
		}
		
		# remove sql file if condition is set
		if($remove_sql_file=="yes"){
			exec("rm -rf ".$sqlscriptpath);
		}
		# remove tar file if condition is set
		if($remove_gzip_file=="yes"){
			exec("rm -rf ".$attachmentpath);
		}
	}
	/**
	 * 
	 * function that sends out timesheet reminders for the previous week until it is submitted
	 */
	function timesheetreminderentryAction(){
		// disable rendering of the view and layout so that we can just echo the output
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$template = new EmailTemplate(); 
		
		$conn = Doctrine_Manager::connection(); 
		# obtain the list of all saved timesheets
		$unentered_timesheets_query_results = $conn->fetchAll("SELECT id, firstname, lastname, email, STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AS weekendingdate FROM useraccount WHERE id NOT IN (SELECT e.id FROM useraccount AS e LEFT JOIN timesheet AS ts ON e.id = ts.employeeid WHERE ts.weekendingdate = STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W')) AND useraccount.receivetimesheetreminders = '1'");
		debugMessage($unentered_timesheets_query_results);
		
		if (count($unentered_timesheets_query_results) != 0) {
			foreach ($unentered_timesheets_query_results as $line){
				// create mail object
				$mail = getMailInstance();
				
				// configure base stuff
				$default_sender = $mail->getDefaultFrom();
				$mail->addTo($line['email']);
				//$mail->addCc($default_sender['email']);
				$mail->setSubject($line['firstname']." ".$line['lastname'] ."'s Timesheet for Week Ending ".changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// assign values
				$template->assign('type', 'timesheet');
				$template->assign('firstname', $line['firstname']);
				$template->assign('weekendingdate', changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// render the view as the body of the email
				$mail->setBodyHtml($template->render('timesheetsubmissionreminder.phtml'));
				debugMessage($mail);
				$mail->send();
			}
		}
	}
	/**
	 * 
	 * function that sends out status report reminders for the previous week until it is submitted
	 */
	function statusreportreminderentryAction(){
		// disable rendering of the view and layout so that we can just echo the output
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$template = new EmailTemplate(); 
		
		$conn = Doctrine_Manager::connection(); 
		# obtain the list of all saved timesheets
		$unentered_statusreports_query_results = $conn->fetchAll("SELECT id, firstname, lastname, email, STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AS weekendingdate FROM useraccount WHERE id NOT IN (SELECT e.id FROM useraccount AS e LEFT JOIN statusreport AS ts ON e.id = ts.employeeid WHERE ts.weekendingdate = STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W')) AND useraccount.receivestatusreportreminders = '1'");
		debugMessage($unentered_statusreports_query_results);
		
		if (count($unentered_statusreports_query_results) != 0) {
			foreach ($unentered_statusreports_query_results as $line){
				// create mail object
				$mail = getMailInstance();
				
				// configure base stuff
				$default_sender = $mail->getDefaultFrom();
				$mail->addTo($line['email']);
				//$mail->addCc($default_sender['email']);
				$mail->setSubject($line['firstname']." ".$line['lastname'] ."'s Status Report for Week Ending ".changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// assign values
				$template->assign('type', 'status report');
				$template->assign('firstname', $line['firstname']);
				$template->assign('weekendingdate', changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// render the view as the body of the email
				$mail->setBodyHtml($template->render('timesheetsubmissionreminder.phtml'));
				debugMessage($mail);
				$mail->send();
			}
		}
	}
	/**
	 * 
	 * function that sends out emails to employees who have saved but not submitted their timesheets 
	 * it checks the last 6 months
	 */
	function savedtimesheetreminderAction(){
		// disable rendering of the view and layout so that we can just echo the output
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$template = new EmailTemplate(); 
		
		$conn = Doctrine_Manager::connection(); 
		# obtain the list of all saved timesheets
		$unsubmitted_timesheets_query_results = $conn->fetchAll("SELECT u.firstname, u.lastname, u.email, t.id, t.weekendingdate, t.`status` FROM useraccount AS u INNER JOIN timesheet AS t ON u.id = t.employeeid WHERE t.weekendingdate <= STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AND t.weekendingdate > STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 180 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AND t.`status` IN ('0', '3') AND u.receivetimesheetreminders = '1'");
		debugMessage($unsubmitted_timesheets_query_results);
		
		if (count($unsubmitted_timesheets_query_results) != 0) {
			foreach ($unsubmitted_timesheets_query_results as $line){
				// create mail object
				$mail = getMailInstance();
				
				// configure base stuff
				$default_sender = $mail->getDefaultFrom();
				$mail->addTo($line['email']);
				//$mail->addCc($default_sender['email']);
				$mail->setSubject($line['firstname']." ".$line['lastname'] ."'s Timesheet for Week Ending ".changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// assign values
				$template->assign('type', 'timesheet');
				$template->assign('firstname', $line['firstname']);
				$template->assign('weekendingdate', changeMySQLDateToPageFormat($line['weekendingdate']));
				$timesheetstatus = LookupType::getLookupValueDescription("TIMESHEET_STATUSREPORT_STATUS", $line['status']);
				$template->assign('timesheetstatus', strtolower($timesheetstatus));
				
				// render the view as the body of the email
				$mail->setBodyHtml($template->render('savedtimesheetsreminder.phtml'));
				debugMessage($mail);
				$mail->send();
			}
		}
	}
	/**
	 * 
	 * function that sends out emails to employees who have saved but not submitted their timesheets 
	 * it checks the last 6 months
	 */
	function savedstatusreportreminderAction(){
		// disable rendering of the view and layout so that we can just echo the output
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		
		$template = new EmailTemplate(); 
		
		$conn = Doctrine_Manager::connection(); 
		# obtain the list of all saved timesheets
		$unsubmitted_timesheets_query_results = $conn->fetchAll("SELECT u.firstname, u.lastname, u.email, t.id, t.weekendingdate, t.`status` FROM useraccount AS u INNER JOIN statusreport AS t ON u.id = t.employeeid WHERE t.weekendingdate <= STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 7 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AND t.weekendingdate > STR_TO_DATE(CONCAT(YEARWEEK((NOW() - interval 180 day),0),_utf8' ',_utf8'Friday'),_utf8'%X%V %W') AND t.`status` IN ('0', '3') AND u.receivestatusreportreminders = '1'");
		debugMessage($unsubmitted_timesheets_query_results);
		
		if (count($unsubmitted_timesheets_query_results) != 0) {
			foreach ($unsubmitted_timesheets_query_results as $line){
				// create mail object
				$mail = getMailInstance();
				
				// configure base stuff
				$default_sender = $mail->getDefaultFrom();
				$mail->addTo($line['email']);
				//$mail->addCc($default_sender['email']);
				$mail->setSubject($line['firstname']." ".$line['lastname'] ."'s Status Report for Week Ending ".changeMySQLDateToPageFormat($line['weekendingdate']));
				
				// assign values
				$template->assign('type', 'status report');
				$template->assign('firstname', $line['firstname']);
				$template->assign('weekendingdate', changeMySQLDateToPageFormat($line['weekendingdate']));
				$timesheetstatus = LookupType::getLookupValueDescription("TIMESHEET_STATUSREPORT_STATUS", $line['status']);
				$template->assign('timesheetstatus', strtolower($timesheetstatus));
				
				// render the view as the body of the email
				$mail->setBodyHtml($template->render('savedtimesheetsreminder.phtml'));
				debugMessage($mail);
				$mail->send();
			}
		}
	}
}

