<?php
//-------------------------------------------------------------------------------------------------
//SAE SDK deploy client, SAE development team, 2009
//kobe, sprewellkobe@163.com
//http://t.sina.com.cn/kobe
//-------------------------------------------------------------------------------------------------
require_once('snoopy.class.php');
require_once('yaml.function.php');
require_once('json.php' );
require_once('fupload.class.php');
//-------------------------------------------------------------------------------------------------
define('SAE_CODE_DEPOSITION_ADDRESS','http://deploy.sae.sina.com.cn/');
define('SAE_DIAGNOSE_ADDRESS','http://diag.sae.sina.com.cn/gather.php');
define('SDK_VERSION','1.0.5');
define('SDK_BUILD','1107071739');
define('SAE_TCP_CONNECTION_TIMEOUT_SECS',10);
//-------------------------------------------------------------------------------------------------

class FilePair
{
 var $filename;
 var $md5;
 var $size;
 function FilePair($filename,$md5,$size=0)
 {
  $this->filename=$filename;
  $this->md5=$md5;
  $this->size=$size;
 }
 function cmp_obj($a,$b)
 {
  return strcmp($a->filename,$b->filename);
 }
 
}//end class FilePair
class AppInfor
{
 var $appname;
 var $version;
 function AppInfor($appname,$version)
 {
  $this->appname=$appname;
  $this->version=$version;
 }
}//end class AppInfor
//-------------------------------------------------------------------------------------------------

function IsWindows()
{
 return strncmp(PHP_OS,'W',1)==0;
}
//-------------------------------------------------------------------------------------------------

function VisitFilesByPath($path,&$files,$with_md5=false,$ext_filter=false)
{
 if(is_dir($path)==false)
    return;
 for(;;)
    {
     $first=false;
     foreach(glob($path.'/*') as $filename)
            {
             if(is_dir($filename)==false)
               {
                if(is_array($ext_filter))
                  {
                   $found=false;
                   foreach($ext_filter as $ext)
                          {
                           if(fnmatch($ext,$filename))
                             {
                              $found=true;
                              break;
                             }
                          }
                   if($found)
                      continue;
                  } 
                if($with_md5)
                   $md5=md5_file($filename);
                else
                   $md5='';
                array_push($files,new FilePair($filename,$md5,filesize($filename)));
               }
             else
               { 
                if($first==false)
                   $first=$filename;
                else
                   VisitFilesByPath($filename,$files,$with_md5,$ext_filter);
               }
            }//end foreach
     if($first!=false)
        $path=$first;
     else
        break;
    }//end for;;
}
//-------------------------------------------------------------------------------------------------

function PrintError($error,$exit=false)
{
 echo 'Error('.SDK_VERSION.' '.SDK_BUILD.'): '.$error."\n";
 if($exit)
    exit;
}
//-------------------------------------------------------------------------------------------------

function PrintWarning($warning)
{
 echo 'Warning('.SDK_VERSION.' '.SDK_BUILD.'): '.$warning."\n";
}
//-------------------------------------------------------------------------------------------------

function ReadPassword()
{
 if(strncmp(PHP_OS,'W',1)==0)
    return trim(fgets(STDIN));
 $oldStyle=shell_exec('stty -g');
 shell_exec('stty -icanon -echo min 1 time 0');
 $password='';
 while(true)
      {
       $char=fgetc(STDIN);
       if($char==="\n")
          break;
       else if(ord($char)===127)
         {
          if(strlen($password)>0)
            {
             fwrite(STDOUT, "\x08 \x08");
             $password=substr($password, 0, -1);
            }
         }
       else
         {
          echo "*";
          $password.=$char;
         }
       }//end while
 shell_exec('stty '.$oldStyle);
 echo "\n";
 return $password;
}
//-------------------------------------------------------------------------------------------------

function CheckAppName($appname)
{
 if(preg_match('/^[0-9a-zA-Z]{4,18}$/',$appname)!=1)
    return false;
 return true;
}
//-------------------------------------------------------------------------------------------------

function CheckAppVersion($appversion)
{
 if(is_numeric($appversion)==false || strval($appversion)!==strval(intval($appversion)) ||
    intval($appversion)<0 ||intval($appversion)>PHP_INT_MAX)
    return false;
 return true;
}
//-------------------------------------------------------------------------------------------------

function MicroTimeFloat()
{
 list($usec,$sec)=explode(" ",microtime());
 return ((float)$usec+(float)$sec);
}
//-------------------------------------------------------------------------------------------------

