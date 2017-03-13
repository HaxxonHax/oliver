<?php
/* 
 * $Id: config-dist.php 13 2010-04-13 01:07:08Z veghead $
 * Copy this file to 'config.php' and then 
 * uncomment the settings you wish to change.
 */

// General options
$conf['vfstype'] = 'ftp';
$conf['ftp_server'] = '127.0.0.1';
$conf['ftp_server_port'] = 21;
$conf['ftp_passive'] = false;
$conf['ftp_timeout'] = 90;
$conf['imgdir'] = 'graphics';
$conf['session_name']  =  'OLIVER';
$conf['motdfile'] = 'motd.php';
$conf['charset'] = 'UTF-8';
$conf['app_title'] = 'Secure FTP Portal';

// Look in 'lang' directory for available translations
$conf['lang'] = 'en-uk';

// Set to false if you don't wish to allow users to choose their 
// language upon login
//$conf['lang_ask'] = true;

// Shares, shortcuts, drives, areas...I don't care what you call them :)
// Don't delete this! If you don't want any, simply remove everything
// within the parentheses.
//$conf['roots'] = array(
//        'H Drive' => '/work',
//        'I Drive' => '/inform',
//        );
$conf['roots'] = array();


// Set to false if you don't wish to allow users to have direct
// links to files
//$conf['enable_dlink'] = true;


// Set to false if you don't wish to allow users to change file
// modes
//$conf['enable_chmod'] = true;


// Number of seconds before a session expires between
// accesses
$conf['session_timeout'] = 600;


// Set to false to disable logging
$conf['enable_logging'] = true;


// Set to an absolute pathname or the word 'syslog' if you 
// prefer to use syslog. 
$conf['logfile'] = '/var/www/oliver/oliver.log';


// Syslog facility to use
// accesses
//$conf['syslog_facility'] = LOG_USER;


// Use this array to prevent certain filenames from 
// being displayed. The key is the filename and the value should be 
// a description.
//$conf['veto_filenames'] = array(
//    'Network Trash Folder' => 'Macintosh debris',
//    ':2eDS_Store' => 'Macintosh debris',
//    );

// Use this array to list the admins for your machines.  Otherwise, they won't 
// get the right menu.
$conf['admins'] = array('unixuser1','unixuser2','unixuser3','unixuser4','unixuser5');
$conf['password_min'] = 6;
$conf['password_max'] = 0; // setting to 0 defaults to 1000
$conf['special_characters_required'] = 1;
$conf['capital_letters_required'] = 1;
$conf['numbers_required'] = 1;

?>
