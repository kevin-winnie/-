<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default_master';
$active_record = TRUE;
$_master_slave_relation = array(
    //  'default_master' => array('default_slave'),

    'default_master'=>array(),
);
$db['default_master']['hostname'] = 'localhost';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
$db['default_master']['username'] = 'root';
$db['default_master']['password'] = 'abc23';//'30XkkaR&FNYjkWL7Z';//'30XkkaR&FNYjkWL7Z';
$db['default_master']['database'] = 'agent';
$db['default_master']['dbdriver'] = 'mysqli';
$db['default_master']['dbprefix'] = 'p_';
$db['default_master']['pconnect'] = FALSE;
$db['default_master']['db_debug'] = true;
$db['default_master']['cache_on'] = FALSE;
$db['default_master']['cachedir'] = '';
$db['default_master']['char_set'] = 'utf8';
$db['default_master']['dbcollat'] = 'utf8_general_ci';
$db['default_master']['swap_pre'] = '';
$db['default_master']['autoinit'] = FALSE;
$db['default_master']['stricton'] = FALSE;

$db['citybox_master']['hostname'] = 'localhost';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
$db['citybox_master']['username'] = 'root';
$db['citybox_master']['password'] = 'abc23';//'30XkkaR&FNYjkWL7Z';//'30XkkaR&FNYjkWL7Z';
$db['citybox_master']['database'] = 'citybox';
$db['citybox_master']['dbdriver'] = 'mysqli';
$db['citybox_master']['dbprefix'] = 'cb_';
$db['citybox_master']['pconnect'] = FALSE;
$db['citybox_master']['db_debug'] = true;
$db['citybox_master']['cache_on'] = FALSE;
$db['citybox_master']['cachedir'] = '';
$db['citybox_master']['char_set'] = 'utf8';
$db['citybox_master']['dbcollat'] = 'utf8_general_ci';
$db['citybox_master']['swap_pre'] = '';
$db['citybox_master']['autoinit'] = FALSE;
$db['citybox_master']['stricton'] = FALSE;

$db['platform_master']['hostname'] = 'localhost';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';//'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
$db['platform_master']['username'] = 'root';
$db['platform_master']['password'] = 'abc23';//'30XkkaR&FNYjkWL7Z';//'30XkkaR&FNYjkWL7Z';
$db['platform_master']['database'] = 'platform';
$db['platform_master']['dbdriver'] = 'mysqli';
$db['platform_master']['dbprefix'] = 'p_';
$db['platform_master']['pconnect'] = FALSE;
$db['platform_master']['db_debug'] = true;
$db['platform_master']['cache_on'] = FALSE;
$db['platform_master']['cachedir'] = '';
$db['platform_master']['char_set'] = 'utf8';
$db['platform_master']['dbcollat'] = 'utf8_general_ci';
$db['platform_master']['swap_pre'] = '';
$db['platform_master']['autoinit'] = FALSE;
$db['platform_master']['stricton'] = FALSE;