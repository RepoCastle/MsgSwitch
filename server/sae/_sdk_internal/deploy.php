<?php
//-------------------------------------------------------------------------------------------------
//SAE SDK deploy client, SAE development team, 2009
//kobe, sprewellkobe@163.com
//http://t.sina.com.cn/kobe
//-------------------------------------------------------------------------------------------------
$fname=basename(__FILE__);
$p=realpath($argv[0]);
if(!defined('SDK_INTERNAL_PATH'))
   define('SDK_INTERNAL_PATH',substr($p,0,strrpos($p,$fname)));
require_once('core.function.php');
define('CHUNK_MAX_SIZE',4*1024*1024);
error_reporting(E_ALL^E_NOTICE);

//-------------------------------------------------------------------------------------------------
$email='';
$password=false;
$force=false;
$ignore=false;
$check=false;
$ufilename=false;
$silent=false;
$appname='';
$apppath='.';
$version=0;
$total_files_size=0;
$http_proxy=NULL;
$http_proxy_host=false;
$http_proxy_port=false;
$http_proxy_username=false;
$http_proxy_password=false;
//-----------------------------------------------
$appinfo=array();
$deploy_files=array();
//wanghai added
$ignore_code_consistent=false;
$version_consistent=true ;
//add end
//-------------------------------------------------------------------------------------------------

function DeployDisplayUsage()
{
 echo "
        ######        ###       ######## 
      ##    ##      ## ##      ##       
      ##           ##   ##     ##       
       ######     ##     ##    ######   
            ##    #########    ##       
      ##    ##    ##     ##    ##       
       ######     ##     ##    ########\n\n";
 echo 'Sina App Engine SDK '.SDK_VERSION.", power by sae.sina.com.cn\n";
 echo "-------------------------------------------------\n\n";
 echo "usage: php deploy.php appname -v version -e account [option]\n";
 echo "
appname
       the deploying app name

-v version
       the deploying code version

-e account
       the email address represent your account

OPTIONS
       -h     show help
       -f     force to deploy all app resource (without modify check)
	   -t     force to deploy code no matter code conflicts
       -p password    
              set password
       -i format 
              ignore all temp files whose ext filename format matched, *.*~|*.bak is default format
       -u filename
              deploy only one file
       -c     check sae php syntax
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -s silent
              without printing any information
       -a apppath
              set your apps path, current workdir is default path
AUTHORS
       Sina App Engine development team, 20090910
";
}
//-------------------------------------------------------------------------------------------------

function TrimAppPath(&$str)
{
 global $appname,$apppath,$version;
 $path=$apppath.'/';
 $path_len=strlen($path);
 if(substr($str,0,$path_len)==$path)
    return trim(substr($str,$path_len),"/");
 return $str;
}
//-------------------------------------------------------------------------------------------------