function GetFileGapTime($filename)
{
 $at=fileatime($filename);
 $mt=filemtime($filename);
 $lt=$at>$mt?$at:$mt;
 return time()-$lt;
}
//-------------------------------------------------------------------------------------------------

function mkdir_r($dirName, $rights=0777)
{
 $dirs = explode('/', $dirName);
 $dir='';
 foreach($dirs as $part)
        {
         $dir.=$part.'/';
         if(!is_dir($dir) && strlen($dir)>0)
           mkdir($dir, $rights);
        }
}
//-------------------------------------------------------------------------------------------------

function removeDir($path)
{
 if(substr($path, -1, 1) != "/")
    $path .= "/";
 $normal_files = glob($path . "*");
 $hidden_files = glob($path . "\.?*");
 $all_files = array_merge($normal_files, $hidden_files);
 foreach($all_files as $file)
        {
         if(preg_match("/(\.|\.\.)$/", $file))
            continue;
         if(is_file($file)===TRUE)
            unlink($file);
         else if (is_dir($file) === TRUE)
            removeDir($file);
        }
 if(is_dir($path)=== TRUE) 
    rmdir($path);
}
//-------------------------------------------------------------------------------------------------

function MyExec($filename)
{
 /*$descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("pipe", "w")
 );
 $process=proc_open($filename,$descriptorspec,$pipes);
 $buffer=stream_get_contents($pipes[1]);
 //$buffer.=stream_get_contents($pipes[2]);
 proc_close($process);*/
 $pipe=popen($filename,'r');
 //$buffer=stream_get_contents($pipe);
 $buffer=fread($pipe,1024);
 pclose($pipe);
 return ltrim($buffer);
}
//-------------------------------------------------------------------------------------------------

function StringToProxyContext($value,&$http_proxy_host,&$http_proxy_port,
                                     &$http_proxy_username,&$http_proxy_password)
{
 $http_proxy_host=false;$http_proxy_port=false;
 $http_proxy_username=false;$http_proxy_password=false;
 $hps=explode(':',$value);
 if(count($hps)<1)
    return NULL;
 $http_proxy_host=trim($hps[0]);
 $http_proxy_port=80;
 if(count($hps)>=2)
    $http_proxy_port=trim($hps[1]);
 if(count($hps)>=3)
    $http_proxy_username=trim($hps[2]);
 if(count($hps)>=4)
    $http_proxy_password=trim($hps[3]);
 $http_proxy_a=array(
                     'http' => array(
                                     'proxy' => 'tcp://'.$http_proxy_host.':'.$http_proxy_port,
                                     'request_fulluri' => True,
                                    ),
                    );
 if($http_proxy_username!=false)
   {
    $auth=base64_encode($http_proxy_username.':'.$http_proxy_password);
    $http_proxy_a['http']['header']="Proxy-Authorization: Basic $auth";
   }
 return stream_context_create($http_proxy_a);
}
//-------------------------------------------------------------------------------------------------

function DisabledFunction($function_name,&$df_map)
{
 if(array_key_exists($function_name,$df_map))
    return true;
 return false;
}
//-------------------------------------------------------------------------------------------------

function CheckFile($filename,&$df_map)
{
 $path_parts=pathinfo($filename);
 if(array_key_exists('extension',$path_parts) && strtolower($path_parts['extension'])!='php')
    return true;
 $cmd="";
 if(IsWindows())
    $cmd='"'.SDK_INTERNAL_PATH.'../php" -n -l "'.$filename.'"';
 else
    $cmd='php -n -l "'.$filename.'"';
 $ret=MyExec($cmd);
 if(substr($ret,0,9)!="No syntax")
   {
	echo "syntax check: ".$ret;
    return false;
   }
 $source=file_get_contents($filename);
 $tokens=token_get_all($source);
 $prepre_valid_token_index=-1;
 $pre_valid_token_index=-1;
 $call_stack=array();
 $tokens_number=count($tokens);
 for($i=0;$i<$tokens_number;$i++)
    {
     $token=$tokens[$i];
     if(is_array($token))
       {
        if($token[0]==T_WHITESPACE)
           continue;
       }
     else
       {
        $pt=&$tokens[$pre_valid_token_index];
        $ppt=$prepre_valid_token_index>=0?$tokens[$prepre_valid_token_index]:false;
        if($token=='(')
          {
           if(
              is_array($pt) && $pt[0]==T_STRING &&
              $ppt==false || is_array($ppt)==false ||( $ppt[0]!=T_FUNCTION && $ppt[0]!=T_OBJECT_OPERATOR)
             )
             {
              array_push($call_stack,$tokens[$pre_valid_token_index]);
              array_push($call_stack,'(');
             }
           else if(count($call_stack)>0)
              array_push($call_stack,'(');
          }
        else if($token==')' && count($call_stack)>0)
          {
           array_pop($call_stack);
           $tv=&$call_stack[count($call_stack)-1];
           if(is_array($tv) && $tv[0]==T_STRING)
             {
              if(DisabledFunction($tv[1],$df_map)==true)
                {
                 if(count($tv)>=3)
                    echo "function ".$tv[1]."() is forbidden for SAE security @ line ".$tv[2].", ".$filename."\n";
                 else
                    echo "function ".$tv[1]."() is forbidden for SAE security @ ".$filename."\n";
                 return false;
                }
              array_pop($call_stack);
             }
          }
       }
     $prepre_valid_token_index=$pre_valid_token_index;
     $pre_valid_token_index=$i;
    }//end for
 return true;
}
//-------------------------------------------------------------------------------------------------

