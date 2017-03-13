<?php
/**
  * $Id: index.php 26 2010-07-20 04:08:27Z veghead $
  */
  set_time_limit(0);
  ini_set('error_reporting',E_ALL);
  ini_set('display_errors','1');
  if (! file_exists('config.php')) {
      header('Location:check.php');
      exit;
  }

define('WSVER','$Id: index.php 26 2010-07-20 04:08:27Z veghead $');

set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/share/pear' . PATH_SEPARATOR . '/usr/share/php' );

/************************************************************************************/
/* External Libraries                                                               */
/*    config-core.php   - The standard configuration file for Oliver                */
/*    session.php       - PHP session libraries for a logged in user                */
/*    functions-inc.php - External functions (writeLog)                             */
/*    vfs-[s]ftp.php    - Libraries for the Transfer Protocol we're using.          */
/************************************************************************************/
require_once('config-core.php');
require_once('session.php');
require_once('functions-inc.php');
require_once('vfs-'.$conf['vfstype'].'.php');


/************************************************************************************/
/* Create a new session and get our configuration settings.                         */
/************************************************************************************/
$s = new OliverSession($conf);
$conf = $s->getConf();

/************************************************************************************/
/* Enable logging to the syslog if set in our config file                           */
/************************************************************************************/
if ($conf['enable_logging'] && ($conf['logfile']=='syslog')) {
    openlog('oliver',LOG_PID|LOG_ERR, $conf['syslog_facility']);
}

/************************************************************************************/
/* Pseudo NLS stuff (messy)                                                         */
/*   Load the default language, in case a translation doesn't exist in the language */
/*   file                                                                           */
/************************************************************************************/
/* 
 * Pseudo NLS stuff - messy - very messy 
 * Load the default language, in case a translation doesn't exist in
 * the language file.
 */
require_once('lang/'.$conf['default_lang'].'.php');

if (file_exists('lang/'.$conf['lang'].'.php')) {
    require_once('lang/'.$conf['lang'].'.php');
}
$_SESSION['language'] = $conf['lang'];

// Create vfs object - also connects
$wvfs = eval('return new oliver_vfs_'.$conf['vfstype'].'($conf);');

if (! $s->sessionValid($wvfs)) {
    $errmsg = $s->getParam('error_message');
    require_once('header-inc.php');
    require_once('loginform-inc.php');
    require_once('footer-inc.php');
    die();
    exit;
}


// At this point we're logged in and everything's groovy. 
$_SESSION['atime'] = time();

// Set up some useful variables
$ip = $s->getParam('ip');
$pwd = $s->getParam('pwd');
$act = $s->getParam('act');
$mode = $s->getParam('mode');
$perms = $s->getParam('perms');
$file = urldecode($s->getParam('file'));
$fileactions = $s->getParam('fileactions');
$sessionID = $s->getParam('sessionID');
$changepass=false;
$nomatch=false;
$oldpass='';
$usertochange='';
$newpass1='';
$newpass2='';
$chpasssuccess = 0;
$output = '';

// Find out if we're an admin.
$cred=explode('|',$_SESSION['cred']);
$user=$cred[0];

foreach($conf['admins'] as $key)  {
  if ( $user === $key ) {
    $_SESSION['isadmin']=true;
    break;
  } else {
    $_SESSION['isadmin']=false;
  }
}
$isadmin = $_SESSION['isadmin'];

/* 
 * First we make sure this isn't a "utility" request 
 * if it is then display the utility 
 */
if ((! empty($_REQUEST['utilchmod'])) && ($file || count($fileactions))) {
    require_once('chmod.php');
    exit;
} elseif ((! empty($_REQUEST['utilchmod']))) {
    $error_message = $conf['nls']['select_files_first'];
}

// Next, make sure we're in the right place 
if (empty($pwd)) {
    $pwd = $wvfs->get_pwd();
} else {
    $pwd = urldecode($pwd);
    $wvfs->chdir($pwd);
}