function SendAllFiles(&$add_files,&$delete_files,$token)
{
 global $password,$force,$appname,$appinfo,$deploy_files;
 global $silent,$version,$apppath,$total_files_size;
 global $http_proxy,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password;
 $begin=0;
 $end=0;
 $chunk_size=0;
 $total_files_size=0;
 $add_files_number=count($add_files);
 $result=true;
 $deploy_files_kv=array();
 foreach($deploy_files as $fp)
         $deploy_files_kv[$fp->filename]=$fp->md5;
  for(;$end<=$add_files_number;)
    {
     $os=false;
     if($end<$add_files_number)
       {
        $cfs=filesize($add_files[$end]->filename);
        if($chunk_size+$cfs<CHUNK_MAX_SIZE)
          {
           $chunk_size+=$cfs;
           $end++;
           continue;
          }
        else
           $os=true; 
       }
     if($end-$begin<1 && $os==true)
       {
        PrintError("uploading file ".$add_files[$begin]->filename." oversize");
        $result=false;
        break;
       }
     $cfiles=array();
     $ccfiles=array();
     if($silent==false)
        echo "sending...\n";
     for($i=$begin;$i<$end;$i++)
        {
         $fs=sprintf("%01.2f",filesize($add_files[$i]->filename)/1024);
         $total_files_size+=$fs;
         if($silent==false)
            echo $add_files[$i]->filename."\t".$fs."K\n";
         $item_name='f['.TrimAppPath($add_files[$i]->filename).']';
         $cfiles[$item_name]=$add_files[$i]->filename;
         $ccfiles[TrimAppPath($add_files[$i]->filename)]=$add_files[$i]->md5;
        }
     $json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
     $snoopy=new Snoopy();
     $snoopy->read_timeout=60;
     $snoopy->_fp_timeout=60;
     $snoopy->set_submit_multipart();
     if($http_proxy!=NULL)
       {
        $snoopy->proxy_host=$http_proxy_host;
        $snoopy->proxy_port=$http_proxy_port;
        if($http_proxy_username!=false)
           $snoopy->proxy_user=$http_proxy_username;
        if($http_proxy_password!=false)
           $snoopy->proxy_pass=$http_proxy_password;
       }
     $args['name'] = $appinfo['name'];
     $args['version']=$appinfo['version'];
     $args['cookie']=$token;
     $args['check']=$json->encode($ccfiles);
     $args['action']='upload';
     $args['force']=$force;
     $args['begin']=0;
     $args['end']=0;
     
     if($begin==0)
        $args['begin']=1;
     if($end==$add_files_number)
       {
        $args['end']=1;
        $cdfiles=array();
        foreach($delete_files as $fp)
               {
                $cdfiles[TrimAppPath($fp->filename)]=$fp->md5;
                unset($deploy_files_kv[$fp->filename]);
               }
        $args['delete']=$json->encode($cdfiles);
        $acfn='f[config.yaml]';
        $cfiles[$acfn]=$apppath.'/'.$appname."/".$version."/config.yaml";
        //echo "df:".print_r($cdfiles,true);
       }
     //echo "af:".print_r($cfiles,true);
     $snoopy->submit(SAE_CODE_DEPOSITION_ADDRESS,$args,$cfiles);
     if($snoopy->status==200 or $snoopy->status==401)
       {
		if(reset(explode("\n",$snoopy->results))!=='0')
		  {
           PrintError("deploy error:".$snoopy->status);
           print_r($snoopy->results);
           $result=false;
           if(strstr($snoopy->results,"login timed out")!=false)
              @unlink($token_filename=SDK_INTERNAL_PATH.'token'); 
           break;
		  }
		
		if($snoopy->results=='User not exists')
          {
           PrintError('username or password wrong',true);
           $result=false;
           break;
          }
        if($snoopy->results=='Not member')
          {
           PrintError('authentication error',true);
           $result=false;
           break;
          }
        if($silent==false)
            echo "done\n";
        for($i=$begin;$i<$end;$i++)
            $deploy_files_kv[$add_files[$i]->filename]=$add_files[$i]->md5;
        $begin=$end;
        $chunk_size=0;
        if($end==$add_files_number)
           break;
       }
    else
       {
        PrintError("fail to deploy:".$snoopy->status.','.$snoopy->error);
        //print_r($snoopy->results); 
		$result=false;
        break;
       }
    }//end for

 $deploy_files=array();
 foreach($deploy_files_kv as $k => $v)
         array_push($deploy_files,new FilePair($k,$v,filesize($k)));
 WriteDeployDat();
 return $result;
}
//-------------------------------------------------------------------------------------------------

function DeployCode(&$add_files,&$delete_files,$config_modified)
{
 global $appname,$email,$password,$silent,$time_start,$version,$http_proxy;
 if(count($add_files)==0 && count($delete_files)==0 && $config_modified==false)
   {
    if($silent==false)
       echo "Nothing to deploy\n";
    return true;
   }
 $token_filename=SDK_INTERNAL_PATH.'token';
 if(file_exists($token_filename) && GetFileGapTime($token_filename)<=1440)
   {
	$token=trim(file_get_contents($token_filename));
	touch($token_filename);
	$time_start=MicroTimeFloat();
   }
 else
   {
    $token='';
    if($password==false)
      {
       echo "Please type your password:\n";
       $password=ReadPassword();
      }
    $time_start=MicroTimeFloat();
    $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
    if($token==false)
      {
       PrintError('fail to get token');
       return false;
      }
    $tarray=explode("\n",$token);
    if(count($tarray)==2||$token=='')
      {
       PrintError("token format error ".$token);
       return false;
      }
    file_put_contents($token_filename,$token);
   }//end else
     return SendAllFiles($add_files,$delete_files,$token);
}
//-------------------------------------------------------------------------------------------------