function CheckSDKUpdate(&$link,$http_proxy=NULL)
{
 $content=false;
 $content=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=sdkversion',false,$http_proxy);
 if($content==false)
    return false;
 $wversion=0;
 $lversion=0;
 $c=explode("\n",$content);
 foreach($c as $cc)
        {
         if(strstr($cc,'Windows')!=false)
           {
            $ccc=explode("\t",$cc);
            $link=$ccc[0];
            $wversion=$ccc[1];
           }
         if(strstr($cc,'Linux')!=false)
           {
            $ccc=explode("\t",$cc);
            $link=$ccc[0];
            $lversion=$ccc[1];
           }
        }//end foreach
 if(strncmp(PHP_OS,'W',1)==0)
   {
    if(SDK_VERSION<$wversion)
       return $wversion;
   }
 else
   {
    if(SDK_VERSION<$lversion)
      {
       return $lversion;
      }
   }
 return false;
}

//-------------------------------------------------------------------------------------------------
//wanghai add
function getServerList(&$data,$appname,$version,$apppath,$email,&$password,$http_proxy=NULL)
{
    $token=false;
	$url=SAE_CODE_DEPOSITION_ADDRESS."?action=getFileList&name=$appname&version=$version&cookie=";
	if(file_exists(SDK_INTERNAL_PATH.'token') && GetFileGapTime(SDK_INTERNAL_PATH.'token')<=1440)
	  {
	   $token=file_get_contents(SDK_INTERNAL_PATH.'token');
	   touch(SDK_INTERNAL_PATH.'token');
	  }
	else
	  {
	   @unlink(SDK_INTERNAL_PATH.'token');
	   if($password===false || $password==='')
        {
         echo "Please type your password:\n";
         $password=ReadPassword();
        }
       $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
       if($token==false)
          PrintError("fail to get token",true);
       $tarray=explode("\n",$token);
       if(count($tarray)==2||$token=='')
         {
          PrintError("token format error ".$tarray[0],true);
          return false;
         }
       file_put_contents(SDK_INTERNAL_PATH.'token',$token);
	  }
	$url.=$token;
	$data=file_get_contents($url,false,$http_proxy);
	if(!$data)
		$data = array() ;
	   //PrintError("fail to get file list",true);
	$data=explode("\n",$data);
	if($data[0] === "0")
	  {
	   $data=unserialize($data[1]);
	   foreach($data as &$file)
				$file->filename=$apppath.'/'.$appname.'/'.$version.'/code/'.$file->filename;
	   return true;
	  }
 return true;
}
//add end
//-------------------------------------------------------------------------------------------------

function getServerFileList($appname,$version,$apppath,$email,$password,$http_proxy=NULL)
{
 $deploy_data_filename=$apppath.$appname.'/'.$version.'/deploy.dat';
 if(!file_exists($deploy_data_filename))
   {
    $token=false;
	$url=SAE_CODE_DEPOSITION_ADDRESS."?action=getFileList&name=$appname&version=$version&cookie=";
	if(file_exists(SDK_INTERNAL_PATH.'token') && GetFileGapTime(SDK_INTERNAL_PATH.'token')<=1440)
	  {
	   $token=file_get_contents(SDK_INTERNAL_PATH.'token');
	   touch(SDK_INTERNAL_PATH.'token');
	  }
	else
	  {
	   @unlink(SDK_INTERNAL_PATH.'token');
       $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
       if($token==false)
          PrintError("fail to get token",true);
       $tarray=explode("\n",$token);
       if(count($tarray)==2||$token=='')
         {
          PrintError("token format error ".$tarray[0],true);
          return false;
         }
       file_put_contents(SDK_INTERNAL_PATH.'token',$token);
	  }
	$url.=$token;
	$data=file_get_contents($url,false,$http_proxy);
	if(!$data)
	   PrintError("fail to get file list",true);
	$data=explode("\n",$data);
	if($data[0] === "0")
	  {
	   $data=unserialize($data[1]);
	   foreach($data as &$file)
				$file->filename=$apppath.$appname.'/'.$version.'/code/'.$file->filename;
	   file_put_contents($deploy_data_filename,serialize($data));
	   return true;
	  }
   }
 return true;
}
//-------------------------------------------------------------------------------------------------