// Let's see what action we're being asked to do
switch ($act) {

case 'chdir':
    if (! preg_match('/^\//',$file)) {
        $file = urldecode($pwd).'/'.$file;
    }
    if (! ($wvfs->chdir($file))) {
        $error_message = "Can't Open Folder";
    }
    break;

case 'changepass':
    $changepass=true;
    break;

case 'cdup':
    $wvfs->cdup();
    break;

case 'getfile':
    $mimetype = $s->getParam('mimetype');
    if ($s->getParam('download')) {
          $mimetype = 'application/octet-stream';
    }
    $mimetype = $mimetype ? $mimetype : 'application/octet-stream';
    $local_file = "ftp_cache/$sessionID.$ip.get";

    writeLog('['.$ip.'] Download: '.$file.' '.$local_file );
    $wvfs->showFile($file, $mimetype, $local_file);
    break;

case 'logout':
    session_destroy();
    session_regenerate_id(true);
    header('Location:./');
    die;
    break;
}

function print_changepass_admin() {
  global $conf;
  echo '<script>';
  echo '  function toggle(checkboxID, toggleID) {';
  echo '     var checkbox = document.getElementById(checkboxID);';
  echo '     var toggle = document.getElementById(toggleID);';
  echo '     updateToggle = checkbox.checked ? toggle.disabled=true : toggle.disabled=false;';
  echo '  }';
  echo '  function notoggle(checkboxID, toggleID) {';
  echo '     var checkbox = document.getElementById(checkboxID);';
  echo '     var toggle = document.getElementById(toggleID);';
  echo '     updateToggle = checkbox.checked ? toggle.disabled=false : toggle.disabled=true;';
  echo '  }';
  echo '</script>';

  echo '<input type="checkbox" onClick="toggle(\'toggle-input\',\'toggle-oldpass\');notoggle(\'toggle-input\',\'toggle-username\');" name="actpasstype" id="toggle-input">';

  echo '<label for="toggle-input" class="toggle-label" style="font-size:' . $conf['nls']['fontsize'] . ';"></label>';
  echo '<div style="font-size:' . $conf['nls']['fontsize'] . ';" class="toggle-user">';
  echo '  <br />Username: <input type=text name=usertochange id="toggle-username" placeholder=\'spearsb\' disabled>';
  echo '</div>';

  echo '<div style="font-size:' . $conf['nls']['fontsize'] . ';" class="toggle-oldpass">';
  echo '  <br />Successful password change will require you to re-login.';
  echo '  <br>Old Password: <input type=password name=oldpass placeholder=\'old password\' id="toggle-oldpass">';
  echo '</div>';

  echo '<p style="font-size:' . $conf['nls']['fontsize'] . ';">';
}

function print_changepass() {
  global $isadmin;
  global $conf;
  if ( $isadmin === true ) {
    print_changepass_admin();
  } else {
    echo '<p style="font-size:' . $conf['nls']['fontsize'] . ';">';
    echo '<br>Old Password: <input type=password name=oldpass placeholder=\'old password\'>';
  }
  echo '<br>New Password: <input type=password name=newpass1 placeholder=\'new password\'>';
  echo '<br>Repeat New Password: <input type=password name=newpass2 placeholder=\'repeat new password\'><br />';
  echo '<br>';
  echo '<input class="copt" type="submit" name="actchpass" value="',$conf['nls']['pwchbutt'],'"><br /><br />';
  echo '</p>';
}