function DeployGetopt($argv,&$opt)
{
 $argc=count($argv);
 if($argc<2)
    return false;
 if($argv[1]=='-h')
   {
    $opt['h']='';
    return true;
   }
 else if($argv[1][0]=='-')
    return false;
 $key='';
 $value='';
 for($i=2;$i<$argc;$i++)
    {
     $arg_length=strlen($argv[$i]);
     if($arg_length>1 && $argv[$i][0]=='-')
       {
        if($key!='')
          {
           $opt[$key]=trim($value,"\"'");
           $key='';
           $value='';
          }
        $key=substr($argv[$i],1,1);
        if($arg_length>2)
           $value=substr($argv[$i],2);
       }
     else if($key!='')
        $value.=$argv[$i];
    }
 if($key!='')
    $opt[$key]=trim($value,"\"'");
 return true;
}

//-------------------------------------------------------------------------------------------------
//wanghai added
function checkVersionChange()
{
 global $email,$password,$force,$appname,$app_config_filename,$appinfo;
 global $ignore,$silent,$version,$apppath,$ufilename,$check;
 global $http_proxy,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password;
 global $ignore_code_consistent,$version_consistent,$force,$deploy_files;	
 $server_deploy=false;
 $deploy_data=false;
 $version_consistent=true;
 getServerList($server_deploy,$appname,$version,$apppath,$email,$password,$http_proxy) ;
 GetDeployDat($deploy_data) ;
 usort($server_deploy,array("FilePair","cmp_obj"));
 usort($deploy_data,array("FilePair","cmp_obj"));
 for($i=0;$i<count($deploy_data);$i++)
 	{
	 $cfn=$appname."/".$version."/config.yaml";
	 $csl=strlen($cfn);
	 $sl=strlen($deploy_data[$i]->filename);
	 if($sl<$csl)
		continue;
	 if(strcmp(substr($deploy_data[$i]->filename,$sl-$csl,$csl),$cfn)==0)
		unset($deploy_data[$i]);
 	}
 if(count($server_deploy)==count($deploy_data))
   {
 	for($i=0;$i<count($server_deploy);$i++)
 	   {
 		if($deploy_data[$i]!=$server_deploy[$i])
 		  {
 		   $version_consistent=false ;
 		   break ;
 		  }	
 		}
   }
 else
    $version_consistent=false ;

 if($version_consistent==false) 
 	{
 	 echo "The current deploying code is older than the deployed code, do you want to continue to overwrite?(Y/n)
(if choose no, you can rename the app directory then download the lastest code to check)\n" ;
 	 $temp=strtolower(trim(fgets(STDIN)));
 	 if($temp=='y'||$temp=='Y')
 		$ignore_code_consistent=true;
 	 else
 		$ignore_code_consistent=false ;
 	}
/* else
   {
	$deploy_files = $server_deploy ;
	WriteDeployDat() ;
	$deploy_files = array() ;
   }*/
}
//-------------------------------------------------------------------------------------------------

