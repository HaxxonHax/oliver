<?php
/*
 * $Id: vfs-ftp.php 18 2010-04-30 19:12:36Z veghead $
 */
include('Net/SFTP.php');
require_once('Net/SFTP.php');
require_once('vfs.php');

class oliver_vfs_sftp extends oliver_vfs {

    function __construct($conf) 
    {
        parent::__construct($conf);
        $this->conf=$conf;
        $this->cid=Net_SFTP($this->conf['ftp_server']);
        printf("Creating new oliver sftp!\n");
    }


    function login($user,$pass) 
    {
        if (! ($res=$this->login($user,$pass))) {
            return($res);
        }
        $this->user = $user;
        $this->pass = $pass;
        return($res);
    }

    function get_pwd()
    {
        return($this->cid->pwd());
    }

    function chmod($file,$mode)
    {
        return($this->cid->chmod($mode,$file));
    }

    function chdir($pwd)
    {
        return($this->cid->chdir($pwd));
    }

    function rmdir($todel)
    {
        return($this->cid->rmdir($todel));
    }

    function cdup()
    {
        return($this->cid->chdir(".."));
    }

    function get($local_file,$file,$fl)
    {
        return($this->cid->get($file,$local_file));
    }

    function makeurl($file)
    {
        $url = 'ftp://'.$this->user.':'.$this->pass.'@';
        $url .= $this->conf['ftp_server'].':'.$this->conf['ftp_server_port'].'/';
        // The trailing slash above tell curl to use an absolute url.
        $url .= $this->get_pwd().'/'.$file;
        return $url;
    }

    function getPipe($file)
    {
        if (! $this->conf['hascurl']) {
            return false;
        }
        $url = $this->makeurl($file);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_URL => $url));
          
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    function put($local_file,$file,$fl)
    {
        return($this->cid->put($file,$local_file,NET_SFTP_LOCAL_FILE));
    }

    function delete($todel)
    {
        return($this->cid->delete($todel));
    }

    function ls($dir)
    {
        $f = empty($this->conf['ls_flags']) ? '' : $this->conf['ls_flags'].' ';
        if (isset($this->conf['space_in_filename_workaround']) && $this->conf['space_in_filename_workaround']) {
            $pwd = $this->cid->pwd();
                    $this->cid->chdir($dir);
                    $list = $this->cid->rawlist($dir);
                    $this->cid->chdir($pwd);
        } else {
            $list = $this->cid->rawlist($dir);
        }
        return($list);
    }

    function size($name)
    {
        return(@ftp_size($this->cid,$name));
    }

    function mkdir($name)
    {
        return(@ftp_mkdir($this->cid,$name));
    }

    function quit()
    {
        return(@ftp_quit($this->cid));
    }

    
}
?>