function print_passrequirements()
{
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

  if (!is_int ($passmaxlength) || $passmaxlength <= 0) {
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

  if ( $passmaxlength < $passminlength )
  {
    $passmaxlength = 1000;
  }

  echo '<p style="font-size:' . $conf['nls']['fontsize'] . ';">';
  echo 'ERROR: Password does not meet requirements.<br />';
  echo 'The password MUST meet the following: <br />';
  echo 'Minimum length: ' . $passminlength . '<br />';
  echo 'Maximum length: ' . $passmaxlength . '<br />';
  echo 'At least ' . $requirespecial . ' special character(s).<br />';
  echo 'At least ' . $requirecaps . ' capital letter(s).<br />';
  echo 'At least ' . $requirenums . ' number(s).<br /><br />';
  echo '</p>';
}

// We want to make sure that the user is editable by our admin
function validateUser($username) {
  global $cred;
  $currentuser=$cred[0];

  $returnvalue=6;   // Always assume bad before assuming good.

  $userid = exec(
    'getent passwd | /usr/bin/awk -F: \'/^' . $username . ':/{print $3}\'',
    $result
  );

  $groupinfo = exec(
    'getent group ' . $currentuser . ' | /usr/bin/awk -F: \'/^' . $currentuser . ':/{print $4}\'',
    $result
  );

  if ( $groupinfo !== '' ) {
    $groups = explode(',',$groupinfo);
  
    foreach ( $groups as $key ) {
      if ( $key == $username )  // We're good and can change this person.
      {
        $returnvalue = 0;
      }
    }
  } else { $returnvalue = 6; }

  if ( $userid < 1000 ) { $returnvalue = 5; }

  return $returnvalue;
}

$pwd = $wvfs->get_pwd();

require_once('header-inc.php');

if (isset($_POST['actchpass'])) {
  $changepass=false;
  $cred=explode('|',$_SESSION['cred']);
  $user=$cred[0];
  if (!empty($_POST['usertochange']))
  {
    $usertochange=$_POST['usertochange'];
  }
  if (!empty($_POST['oldpass']))
  {
    $oldpass=$_POST['oldpass'];
  }
  if (!empty($_POST['newpass1']))
  {
    $newpass1=$_POST['newpass1'];
  }
  if (!empty($_POST['newpass2']))
  {
    $newpass2=$_POST['newpass2'];
  }
  // Passtype is 1 if we're checked, which means we're changing another user.
  $actpasstype = isset($_POST['actpasstype']) ? $_POST['actpasstype'] : 0;
  if ( $newpass1 !== $newpass2 )
  {
    $nomatch=true;
    $changepass=true;
  }
  // If we have a user to change, we better be an admin because we're changing that user's password.
  if ( $usertochange !== '' && $isadmin === true && $actpasstype === 'on' ) {
    $user = $usertochange;
    $validstatus = validateUser($usertochange);
    if ( $validstatus !== 0 ) { $chpasssuccess = $validstatus; };
  } else {   // Otherwise, there's an old password to change.
    $getpasshashcmd = "sudo /var/www/cgi-bin/getpasshash $user";
    $oldpasshash = exec($getpasshashcmd,$validpass,$status);
    if ( $status === 0 )
    {
      $validpass = password_verify($oldpass,$oldpasshash);
      if ( $validpass !== true )
      {
        $chpasssuccess=3;
      }
    }
  }
 
  if ( $chpasssuccess !== 3 && $chpasssuccess !== 4 && $chpasssuccess !== 5 && $chpasssuccess !== 6 && $chpasssuccess !== 7 )
  {
    // Here we actually change the password.
    if ( $newpass1 === $newpass2 )
    {
      // We've passed all the other tests.  We want to validate the password 
      // meets the parameters in config.php
      $validatevalue = validatePassword($newpass1);
      if ( $validatevalue === false ) {
        $chpasssuccess = 7;
#        $passminlength = $conf['password_min'];
#        $passmaxlength = $conf['password_max'];
#        $requirespecial = $conf['special_characters_required'];
#        $requirecaps = $conf['capital_letters_required'];
#        $requirenums = $conf['numbers_required'];
#
#        // Make sure we got a valid minimum length.
#        if (!is_int ($passminlength) || $passminlength < 0) {
#          $passminlength = 6;
#        }
#
#        if (!is_int ($passmaxlength) || $passmaxlength <= 0) {
#          $passmaxlength = 1000;
#        }
#
#        if (!is_int ($requirespecial) || $requirespecial < 0) {
#          $requirespecial = 1;
#        }
#
#        if (!is_int ($requirecaps) || $requirecaps < 0) {
#          $requirecaps = 1;
#        }
#
#        if (!is_int ($requirenums) || $requirenums < 0) {
#          $requirenums = 1;
#        }
#
#        if ( $passmaxlength < $passminlength )
#        {
#          $passmaxlength = 1000;
#        }
#
#        echo '<p style="font-size:' . $conf['nls']['fontsize'] . ';">';
#        echo 'ERROR: Password does not meet requirements.<br />';
#        echo 'The password MUST meet the following: <br />';
#        echo 'Minimum length: ' . $passminlength . '<br />';
#        echo 'Maximum length: ' . $passmaxlength . '<br />';
#        echo 'At least ' . $requirespecial . ' special character(s).<br />';
#        echo 'At least ' . $requirecaps . ' capital letter(s).<br />';
#        echo 'At least ' . $requirenums . ' number(s).<br /><br />';
#        echo '</p>';
      } else {
        $fp = fopen('/dev/urandom', 'r');
        $randomString = fread($fp, 32);
        fclose($fp);
        $salt = base64_encode($randomString);
        $somehash=crypt($newpass1,'$6$'.$salt);
        $shellscript="sudo /var/www/cgi-bin/chpasswd";
        $cmd="$shellscript " . $user . " '" . $newpass1 . "'";
#    $cmd="$shellscript " . $user . " '" . $somehash . "'";
        exec($cmd,$pssoutput,$status);
        syslog(LOG_INFO,"PASSHASH: " . $somehash);
        syslog(LOG_INFO,$cmd . " " . $status);
        if ( $status === 0 && $usertochange === '' )
        { // Success - password changed
          $nomatch=false;
          $chpasssuccess=1;
          session_destroy();
          session_regenerate_id(true);
          header('Location:./passwordsuccess.php');
          die;
        }
        else
        { // Password failed 
          $chpasssuccess=2;
        }
      }
    }    
  }
}

if (isset($_POST['actdel'])) {
    $deleted=0;
    foreach($fileactions as $key => $value)  {
        $tobedeleted = urldecode($pwd)."/".urldecode($key);
        if ($wvfs->delete($tobedeleted)) {
            $deleted++;
        } else {
            if ($wvfs->rmdir($tobedeleted)) {
                $deleted++;
            } else {
                $error_message.="$key: ".
                    $conf['nls']['delete_failed']."\\n";
            }
        }
    }
    $error_message .= "\\n$deleted ".$conf['nls']['files_deleted']."\\n";
    
} elseif (($conf['enable_chmod']) && 
    (!empty($_POST['actchmod'])) && $perms && $fileactions) {

    foreach($fileactions as $key => $value)  {
        $key=urldecode($key);
        if (! ($wvfs->chmod($key,decoct($perms)))) {
            $error_message.=$conf['nls']['cant_change_perms'].
                                " $key\\n";
        } else {
            $error_message.=$conf['nls']['perms_changed'].
                                " $key\\n";
        }
    }
} elseif (!empty($_POST['actmkdir'])) {
    if (! $wvfs->mkdir($s->getParam('newdir'))) {
        $error_message.=$conf['nls']['dir_create_failed'].
                            ' '.$s->getParam('newdir')."\n";
    } else {
        $error_message.=$conf['nls']['dir_created'].
                            ' '.$s->getParam('newdir')."\n";
    }
} elseif (!empty($_POST['actup'])) {
    $tmpFilename = $s->getParam('tmpFilename');
    $origFilename = $s->getParam('origFilename');
    writeLog('['.$ip.'] Upload: '.$origFilename.' '.$tmpFilename );
    if (is_uploaded_file($tmpFilename)) {
        $wvfs->put($origFilename,$tmpFilename, FTP_BINARY);
    } else {
        $error_message .= $conf['nls']['error'].$conf['nls']['err_upload'];
        if ($origFilename!="") {
            $error_message.=" ($origFilename)";
        }
    }
}

if (($sessionID) && ($s->getParam('showlisting'))) {
        echo '<div style="font-size:' . $conf['nls']['fontsize'] . ';" id="pathcss">';
        echo $conf['nls']['path'];
        echo " $pwd";
        echo '</div>';
}

// Display button bar
echo '<div class="buttonbar">';
echo '<a class="button" style="background-image:url(graphics/parent.gif)" href="',$_SERVER['PHP_SELF'],'?act=cdup&amp;file=';
echo rawurlencode(rawurlencode($file)),"&amp;pwd=$pwd",'">';
echo $conf['nls']['up'],'</a>';
echo '<a class="button" style="background-image:url(graphics/home.gif)" href="',$_SERVER['PHP_SELF'],'?sessionID=',$sessionID,'">';
echo $conf['nls']['home'],'</a>';

if (sizeof($conf['roots'])>0) {
    foreach($conf['roots'] as $key => $value) {
        echo '<a class="button" style="background-image:url(graphics/gollem.gif)" href="',$_SERVER['PHP_SELF'],"?pwd=$value",'">';
        echo $key,'</a>';
    }
}

echo '<a class="button" style="background-image:url(graphics/logout.gif)" href="',$_SERVER['PHP_SELF'],"?act=logout",'">';
echo $conf['nls']['logout'],'</a>';
echo '<a class="button" style="background-image:url(graphics/help.gif)" href="howto/howtousewebsftp.html">';
echo $conf['nls']['help'],'</a>';

// We're going to show the changepass/admin link for only admins.
// Otherwise, we give the same action, but with a different label.
if ( $isadmin === true ) {
  echo '<a class="button" style="background-image:url(graphics/admin.png)" href="',$_SERVER['PHP_SELF'],"?act=changepass",'">';
  echo $conf['nls']['admin'],'</a>';
} else {
  // Otherwise, we're going to show the settings link for everyone.
  echo '<a class="button" style="background-image:url(graphics/admin.png)" href="',$_SERVER['PHP_SELF'],"?act=changepass",'">';
  echo $conf['nls']['settings'],'</a>';
}
echo '<br />&nbsp;</div>';

// Get a list of files
$list = array();
$list = $wvfs->ls($pwd);

if ($conf['rawlistdebug'] && function_exists('base64_encode')) {
    echo '<h2>Copy this block of text and send it to the developers:</h2><pre>';
    echo preg_replace('/(.{80})/',"$1\n",base64_encode(serialize($list)));
    echo '</pre>';
    exit;
}

if (! is_array($list)) {
    $list=array();
}

// Remove the files in the veto list
$pa=array();

foreach($list as $key => $dirline) {
    //print (":$dirline:<br>\n");
    // Workaround for OS-X and its inventive "total" line
    if (preg_match("/^total\s+\d+$/",$dirline)) {continue;}
    $fn = $wvfs->getFilename($dirline,0);
    if(empty($conf['veto_filenames'][$fn]) && ($fn!=".")) {
        array_push($pa,$dirline);
    } 
}

$i=0;
$n = count($pa) - 1;

/* Error message if it exists */
if ($error==1) {
    $error_message = $conf['nls']['cant_create']." \"".urldecode($file)."\"".$conf['nls']['already_exists'];
}

if ($error_message) {
    print '<script language="JavaScript">';
    print "<!--\n";
    print "alert('$error_message');\n";
    print "//-->\n";
    print "</script>\n";
    print "<noscript>\n";
    print '<p><b>Status:'.preg_replace('/[^\w\s]n/','<br>',$error_message);
    print "</b></p>\n";
    print "</noscript>\n";
}

echo '<form name="indexform" method="post" enctype="multipart/form-data" action="',$_SERVER['PHP_SELF'],'?pwd=',$pwd,'">';
echo '<input type="hidden" name="sessionID" value="',$sessionID,'">',"\n";

    if ( $chpasssuccess === 2 )
    {
      print_r($output);
    }
    if ( $chpasssuccess === 3 )
    {
      echo '<br><p style="color: red;font-size:' . $conf['nls']['fontsize'] . ';">Sorry, the Old Password entered was invalid.</p>';
      print_changepass();
    }
    if ( $chpasssuccess === 5 )
    {
      echo '<br><p style="color: red;font-size:' . $conf['nls']['fontsize'] . ';">Sorry, you cannot change the password to privileged accounts.</p>';
      print_changepass();
    }
    if ( $chpasssuccess === 6 )
    {
      echo '<br><p style="color: red;font-size:' . $conf['nls']['fontsize'] . ';">Sorry, you do not have access to change that account\'s password.</p>';
      print_changepass();
    }
    if ( $chpasssuccess === 7 )
    {
      print_passrequirements();
      print_changepass();
    }
    if ( $chpasssuccess === 1 )
    {
      echo '<br>Password successfully changed.  ';
    }

  if ( $changepass === true )
  {
    if ( $nomatch === true && $chpasssuccess !== 3 )
    {
      echo '<p style="font-size:' . $conf['nls']['fontsize'] . ';">';
      echo 'Sorry, those passwords do not match.<br>';
      echo '</p>';
    }
    print_changepass();
  }

echo '<table class="filelist">';

$newdirname=$conf['nls']['new_directory'];
$newdirid='';

if ($n >= 0) {
    echo "<tr class=\"tablecell\">\n<td><!-- <input type=checkbox name=tog onclick=\"javascript:toggle();\"> -->&nbsp;</td>\n";
    echo "<td colspan=\"2\">".$conf['nls']['file_name']."</td>\n";
    echo "<td>".$conf['nls']['owner']."</td>\n";
    echo "<td>".$conf['nls']['permissions']."</td>\n";
    echo "<td>".$conf['nls']['last_mod']."</td>\n";
    echo "<td>".$conf['nls']['size']."</td>\n";
    echo "<td>&nbsp;</td>\n";
    echo "</tr>\n";
    
    // display each file on a line of its own 
    for ($i = 0; $i <= $n; $i++) {
        $pa[$i] = trim($pa[$i]);
    
        $cor=($i%2) ? "even" : "odd";
    
        echo "<tr class=\"$cor\">\n";
        // tick box
        echo '<td class="tool">';
        echo '<input type="checkbox" name="cb['.
                urlencode($wvfs->getFilename($pa[$i],0)).']">';
        echo "</td>\n";

        // icon
        echo '<td class="tool">';
        $filetype = $wvfs->getFileType($pa[$i]);
        if ( $filetype == 'd' || $filetype == 'l') {
            echo $wvfs->getFileTypeIcon($pa[$i]);
        } else {
            $fname = $wvfs->getFilename($pa[$i],0);
            echo '<a target="blank" href="',$_SERVER['PHP_SELF'],'?sessionID=',$sessionID,'&amp;act=getfile&amp;pwd=',rawurlencode($pwd);
            echo '&amp;mimetype=',rawurlencode($wvfs->getMIMEType($pa[$i])),'&amp;file=',rawurlencode($fname),'&amp;d=1">';
            echo $wvfs->getFileTypeIcon($pa[$i]),'</a>';
        }
        echo '</td>';

        // filename
        echo '<td>';
        $filetype = $wvfs->getFileType($pa[$i]);
        if ( $filetype == 'd' || $filetype == 'l') {
            $fname = urlencode($wvfs->getFilename($pa[$i],2));
            if ($fname == urlencode($newdirname.$newdirid)) {
                $newdirid++;
            }
            echo '<a href="',$_SERVER['PHP_SELF'],'?sessionID=',$sessionID,'&amp;act=chdir&amp;pwd=',urlencode($pwd),'&amp;file=',rawurlencode($fname);
        } else  {
            $fname = urlencode($wvfs->getFilename($pa[$i],0));
            echo '<a target="new" href="',$_SERVER['PHP_SELF'],'?sessionID=',$sessionID,'&amp;act=getfile&amp;pwd=';
            echo urlencode($pwd),'&amp;mimetype=',urlencode($wvfs->getMIMEType($pa[$i])),'&amp;file=',rawurlencode($fname);
        }
    
    
        echo '">'.$wvfs->getFilename($pa[$i],1).'</a>';
        echo "</td>\n";

        // owner
        echo "<td>";
        echo $wvfs->getOwner($pa[$i]);
        echo "</td>\n";

        // mode
        $mode = $wvfs->getFileMode($pa[$i]);
        $umode = urlencode($mode);
        echo '<td><span class="tt">';

        if ($conf['enable_chmod'] ) {
            echo '<a href="', $_SERVER['PHP_SELF'], '?pwd=', $pwd ,'&amp;file=',rawurlencode($fname), '&amp;mode=', $umode, '&amp;utilchmod=1">';
            echo $mode,'</a>';
        } else {
            echo $mode;
        }

        echo '</span></td>';

        // lastmod
        echo '<td>';
        echo $wvfs->getFileDate($pa[$i]);
        echo "&nbsp;</td>\n";

        // size
        echo '<td>';
        $tam = $wvfs->size($wvfs->getFilename($pa[$i],0));
        echo $wvfs->FileTam($tam);
        echo "&nbsp;</td>\n";


        // direct link
        echo '<td class="tool">';

        if ($conf['enable_dlink'] && $filetype != 'd') {
            echo '<a href="dl.php?f=',urlencode("$pwd/$fname"),'">';
            echo '<img src="graphics/link.png" width="12" height="12" alt="Direct Link"></a>';
        } else {
            echo '&nbsp;';
        }

        echo "</td></tr>\n";
    }
} else {
    echo '<tr class="even">';
    echo '<td colspan="3"><center><em>',$conf['nls']['empty_directory'];
    echo '</b></center></td>', "</tr>\n";
}

// get the decor right
$cor = ($i%2) ? "even" : "odd";

// suggestion for the name of a new directory 
$newdirname .= $newdirid;

// invite the user to create a directory
echo '<tr class="',$cor,'"><td>&nbsp;</td>';
echo '<td class="tool">';
echo '<img src="',$conf['imgdir'],'/dir.gif" alt=""></td>';
echo '<td class="tool" colspan="',($conf['listcols']-2),'"><input type="text" size="20" name="newdir" ';
echo 'placeholder="',$newdirname, '">&nbsp;';
echo '<input type="submit" name="actmkdir" ';
echo ' value="',$conf['nls']['create_new_directory'],'" class="bopt">';
echo '</td></tr>';

// Buttons!
echo '<tr><td class="topt" colspan="',$conf['listcols'],'">';
if ($conf['enable_chmod']) {
    echo '<input class="bopt" type="submit" name="utilchmod" value="',$conf['nls']['change_perms'],'"> | ';
}
echo '<input class="bopt" type="submit" name="actdel" value="',$conf['nls']['delete'],'">';
echo ' | <input class="bopt" type="submit" name="actup" value="',$conf['nls']['upload'],'">';
echo '&nbsp;<input type="file" name="filename">&nbsp;';
echo '</tr></table>';
echo '</form>';


// close our connection
$wvfs->quit();

require_once('footer-inc.php');
?>