function Prepare(&$argv)
{
 global $email,$password,$force,$appname,$app_config_filename,$appinfo;
 global $ignore,$silent,$version,$apppath,$ufilename,$check;
 global $http_proxy,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password;
 //wanghai added
 global $ignore_code_consistent,$version_consistent;
 //add end
 $opt=array();
 if(DeployGetopt($argv,$opt)==false)
   {
    PrintError('wrong arguments',false);
    DeployDisplayUsage();
    exit; 
   }
 foreach($opt as $key => $value)
        {
         switch($key)
               {
                case 'h':
                     DeployDisplayUsage();
                     exit;
                case 'e':
                     $email=$value;
                     break;
                case 'p':
                     $password=$value;
                     break;
                case 'f':
                     $force=true;
                     break;
				//wanghai added
				case 't':
					 $ignore_code_consistent=true;
					 break ;
				//add ended
				case 'v':
					 $version=$value;
					 break;
                case 'i':
                     $ignore=explode("|",$value);
                     break;
                case 'c':
                     $check=true;
                     break;
                case 'u':
                     $ufilename=$value;
                     break;
                case 's':
                     $silent=true;
                     break;
                case 'a':
                     $apppath=trim($value);
                     break;
                case 'x':
                     $http_proxy=StringToProxyContext($value,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password);
                     break;
                default:
                     break;
               }//end switch
        }//end foreach
 $appname=$argv[1];
 if(CheckAppName($appname)==false)
    PrintError('appname invalid',true);
 if(is_dir($apppath.'/'.$appname)==false)
    PrintError($apppath.'/'.$appname.' is not a legal dir',true);
 if($email=='')
   {
    PrintError('require -e option',false);
    DeployDisplayUsage();
    exit;
   }
 $app_config_filename=$apppath.'/'.$appname.'/'.$version.'/config.yaml';
 if(file_exists($app_config_filename)==false)
    PrintError($app_config_filename.' is not existed',true);
 if(!$appinfo=yaml($app_config_filename))
    PrintError('client '.$app_config_filename.' format error',true);
 if($appname!=$appinfo['name'])
    PrintError('appname in command line and in yaml are not same - '.$apppath.'/'.$appname.' != '.$appinfo['name'],true);
 if($version!=$appinfo['version'])
    PrintError('version in command line and in yaml are not same - '.$version.' != '.$appinfo['version'],true);
 if(CheckAppVersion($version)==false)
    PrintError('app version only support INT version number',true);
 //wanghai added
 if($ignore_code_consistent==false)
   {
    checkVersionChange();
	if($ignore_code_consistent==false&&$version_consistent==false)
	   return;
   }
 //add end
 if($force && file_exists($apppath.'/'.$appname.'/'.$version."/deploy.dat"))
    @unlink($apppath.'/'.$appname.'/'.$version."/deploy.dat");
 else if(!$force && !file_exists($apppath.'/'.$appname.'/'.$version."/deploy.dat"))
   {
    if($password===false || $password==='')
      {
       echo "Please type your password:\n";
       $password=ReadPassword();
      }
	getServerFileList($appname,$version,$apppath,$email,$password,$http_proxy);
   }
 if($ignore==false)
   {
    $ignore=array();
    array_push($ignore,"*.*~");
    array_push($ignore,"*.bak");
   }
}//end Prepare
//-------------------------------------------------------------------------------------------------

function GetDeployDat(&$deploy_data)
{
 global $appname,$version,$apppath;
 $deploy_dat_filename=$apppath.'/'.$appname.'/'.$version.'/deploy.dat';
 if(file_exists($deploy_dat_filename) && $fp=fopen($deploy_dat_filename,'r'))
   {
    $file_content=fread($fp,filesize($deploy_dat_filename));
    $deploy_data=unserialize($file_content);
   }//end if
 if($deploy_data==false)
    $deploy_data=array();
}
//-------------------------------------------------------------------------------------------------

function ReadDeployDat()
{
 global $appname,$deploy_files,$version,$apppath;
 $deploy_dat_filename=$apppath.'/'.$appname.'/'.$version.'/deploy.dat';
 $deploy_files=false;
 if(file_exists($deploy_dat_filename) && $fp=fopen($deploy_dat_filename,'r'))
   {
    $file_content=fread($fp,filesize($deploy_dat_filename));
    $deploy_files=unserialize($file_content);
   }//end if
 if($deploy_files==false)
    $deploy_files=array();
}
//-------------------------------------------------------------------------------------------------

function WriteDeployDat()
{
 global $appname,$app_config_filename,$deploy_files,$version,$apppath;
 array_push($deploy_files,new FilePair($apppath.'/'.$appname.'/'.$version.'/config.yaml',md5_file($app_config_filename),filesize($app_config_filename)));
 $file_content=serialize($deploy_files);
 $deploy_dat_filename=$apppath.'/'.$appname.'/'.$version.'/deploy.dat';
 $fp=fopen($deploy_dat_filename,'w');
 if($fp)
   {
    fwrite($fp,$file_content);
    fclose($fp);
   }
}
//-------------------------------------------------------------------------------------------------

