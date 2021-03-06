<?php

if (!isset($dontconnect))
$dontconnect = false;

error_reporting(E_ERROR | E_WARNING | E_PARSE);

session_start();
/**
 * Site-wide maintenance switch
 *
 * The $maintenance variable represents whether the site is undergoing site-wide
 * maintenance. If true, then you will not be able to access the site until you
 * attach ?maintenence=override to the url. You only have to do this once. If
 * false, everyone has access to the site as normal.
 *
 */
$maintenance = false;

/******************************************************************************/
if (!isset($_SESSION['maintenance_override'])) {
	if (isset($_GET['maintenance']) && $_GET['maintenance'] == 'override')
		$_SESSION['maintenance_override'] = true;
	else
		$_SESSION['maintenance_override'] = false;
}
else if ($_SESSION['maintenance_override'] === false)
	if (isset($_GET['maintenance']) && $_GET['maintenance'] == 'override')
		$_SESSION['maintenance_override'] = true;

if ($maintenance && $_SESSION['maintenance_override'] !== true)
	die("<h1>This site is currently undergoing maintenance. Please check back later. Thank you.</h1>");
if (!$maintenance && isset($_SESSION['maintenance_override']))
	unset($_SESSION['maintenance_override']);
/******************************************************************************/

if (!(isset($config['base_url']) && isset($config['root_dir']))) {
	require 'config.php';
}

/****************************************************
 * Initialization
 *
 * include all files in the includes directory
 */

	// universal libraries
/*	require('common.inc');
	require('error.inc');
	require('login.inc');

	require('globals.inc');*/


	define("ROOTURL", "http://tht.vrac.iastate.edu");
	
/*
 * Applying Security Filters
 *
 *	SQL Injection       - using mysql_real_escape_string
 *	XSS 		           - completely stripping out all html, css, comments, AND javascript
 *      Cleanliness          - using trim()
 *
 * Whenever bootstrap is called, this segment runs on $_POST, $_GET, and $_REQUEST.
 * It does a nice job of filtering nearly all XSS and Injection attacks that I could muster.
 *
 * It does NOT run inside of arrays, so avoid doing this "<input type="text" name="ref[]" />"
 *
 * Also do not get rid of the is_array if statement. That will cause very annoying bugs.
 */

	if($dontconnect == FALSE || !isset($dontconnect)){
		$initial_connect = connect();

	foreach($_POST as $k=>$v) {

		if(!is_array($v)) {
			$v = html2txt($v);
			$_POST[$k] = mysql_real_escape_string(trim($v));
		}
	}
	foreach($_REQUEST as $k=>$v) {

		if(!is_array($v)) {
			$v = html2txt($v);
			$_REQUEST[$k] = mysql_real_escape_string(trim($v));
		}
	}
	foreach($_GET as $k=>$v) {

		if(!is_array($v)) {
			$v = html2txt($v);
			$_GET[$k] = mysql_real_escape_string(trim($v));
		}
	}

		mysql_close($initial_connect);
	}




/*
 * Function that simplifies the connection process
 * Use this function if you want to connect to the database.
 *
 * NOTE: there are only 2 places in this entire application where the database name,
 *          database username, database password, and database location are found.
 *          This function is one of those locations. The other is in theme/admin_header.php
 *
 * @return $linkID - the mysql_connection resource pointer.  returned incase you ever need it.
 */
function connect() {
	global $dontconnect;
	if ($dontconnect == true) return null;
	// Get $db_config = array(db_name,db_user,db_pass,db_host).
	include('/data/tht_data/mysql_connections/T3wheat_writeaccess.inc');
	extract($db_config);
	$linkID = mysql_connect($db_host, $db_user, $db_pass);
	if(!$linkID) {
		die(mysql_error());
	}
    	else {
		mysql_select_db($db_name, $linkID);
	}
	return $linkID;
}





function html2txt($document){

	$search = array(
		'@<script[^>]*?>.*?</script>@si',   // Strip out javascript
                '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
	);

	$text = preg_replace($search, '', $document);
	return $text;

}





?>
