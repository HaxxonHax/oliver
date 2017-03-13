<?php
/* 
 * $Id: functions-inc.php 8 2010-04-08 19:39:44Z veghead $
 */


function writeLog($logmsg)
{
    global $conf;
    if (! $conf['enable_logging']) {return;}
    if ($conf['logfile']=="syslog") {
        syslog(LOG_INFO,$logmsg);
    } else {
        if (!($fp=fopen($conf['logfile'], "a"))) {
            return;
        }
        date_default_timezone_set("America/Phoenix");
        $now=date("Y-d-m H:i:s");
        fputs($fp, "$now $logmsg\n");
        fclose($fp);
    }
}

function multiexplode ($delimiters,$string) {
    
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

// Validate the password according to parameters
#function validatepassword ($passWord) {
#  global $conf;
#  $passminlength = $conf['password_min'];
#  $passmaxlength = $conf['password_max'];
#  $requirespecial = $conf['special_characters_required'];
#  $requirecaps = $conf['capital_letters_required'];  
#  $requirenums = $conf['numbers_required'];
#
#  if ( ! is_numeric($passminlength) ) { $passminlength=8; }
#  if ( ! is_numeric($passmaxlength) ) { $passmaxlength=1000; }
#  if ( ! is_numeric($requirespecial) ) { $requirespecial=1; }
#  if ( ! is_numeric($requirecaps) ) { $requirecaps=1; }
#  if ( ! is_numeric($requirenums) ) { $requirenums=1; }
#  if ( $passmaxlength === 0 ) { $passmaxlength=1000; }
#
#  if ( strlen($passWord) < $passminlength ) { return 1; }
#  if ( $requirespecial > 0 ) {
#    if ( preg_match('/[\.;?,-=+_]/',$password ) { return 2; }
#  }
#  if ( $requirespecial > 0 ) {
#    if ( preg_match('/[0-9]/',$password ) { return 3; }
#  }
#}

function validatePassword ($password) {
  global $conf;
  $passminlength = $conf['password_min'];
  $passmaxlength = $conf['password_max'];
  $requirespecial = $conf['special_characters_required'];
  $requirecaps = $conf['capital_letters_required'];  
  $requirenums = $conf['numbers_required'];

  // Make sure we got a valid minimum length.
  if (!is_int ($passminlength) || $passminlength < 0) {
    $passminlength = 6;
  }

  if (!is_int ($passmaxlength) || $passmaxlength < 0) {
    $passmaxlength = 1000;
  }

  if (!is_int ($requirespecial) || $requirespecial < 0) {
    $requirespecial = 1;
  }

  if (!is_int ($requirecaps) || $requirecaps < 0) {
    $requirecaps = 1;
  }

  if (!is_int ($requirenums) || $requirenums < 0) {
    $requirenums = 1;
  }

  // Create the constraints for the password.
  $passReg = '';
  $passReg .= '(?=.*[a-z])';
  if ($requirecaps > 0) {
    $passReg .= '(?=.*[A-Z])';
  }
  if ($requirenums > 0) {
    $passReg .= '(?=.*\\d)';
  }
  if ($requirespecial > 0) {
    $special = preg_quote (',.;:"\'!?*(){}[]/^§|#¤%&_=<>@£$€ +-', '/');
    $passReg .= "(?=.*[$special])";
  }
 
  // Add the minimum length requirement.
  $passReg .= '.{'.$passminlength.',}';
 
  // Check that the password matches the constraints, and return a boolean.
  if (!preg_match ("/^$passReg\\z/u", $password)) {
    return false;
  }
 
  return $password;
}

?>