function GetUFile(&$add_files,&$delete_files,&$config_modified)
{
 global $apppath,$appname,$version,$ufilename,$deploy_files;
 $config_modified=false;
 $filename="";

 $filename=$apppath.'/'.$appname.'/'.$version.'/code/'.$ufilename;

 $md5=md5_file($filename);
 array_push($add_files,new FilePair($filename,$md5,filesize($filename)));
 ReadDeployDat($deploy_files); 
}
//-------------------------------------------------------------------------------------------------

function GetFileList(&$add_files,&$delete_files,&$config_modified)
{
 global $password,$force,$appname,$app_config_filename;
 global $appinfo,$deploy_files,$ignore,$silent,$version,$apppath;
 $app_code_path=$apppath.'/'.$appname.'/'.$version.'/code';
 $current_files=array();
 VisitFilesByPath($app_code_path,$current_files,true,$ignore);
 usort($current_files,array("FilePair","cmp_obj"));
 ReadDeployDat($deploy_files);

 $i=0;
 $config_modified=true;
 $dc=count($deploy_files);
 for(;$i<$dc;$i++)
    {
     if(strcmp($deploy_files[$i]->filename,"config.yaml")==0)  //$apppath."/".$appname."/"."$version"."/".
       {
        if($deploy_files[$i]->md5==md5_file($app_config_filename))
           $config_modified=false;
        break;
       }
    }//end for 
 if($i!=$dc)
    unset($deploy_files[$i]);
    
// print_r($deploy_files) ;
 if($force)
    $config_modified=true;

 usort($deploy_files,array("FilePair","cmp_obj"));

 $current_files_index=0;
 $deploy_files_index=0;
 $current_files_length=count($current_files);
 $deploy_files_length=count($deploy_files);
 while($current_files_index<$current_files_length && $deploy_files_index<$deploy_files_length)
      {
      $ret=FilePair::cmp_obj($current_files[$current_files_index],$deploy_files[$deploy_files_index]);
      if($ret==0)
        {
         if($force==false && $current_files[$current_files_index]->md5!=$deploy_files[$deploy_files_index]->md5)
            array_push($add_files,$current_files[$current_files_index]);
         $current_files_index++;
         $deploy_files_index++;
        }
      else if($ret<0)
        {
         array_push($add_files,$current_files[$current_files_index]);
         $current_files_index++;
        }
      else
        {
         array_push($delete_files,$deploy_files[$deploy_files_index]);
         $deploy_files_index++;
        }
      }//end while
 if($current_files_index<$current_files_length)
   {
    for(;$current_files_index<$current_files_length;$current_files_index++)
        array_push($add_files,$current_files[$current_files_index]);
   }
 else if($deploy_files_index<$deploy_files_length)
   {
    for(;$deploy_files_index<$deploy_files_length;$deploy_files_index++)
        array_push($delete_files,$deploy_files[$deploy_files_index]);
   }
}
//-------------------------------------------------------------------------------------------------

