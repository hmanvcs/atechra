<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    realpath(APPLICATION_PATH . '/models'),
    realpath(APPLICATION_PATH . '/controllers'),
    realpath(APPLICATION_PATH . '/plugins'), # controller plugins
    realpath(APPLICATION_PATH . '/includes'), # classes and scripts used through the application
    get_include_path(),
)));

// public folder directory
if(APPLICATION_ENV == 'production'){
	define('PUBLICFOLDER', 'public_html');
} else {
	define('PUBLICFOLDER', 'public');
}

require_once APPLICATION_PATH.'/includes/commonfunctions.php';

/** register an autoloader */
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);
$autoloader->suppressNotFoundWarnings(false);

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// the database adapter for the session
Zend_Registry::set("dbAdapter", $application->getBootstrap()->getPluginResource('db')->getDbAdapter()); 

$cache = $application->getBootstrap()->getPluginResource('cachemanager')->getCacheManager()->getCache('database');
Zend_Registry::set('cache', $cache);

# Zend Translate instance
$translate = new Zend_Translate(
    array(
        'adapter' => 'ini',
        'content' => APPLICATION_PATH.'/configs/en.language.ini',
    )
);
# $translate->setCache($cache); // the caching of the translate seems to cause an error, do not know why
Zend_Registry::set('translate', $translate);

# Zend Logger instance
Zend_Registry::set("logger", $application->getBootstrap()->getPluginResource('log')->getLog());

# currency object
$currency = new Zend_Currency('en_US');
$currency->setCache($cache);
Zend_Registry::set('Zend_Currency', $currency); 

# Zend Mail instance
// create a new instance
$mailer = new Zend_Mail('utf8'); 
// set the default transport configured in application.ini
$mailer->setDefaultTransport($application->getBootstrap()->getPluginResource('mail')->getMail());

// set the default to and from addresses 
$mail_config = new Zend_Config($application->getBootstrap()->getPluginResource('mail')->getOptions());
$mailer->setDefaultFrom($mail_config->defaultFrom->email, $mail_config->defaultFrom->name);
$mailer->setDefaultReplyTo($mail_config->defaultReplyTo->email, $mail_config->defaultReplyTo->name);

// add the mail instance to the registry 
Zend_Registry::set("mail", $mailer);
// add caching for Zend_Table which is used for Session information
Zend_Db_Table_Abstract::setDefaultMetadataCache($cache); 