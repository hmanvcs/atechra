<?php
# define the environment as testing
define('APPLICATION_ENV', 'testing'); 

require_once realpath(dirname(__FILE__)).'/../public/index_includes.php';

// autoload Doctrine Models
Zend_Loader_Autoloader::getInstance()->registerNamespace('Doctrine')
         ->pushAutoloader(array('Doctrine', 'autoload'), 'Doctrine');
         
// get the database connection parameters 
$db_params = $application->getBootstrap()->getPluginResource('db')->getParams();

// initialize the doctrine connection manager 		
$manager = Doctrine_Manager::getInstance();
# set conservative loading for models, saves memory and does not include all model files
$manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
# set validation for all fields
$manager->setAttribute(Doctrine_Core::ATTR_VALIDATE, Doctrine_Core::VALIDATE_ALL);
# allow overidding of field accessors 
$manager->setAttribute(Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);
// configure and set the cache driver on the manager
$cacheConn = Doctrine_Manager::connection(new PDO('sqlite::memory:'));
$cacheDriver = new Doctrine_Cache_Db(array('connection' => $cacheConn, 'tableName' => 'cache'));
// create the cache table
$cacheDriver->createTable();

// set the cache for the queries and resultsets
$manager->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
$manager->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);
$manager->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN, 86400);

$dsn = 'mysql://' . $db_params['username'] . ':' . $db_params['password'] . '@' .$db_params['host'] . '/' . $db_params['dbname'];
$conn = $manager->connection($dsn);

// set all field names to be escaped with the MySQL backtick. This allows the use of MySQL keywords as column names
$conn->setAttribute(Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);
# use the native enumeration for the database
$conn->setAttribute(Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../tests/application/controllers'),
    realpath(APPLICATION_PATH . '/../tests/application/models'),
    get_include_path(),
)));

Zend_Session::$_unitTestEnabled = true;