function GetDisabledFunctionMap()
{
 global $http_proxy;
 $da='';
 $df_map=array();
 $da=explode("\n",file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=disabledfunc',false,$http_proxy));
 if($da==false)
    return $df_map;
 if(count($da)>=2)
   {
    $df=explode(',',$da[0]);
    foreach($df as $f)
            $df_map[$f]=1;
   }
 return $df_map;
}
//-------------------------------------------------------------------------------------------------

function checkDeploy($add_files,$delete_files)
{
 global $appinfo,$apppath,$force,$email,$password;
 global $http_proxy,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password;
 $token='';
 $add=array();
 $del=array();
 $trimPath=$apppath.'/'.$appinfo['name'].'/'.$appinfo['version'].'/code/';
 foreach($add_files as $k => $v)
	    {
		 $fn=substr($v->filename,strlen($trimPath));
		 $add[$fn]=new FilePair($fn,$v->md5,$v->size);
	    }
 foreach($delete_files as $k2 => $v2)
	    {
		 $fn=substr($v2->filename,strlen($trimPath));
		 $del[$fn]=new FilePair($fn,$v2->md5,$v2->size);
	    }
 if(file_exists(SDK_INTERNAL_PATH.'token')&& GetFileGapTime(SDK_INTERNAL_PATH.'token')<=1440)
   {
	$token=file_get_contents(SDK_INTERNAL_PATH.'token');
	touch(SDK_INTERNAL_PATH.'token');
   }
 else
   {
	@unlink(SDK_INTERNAL_PATH.'token');
    if($password==false /*&& IsWindows()==false*/)
      {
       echo "Please type your password:\n";
       $password=ReadPassword();
      }
    $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
    if($token==false)
       PrintError('fail to get token',true);
    $tarray=explode("\n",$token);
    if(count($tarray)==2||$token=='')
      {
       PrintError("token format error ".$token,true);
       return false;
      }
    file_put_contents(SDK_INTERNAL_PATH.'token',$token);
   }
 $snoopy = new Snoopy();
 $snoopy->read_timeout=60;
 $snoopy->_fp_timeout=60;
 $args['name'] = $appinfo['name'];
 $args['version']=$appinfo['version'];
 $args['cookie']=$token;
 $args['action']='checkDeploy';
 $args['force']=$force;
 $args['addList']=serialize($add);
 $args['delList']=serialize($del);
 if($http_proxy!=NULL)
   {
    $snoopy->proxy_host=$http_proxy_host;
    $snoopy->proxy_port=$http_proxy_port;
    if($http_proxy_username!=false)
       $snoopy->proxy_user=$http_proxy_username;
    if($http_proxy_password!=false)
       $snoopy->proxy_pass=$http_proxy_password;
   }
 $snoopy->submit(SAE_CODE_DEPOSITION_ADDRESS,$args);
 if($snoopy->status == 200 || $snoopy->status == 401)
   {
	$dat=$snoopy->results;
	if($dat)
	  {
	   $dat=explode("\n",$dat);
	   if($dat[0] !== "0")
		  PrintError($snoopy->results,true);
		else
		  return true;
	  }
	else
		PrintError('Empty response from server.'.$snoopy->results,true);
   }
 return $snoopy->status;	
}
//-------------------------------------------------------------------------------------------------

function main(&$argv)
{
 global $password,$force,$appinfo,$silent,$time_start;
 global $total_files_size,$appname,$version,$ufilename,$check;
 global $version_consistent,$ignore_code_consistent;
 Prepare($argv);
 //wanghai added
 if($ignore_code_consistent==false&&$version_consistent==false)
   {
	echo "\nFinished! The code not be deployed, because the server-side code maybe newer than your code\n" ;
	return;
   }
 //add end
 $add_files=array();
 $delete_files=array();
 $config_modified=true;
 if($ufilename==false)
     GetFileList($add_files,$delete_files,$config_modified);
 else
     GetUFile($add_files,$delete_files,$config_modified);
 
 $checkDeployResult = checkDeploy($add_files,$delete_files);
 if(true!==$checkDeployResult)
     PrintError("check deploy error!".$checkDeployResult,true);

 if($check)
   {
    $df_map=GetDisabledFunctionMap();
    foreach($add_files as $af)
           {
            if(CheckFile($af->filename,$df_map)==false)
               return false;
           }
   } 
 $ret=DeployCode($add_files,$delete_files,$config_modified);
 $time_end=MicroTimeFloat();
 $time=$time_end-$time_start;
 if($silent==false)
   {
    echo "\n";
    if($ret)
      {
       if($total_files_size<=1536)
          echo "Total deployed filesize ".sprintf("%01.2f",$total_files_size)."K";
       else
          echo "Total deployed filesize ".sprintf("%01.2f",$total_files_size/1024)."M";
	   if(isset($time_start))
	      echo ", timecost ".sprintf("%01.2f",$time)." seconds\n";
       else
          echo "\n";
	   echo "\nFinished! Deploy successful, Please visit http://".$version.".".$appname.".sinaapp.com\n";
      }
    else
       echo "\nSorry... Deploy unsuccessful\n";
   }
 else
   {
    if($ret)
       echo "true\n";
    else
       echo "false\n";
   }
}
//-------------------------------------------------------------------------------------------------
main($argv);
?>
