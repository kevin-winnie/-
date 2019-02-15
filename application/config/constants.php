<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');


defined('PLATFORM_SECRET') or define('PLATFORM_SECRET', 'QUz3ZSZeA1sMcon4SsXjfzyGsdj4K9Rk');
defined('RBAC_URL') or define('RBAC_URL', 'http://stagingcityboxadmin.fruitday.com/api/');

defined('PLATFORM_HOST_SECRET') or define('PLATFORM_HOST_SECRET', 'irBplW5mdCGS6O5sRrMqKgI9i4f4rbuO');
defined('PLATFORM_HOST_URL') or define('PLATFORM_HOST_URL', 'http://platform1.fruitday.com/api/');

defined('CONFIG_PRE') or define('CONFIG_PRE', 'cb_config_');