function myCurl($url_a,$savePath,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password)
{
 $url=parse_url($url_a);
 $path=isset($url['path'])?$url['path']:'/';
 $query=isset($url['query'])?$url['query']:'';
 $host=isset($url['host'])?$url['host']:'';
 if($url['scheme'] === 'http')
    $port=isset($url['port'])?$url['port']:80;
 elseif($url['scheme'] === 'https')
    $port=443;
 $fp=false;
 if($http_proxy_host==false)
    $fp=fsockopen($url['host'],$port,$errno,$err,SAE_TCP_CONNECTION_TIMEOUT_SECS);
 else
    $fp=fsockopen($http_proxy_host,$http_proxy_port,$errno,$err,SAE_TCP_CONNECTION_TIMEOUT_SECS);
 if(!$fp)
    die("error connecting to the deploy server!");
 $header='';
 if($http_proxy_host==false)
   {
    $header="GET $path?$query HTTP/1.0\r\n";
    $header.="Host: $host\r\n";
   }
 else
   {
    $header="GET $url_a HTTP/1.0\r\n";
    if($http_proxy_username!=false)
       $header.='Proxy-Authorization: '.'Basic '.base64_encode($http_proxy_username.':'.$http_proxy_password)."\r\n";
   }
 $header.="Connection: close\r\n\r\n";
 //print_r($header);
 fwrite($fp,$header);
 $total=0;
 while(!feof($fp))
      {
       $s=fgets($fp);
       if(preg_match('/^Content-Length:(.+)$/',$s,$mat))
          $total=intval($mat[1]);
       else if(preg_match('/^\r\n$/',$s))
          break;
      }
 $download=0;
 if(file_exists($savePath))
    unlink($savePath);
 $h=fopen($savePath,"a+");
 $t=0;
 while(!feof($fp))
      {
       $s=fgets($fp);
       $download+=strlen($s);
       $percent=round(($download/$total)*100,2);
       $tmp=intval($percent);
       if($tmp>$t)
         {
          echo '.';
          $t=$tmp;
          if( $tmp%10 === 0 )
             echo intval($percent)."%";
         }
       fwrite($h,$s);
      }//end while
 echo "\r\n";
 fclose($h);
 fclose($fp);
 if($total>0&&$total>$download)
   {
    //echo $total."--".$download."\n";
    return false;
   }
 return true; 
}
//-------------------------------------------------------------------------------------------------

function getAppInfo($token, $appname,$proxyHost=null,$proxyPort=null,
                    $proxyUser=null,$proxyPass=null,&$errstr=null)
{
 $snoopy=new Snoopy();
 if($proxyHost!='')
   {
    $snoopy->proxy_host= $proxyHost;
    $snoopy->proxy_port= $proxyPort;
    if($proxyUser!='')
      {
       $snoopy->proxy_user= $proxyUser;
       $snoopy->proxy_pass= $proxyPass;
      }
   }
 $rv=$snoopy->fetch(SAE_CODE_DEPOSITION_ADDRESS."?action=appinfo&name=$appname&cookie=$token");
 if($rv==false)
   {
    $errstr=$snoopy->error;
    return false;
   }
 list($code,$message)=explode("\n",$snoopy->results, 2);
 if($code!=0)
   {
    $errstr=$message;
    return false;
   }
 $items=explode(" ",$message,5);
 return array("akey" => $items[1], "skey" => $items[2], "dbPort" => $items[3], "dbHost" => $items[4]);
}
//-------------------------------------------------------------------------------------------------

function find_unix_shell($shell)
{
 $unix_bin_path = explode(':', $_SERVER['PATH']);
 $unix_bin_path = array_merge( $unix_bin_path, array('/bin', '/sbin', '/usr/bin', '/usr/sbin') );
 foreach($unix_bin_path as $p)
        {
         if (file_exists($p.DIRECTORY_SEPARATOR.$shell)) return $p.DIRECTORY_SEPARATOR.$shell;
        }
 return false;
}
//-------------------------------------------------------------------------------------------------
?>
