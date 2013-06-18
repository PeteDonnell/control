<?php
/**
 * CoNtRol configuration file
 *
 * Configuration details for CoNtRol. This file is included at the top of header.php, and
 * hence is automatically included in every page that produces HTML output. It must be
 * included separately in each handler page.
 *
 * Note: when configuring your site, make sure to set SITE_DIR to the correct relative path.
 *
 * @author     Pete Donnell <pete dot donnell at port at ac at uk>
 * @copyright  University of Portsmouth, Kitson Consulting Limited 2012-2013
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    01/10/2012
 * @modified   30/05/2013
 */

/////////////////////////////////////////
// THINGS YOU SHOULD DEFINITELY CHANGE //
/////////////////////////////////////////

// Email address for the site admin. All emails sent by CoNtRol are sent from this address.
define('ADMIN_EMAIL', 'control@reaction-networks.net', false);

// Database connection information. You definitely need to change this.

// MySQL on the same server
define('DB_STRING', 'mysql:host=localhost;dbname=control;charset=utf8', false);

// MySQL on a different server
//define('DB_STRING', 'mysql:host=1.2.3.4;port=3306;dbname=control;charset=utf8', false);

// SQLite
//define('DB_STRING', 'sqlite:sql/control.sqlite', false);

define('DB_USER', 'control', false);
define('DB_PASS', 'password', false);
define('DB_PREFIX', '', false);



/////////////////////////////////////
// THINGS YOU MIGHT WANT TO CHANGE //
/////////////////////////////////////

// Location for the executables used by CoNtRol. You may want to change this for extra security.
define('BINARY_FILE_DIR', '../bin/', false);

// Debugging variable. Set to true when debugging.
define('CRNDEBUG', false, false);

// Niceness command to use when running tests. Note trailing space.
// Value for dedicated server, i.e. normal priority okay
//define('NICENESS', '', false);
// Value for shared server without ionice. N.B. trailing space!
//define('NICENESS', 'nice -n 19 ', false);
// Value for shared server with ionice. N.B. trailing space!
define('NICENESS', 'nice -n 19 ionice -c3 ', false);

// Location for temporary files. The default should work but you may wish to change it.
define('TEMP_FILE_DIR', '/var/tmp/', false);

// The maximum amount of time a test will run before being cancelled. This is required because
// tests are run via calls to exec(), which doesn't count towards max_execution_time.
define('TEST_TIMEOUT_LIMIT', 60, false);



//////////////////////////////////////////////////
// THINGS YOU ALMOST CERTAINLY SHOULDN'T CHANGE //
//////////////////////////////////////////////////

// Get the site URL. You shouldn't need to change this unless you're running behind a proxy.
$protocol = 'http';
if(isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] and $_SERVER['HTTPS'] != 'off') $protocol .= 's';
define('SITE_DIR', 'control', false);
if(isset($_SERVER['HTTP_HOST'])) define('SITE_URL', $protocol.'://'.$_SERVER['HTTP_HOST'].'/'.SITE_DIR.'/', false);

// Default page information. You shouldn't need to change this.
define('DEFAULT_PAGE_TITLE', 'CoNtRol - Chemical Reaction Network analysis tool', false);
define('DEFAULT_PAGE_DESCRIPTION', 'Allows the user to input a chemical reaction network. Produces a DSR graph and carries out mathematical analysis of network.', false);

// Work out which line ending to use for file exports. Don't change this.
// Default to UNIX line ending
$line_ending = "\n";
if(isset($_SERVER['HTTP_USER_AGENT']))
{
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'Windows;') !== false) $line_ending = "\r".$line_ending;
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'Macintosh;') !== false) $line_ending = "\r";
}
define('CLIENT_LINE_ENDING', $line_ending, false);

// Extra database options. It shouldn't be necessary to change this.
if(strpos('mysql', DB_STRING) === 0 and (!defined(PHP_VERSION_ID) or PHP_VERSION_ID < 50306)) $db_options = array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
else $db_options = null;

// Probably obsolete, will be autodetected in future. Don't change this.
$supported_batch_file_types = array(
	array('extension' => 'zip', 'mimetype' => 'application/zip', 'binary' => '/usr/bin/unzip'),
//	array('extension' => 'rar', 'mimetype' => 'application/rar', 'binary' => '/usr/bin/unrar e')
);
