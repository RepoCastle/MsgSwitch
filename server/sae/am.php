<?php
//Sina App Engine Development Team, 2009.10
//kobe, sprewellkobe@163.com
//http://t.sina.com.cn/kobe
//-------------------------------------------------------------------------------------------------
$fname=basename(__FILE__);
$p=realpath($argv[0]);
define('ROOT',substr($p,0,strrpos($p,$fname)));
define('SDK_INTERNAL_PATH',ROOT.'/_sdk_internal/');
require_once(SDK_INTERNAL_PATH.'core.function.php');
//-------------------------------------------------------------------------------------------------

function AmDisplayUsage()
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
 echo "usage: php am.php <subcommand> [args]\n";
 echo "
SUBCOMMANDS:

    create [args]
        create a new app, for more args, php am.php create -h     
    upload [args]
        upload a existed app to SAE, for more args, php am.php upload -h
    download [args]
        download a deployed app from SAE, for more args, php am.php download -h
    downloadall [args]
        download all deployed apps from SAE, for more args, php am.php downloadall -h
    bigfileupload [args]
        upload big file to storage engine, for more args, php am.php bigfileupload -h
    account
        clear account information 
    quick
        repeat last action
    upgrade [args]
        upgrade sdk if new version exists
    diagnose [args]
        diagnose environment if facing connection problem, for more args, php am.php diagnose -h
    help
        show help information

AUTHORS
        Sina App Engine development team, 20090910
"; 
}
//-------------------------------------------------------------------------------------------------

function AmGetopt($argv,&$subcommand,&$args)
{
 $argc=count($argv);
 if($argc<2)
    return false;
 $subcommand=$argv[1];
 $key='';
 $value='';
 for($i=2;$i<$argc;$i++)
    {
     $arg_length=strlen($argv[$i]);
     if($arg_length>1 && $argv[$i][0]=='-')
       {
        if($key!='')
          {
           $args[$key]=trim($value,"\"'");
           $key='';
           $value='';
          }
        $key=substr($argv[$i],1,1);
        if($arg_length>2)
           $value=substr($argv[$i],2);
       }
     else if($key!='')
        $value.=trim($argv[$i],"\"'");
    }
 if($key!='')
    $args[$key]=trim($value,"\"'");
 return true;
}
//-------------------------------------------------------------------------------------------------

function GetAppNameFromList()
{
 $ls='';
 $plist=array();
 foreach(glob(ROOT.'apps/*',GLOB_ONLYDIR) as $k => $p)
        {
         $j=$k+1;
         $plist[$j]=$p=basename($p);
         $ls.=$j.' => '.$p."\n";
        }
 if(count($plist)<=0)
    PrintError('there is no app exists, please create one first',true);
 elseif(count($plist)==1)
   {
    $num=1;
    echo 'Upload app '.$plist[1]."\n";
   }
 else
   {
    echo 'Please enter the app number you want to upload: '."\n";
    echo $ls;
    $num=trim(fgets(STDIN));
   }
 if(!isset($plist[$num]))
    PrintError('app you assgined not existed',true);
 return $plist[$num];
}
//------------------------------------------------------------------------------------------------

function GetAppVersionFromList($appname)
{
 $plist=array();
 $ls='';
 $appversion=1.0;
 foreach(glob(ROOT.'apps/'.$appname.'/*',GLOB_ONLYDIR) as $k => $p)
        {
         $j=$k+1;
         $plist[$j]=$p=basename($p);
         $ls.=$p."\n";
        }
 if(count($plist)<=0)
   {
    PrintError('there is no appversion exists, please create one first',true);
    exit;
   }
 elseif(count($plist)==1)
   {
    $num=1;
    echo 'Using appversion '.$plist[1]."\n";
    $appversion=intval($plist[1]);
   }
 else
   {
    echo 'Please enter the appversion: '."\n";
    echo $ls;
    $appversion=trim(fgets(STDIN));
   }
 return $appversion;
}
//------------------------------------------------------------------------------------------------

function GetAppList(&$array)
{
 if(is_array($array)==false)
    return false;
 if(array_key_exists('data',$array)==false)
    return false;
 $applist=array();
 foreach($array['data'] as $key => $value)
        {
         $appname=$key;
         if(is_array($value) && array_key_exists('versions',$value))
           {
            $versions=$value['versions'];
            foreach($versions as $v=>$i)
                    array_push($applist,new AppInfor($appname,$v));
           }
        }//end foreach
 return $applist;
}
//------------------------------------------------------------------------------------------------

function Create($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php create [args]\n";
 echo "
ARGS
       -n appname
          set app name
       -v appversion
          set app version
       -a apppath
          set your apps base, current workdir is default
       -i
          with a example helloworld in index.php
       -c
          with a example cron in config.yaml\n";
    return;
   }
 $appname='';
 if(array_key_exists('n',$args)==false)
   {
    echo 'Please enter your appname: ';
    $appname=basename(trim(fgets(STDIN)));
   }
 else
    $appname=$args['n'];
 if(CheckAppName($appname)==false)
    PrintError("appname invalid",true);
 $appversion=1.0;
 if(array_key_exists('v',$args)==false)
   {
    echo 'Please enter your appversion: ';
    $appversion=trim(fgets(STDIN));
   }
 else
    $appversion=$args['v'];
 if(CheckAppVersion($appversion)==false)
    PrintError('appversion only support INT number',true);
 $appbase=ROOT.'apps/';
 if(array_key_exists('a',$args)==true)
   {
    $appbase=trim($args['a']);
    if(is_dir($appbase)==false)
       PrintError("apppath not exists ".$appbase,true);
    else
      {
       if($appbase[strlen($appbase)-1]!='/'||$appbase[strlen($appbase)-1]!='\\')
          $appbase.='/';
      }
   }
 
 $folder=$appbase.$appname.'/'.$appversion;
 if(file_exists($folder))
    PrintError('folder '.$folder.' already existed!',true);
 
 @mkdir($folder,0755,true);
 $code_folder=$folder.'/code/';
 @mkdir($code_folder,0755,true);
 if(array_key_exists('i',$args) && !file_exists($code_folder.'index.php'))
   {
    $index_php_data="<?php
echo 'hello world!';
?>";
    file_put_contents($code_folder.'index.php',$index_php_data);
   }
 if(!file_exists($folder.'/config.yaml'))
   {
    $yaml_data="name: {$appname}
version: {$appversion}";
    if(array_key_exists('c',$args)==true)
      {
       $yaml_data.="\ncron:
    - description: cron test
      url: index.php
      schedule: every 43 mins
      timezone: Beijing";
      }
    else
      {
       $yaml_data.="\n#cron:
#    - description: cron test
#      url: index.php
#      schedule: every 43 mins
#      timezone: Beijing";
      }
    file_put_contents($folder.'/config.yaml',$yaml_data);
    echo "\n\n".'Finished!'."\n";
   }
 else
    echo "\n\n".'Finished!'."\n";
 sleep(1);
}
//-------------------------------------------------------------------------------------------------

function Upload($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php upload [args]\n";
 echo "
ARGS
       -e account
          set email account
       -p password
          set password
       -n appname
          set app name
       -a apppath
          set your apps base, current workdir is default
       -v appversion
          set app version

	   -t 0|1
	      -t1 to force to deploy code no matter code conflicts
       -f 0|1 
          -f1 upload all files (-f0 only modified files)
       -u filename
          upload specific file
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -c 
          sae php syntax check\n";
    return;
   }
 $appname='';
 if(array_key_exists('n',$args)==false)
    $appname=GetAppNameFromList();
 else
    $appname=$args['n'];
 if(CheckAppName($appname)==false)
    PrintError('appname invalid',true); 
 
 $appversion=1.0;
 if(array_key_exists('v',$args)==false)
    $appversion=GetAppVersionFromList($appname);
 else
    $appversion=$args['v'];
 if(CheckAppVersion($appversion)==false)
    PrintError('appversion only support INT number',true); 
 $email_filename=SDK_INTERNAL_PATH.'email';
 $last_filename=SDK_INTERNAL_PATH.'last';
 if(array_key_exists('e',$args)==true)
    define('EMAIL',$args['e']);
 else
   {
    set_time_limit(0);
    if(!file_exists($email_filename))
      {
       echo 'Please enter your email account: ';
       $email=trim(fgets(STDIN));
       define('EMAIL',$email);
       file_put_contents($email_filename,$email);
       echo 'Your email saved in '.$email_filename.", run account command if you want to change to another\n\n";
      }
    else
       define('EMAIL',file_get_contents($email_filename));
   }
   
 //wanghai add
 $forceversion = '' ;
 if(array_key_exists('t',$args)==true)
 {
	 if($args['t'] == '1')
	   $forceversion = 'y' ;
	 else
	   $forceversion = 'n' ;
 }
 else
 {
	 $forceversion = 'n' ;
 } //add end
 
 $notforce='';
 if(array_key_exists('u',$args)==true)
   $notforce='Y';
 else if(array_key_exists('f',$args)==true)
   {
    if($args["f"]=='1')
       $notforce='n';
    else
       $notforce='';
   }
 else
   {
    echo 'Only upload modified files? (Y/n) ';
    $notforce=strtolower(trim(fgets(STDIN)));
   }
 
 $apppath=ROOT.'apps';
 if(array_key_exists('a',$args)==true)
    $apppath=trim($args['a']);
 $phpexe='';
 if(IsWindows())
    $phpexe='"'.ROOT.'/php"';
 else
    $phpexe='php';
 $cmd=$phpexe.' -n "'.SDK_INTERNAL_PATH.'deploy.php" '.$appname.' -v'.$appversion.' -e'.EMAIL;

 if(array_key_exists('p',$args)==true)
   $cmd.=' -p"'.$args['p'].'"';
 $cmd.=' -a" '.$apppath.' "';

 //wanghai add

 if($forceversion=='y')
   $cmd.=' -t';
 //add end

 if($notforce=='n')
   $cmd.=' -f';
 if(array_key_exists('c',$args)==true)
   $cmd.=' -c';
 if(array_key_exists('u',$args)==true)
   $cmd.=' -u'.$args['u'];
 if(array_key_exists('x',$args)==true)
   $cmd.=' -x'.$args['x'];
 file_put_contents($last_filename,$cmd);
 passthru($cmd);
}
//-------------------------------------------------------------------------------------------------

function Download($args,$nocontinue=true)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php download [args]\n";
 echo "
ARGS
       -e account
          set email account
       -p password
          set password
       -n appname
          set app name
       -a apppath
          set your apps base, current workdir is default
       -v appversion
          set app version
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -o
          overwrite existed files (default is no)\n";
    return;
   }
 $appname='';
 if(array_key_exists('n',$args)==false)
   {
    echo('Please enter the appname you want to download: ');
    $appname=trim(fgets(STDIN));
   }
 else
    $appname=$args['n'];
 if(CheckAppName($appname)==false)
    PrintError('appname invalid',true);
 $appversion=1.0;
 if(array_key_exists('v',$args)==false)
   {
    echo 'Please enter appversion: ';
    $appversion=trim(fgets(STDIN));
   }
 else
    $appversion=$args['v'];
 if(CheckAppVersion($appversion)==false)
    PrintError('appversion only support INT number',true);

 $token_filename=SDK_INTERNAL_PATH.'token';
 $email_filename=SDK_INTERNAL_PATH.'email';
 $token='';
 $email=false;
 $password=false;
 $http_proxy=NULL;
 $http_proxy_host='';
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 if(file_exists($token_filename) && GetFileGapTime($token_filename)<=1440)
   {
    $token=trim(file_get_contents($token_filename)); 
    touch($token_filename);
   }
 else
   {
    if(array_key_exists('e',$args)==true)
       $email=$args['e'];
    else if(file_exists($email_filename)==true)
       $email=trim(file_get_contents($email_filename));
    else
	  {
	   echo 'Please enter your email account of app '.$appname.': ';
	   $email=trim(fgets(STDIN));
	  }

    if(array_key_exists('p',$args)==true)
       $password=$args['p'];
    else
	  {
	   echo "Please type your password: ";
	   $password=ReadPassword();
	  }
    $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
    if($token==false)
       PrintError('fail to get token',true);
    $tarray=explode("\n",$token);
    if(count($tarray)==2||$token=='')
	   PrintError('token format error '.$token,true);
    file_put_contents($token_filename,$token);
    file_put_contents($email_filename,$email);
   }//end else
 echo 'Downloading '.$appname." ...\n";
  
 $file_save_path=ROOT.'tmp/'.$appname.'/';
 $file_full_name=$file_save_path.$appversion.'.zip' ;
 $config_full_name=$file_save_path.$appversion.'.config.yaml' ;
 @mkdir($file_save_path ,0755,true);
 
 $rurl=SAE_CODE_DEPOSITION_ADDRESS.'?action=download&name='.$appname.'&version='.$appversion.'&cookie='.$token;
 $rv=@myCurl($rurl,$file_full_name,$http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password);
 if($rv==false)
   {
    PrintError("download uncompleted");
    return;
   }
 
 $fp=fopen($file_full_name,'r');
 $s=fgets($fp);
 fclose($fp);
 if(strncmp('PK',$s,2) !== 0 )
   {
    $sa=explode("\n",$s);
    if($sa[0]=="1112")
      {
       @unlink($token_filename);
       PrintError("download failed ".$s.', token timeout, please redownload',$nocontinue);
       if($nocontinue==false) return false;
      }
    else
      {
       PrintError("download failed ".$s.', please upload your code before download',$nocontinue);
       if($nocontinue==false) return false;
      }
   }
 $config_content=NULL;
 $config_content=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=config&name='.$appname.'&version='.$appversion.'&cookie='.$token,
                                       false,$http_proxy);
 if($config_content==false)
    PrintError('fail to get config',$nocontinue);
 $config_content=explode("\n",$config_content,2);
 if($config_content[0]==0)
    file_put_contents($config_full_name,$config_content[1]);
 else
    PrintWarning('config format error '.$config_content[0]);

 $apppath=ROOT.'apps/';
 if(array_key_exists('a',$args)==true)
   {
    $apppath=Trim($args['a']);
    if(is_dir($apppath)==false)
       PrintError("apppath not exists ".$apppath,true);
    else
      {
       if($apppath[strlen($apppath)-1]!='/'||$apppath[strlen($apppath)-1]!='\\')
          $apppath.='/';
      }
   }
 $app_dir=$apppath.$appname.'/'.$appversion.'/';
 $app_code_dir=$app_dir.'code/';

 @mkdir($app_code_dir,0755,true);

 $overwrite=false;
 if(count(glob($app_code_dir.'/*'))>0)
   {
    if(array_key_exists('o',$args)==false)
      {
       echo 'Code folder '.$app_code_dir.' not empty, overwrite ?(Y/n)';
       if(strtolower(trim(fgets(STDIN)))!='n')
	      $overwrite=true;
	   else
	      $overwrite=false;
      }
    $overwrite=true;
   }
 else
   $overwrite=true;

 if($overwrite)
   {
    echo "Downloaded, unziping ...\n";
    require_once(SDK_INTERNAL_PATH.'dUnzip2.inc.php');
	$zip = new dUnzip2($file_full_name);
	$zip->debug = false; 
	$zip->unzipAll($app_code_dir);
    if(file_exists( $config_full_name )) copy($config_full_name,$app_dir.'config.yaml');
	   echo 'Finished! code unziped to '.$app_dir."\n";
   }
 else
    echo "Downloaded\n".'Finished! Zip file saved to '.$file_save_path."\n";
	
 //$apppath=ROOT.'apps';
 getServerFileList($appname,$appversion,$apppath,$email,$password,$http_proxy);
}
//-------------------------------------------------------------------------------------------------

function DownloadAll($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php downloadall [args]\n";
 echo "
ARGS
       -e account
          set email account
       -p password
          set password
       -a apppath
          set your apps base, current workdir is default
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -o
          overwrite existed files (default is no)\n";
    return;
   }
 $token_filename=SDK_INTERNAL_PATH.'token';
 $email_filename=SDK_INTERNAL_PATH.'email';
 $token='';
 $email=false;
 $password=false;
 $http_proxy=NULL;
 $http_proxy_host='';
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 if(array_key_exists('e',$args)==true)
    $email=$args['e'];
 else if(file_exists($email_filename)==true)
    $email=file_get_contents(trim($email_filename));
 else
   {
    echo 'Please enter your email account: ';
    $email=trim(fgets(STDIN));
   }
 if(array_key_exists('p',$args)==true)
    $password=trim($args['p']);

 if(file_exists($token_filename) && GetFileGapTime($token_filename)<=1440)
   {
    $token=trim(file_get_contents($token_filename));
    touch($token_filename);
   }
 else
   {
    if($token=='' && $password==false)
      {
       echo "Please type your password: ";
       $password=ReadPassword();
      }
    $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
    if($token==false)
       PrintError('fail to get token',true);
    $tarray=explode("\n",$token);
    if(count($tarray)==2||$token=='')
       PrintError('token format error '.$token,true);
    file_put_contents($token_filename,$token);
    file_put_contents($email_filename,$email);
   }
 $json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
 $url=SAE_CODE_DEPOSITION_ADDRESS.'?action=applist&email='.$email.'&cookie='.$token;
 $retstr=false;
 $retstr=file_get_contents($url,false,$http_proxy);
 if($retstr==false)
    PrintError('fail to get app list',true);
 $retstr=$json->decode($retstr);
 $applist=GetAppList($retstr);
 if(is_array($applist)==false)
    PrintError('app list format error '.$applist,true);
 $myargs=array();
 foreach($applist as $appinfor)
        {
         $myargs['n']=$appinfor->appname;
         $myargs['v']=$appinfor->version;
         if(array_key_exists('e',$args)==true)
            $myargs['e']=$args['e'];
         if(array_key_exists('p',$args)==true)
            $myargs['p']=$args['p'];
         if($email!=false)
            $myargs['e']=$email;
         if($password!=false)
            $myargs['p']=$password;
         if(array_key_exists('o',$args)==true)
            $myargs['o']=$args['o'];
         if(array_key_exists('x',$args)==true)
            $myargs['x']=$args['x'];
         if(array_key_exists('a',$args)==true)
            $myargs['a']=$args['a'];
         Download($myargs,false);
         echo "\n";
        } 
}
//-------------------------------------------------------------------------------------------------

function BigFileUpload($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php bigfileupload [args]\n";
 echo "
ARGS
       -e account
          set email account
       -p password
          set password
       -n appname
          set app name
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -f filename
          set local big file filename
       -s storengine
          stor => SAE stor(as default), s3 => Sina Simple Storage
       -d domain
          set storage domain\n";
    return;
   }
 $token_filename=SDK_INTERNAL_PATH.'token';
 $email_filename=SDK_INTERNAL_PATH.'email';
 $appname='';
 $email='';
 $password='';
 $token='';
 $bigfilename='';
 $http_proxy=NULL;
 $http_proxy_host='';
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 if(array_key_exists('e',$args)==true)
    $email=$args['e'];
 else if(file_exists($email_filename)==true)
    $email=file_get_contents(trim($email_filename));
 else
   {
    echo 'Please enter your email account: ';
    $email=trim(fgets(STDIN));
   }
 if(array_key_exists('p',$args)==true)
    $password=trim($args['p']);

 if(file_exists($token_filename) && GetFileGapTime($token_filename)<=1440)
   {
    $token=trim(file_get_contents($token_filename));
    touch($token_filename);
   }
 else
   {
    if($token=='' && $password==false)
      {
       echo "Please type your password: ";
       $password=ReadPassword();
      }
    $token=file_get_contents(SAE_CODE_DEPOSITION_ADDRESS.'?action=auth&email='.$email.'&password='.urlencode($password),false,$http_proxy);
    if($token==false)
       PrintError('fail to get token',true);
    $tarray=explode("\n",$token);
    if(count($tarray)==2||$token=='')
       PrintError('token format error '.$token,true);
    file_put_contents($token_filename,$token);
    file_put_contents($email_filename,$email);
   }
 if(array_key_exists('n',$args)==false)
   {
    echo('Please enter the appname: ');
    $appname=trim(fgets(STDIN));
   }
 else
    $appname=$args['n'];

 $errormsg='';
 $ra=getAppInfo($token,$appname,$http_proxy_host,$http_proxy_port,
                $http_proxy_username,$http_proxy_password,$errormsg);
 if($ra==false)
    PrintError('fail to get app info,'.$errormsg,true);
 
 $extra=new FUploadExtra();
 if(array_key_exists('f',$args)==true)
    $bigfilename=$args['f'];
 else
   {
    echo 'Please enter big file filename: ';
    $bigfilename=trim(fgets(STDIN));
   }
 if(file_exists($bigfilename)==false)
   {
    echo $bigfilename." not exists\n";
    return;
   }
 $sengine='';
 if(array_key_exists('s',$args)==true)
    $extra->storengine=$args['s'];    
 else
   {
    echo "Please choose storengine number:\n";
    $se=array();
    $se['1']='stor';
    $se['2']='s3';
    foreach($se as $key=>$value)
           {
            echo $key.' => '.$value."\n";
           }
    $index=trim(fgets(STDIN));
    if(array_key_exists($index,$se)==false)
       $index='1';
    $sengine=$se[$index];
    $extra->storengine($sengine);
   }
 if(array_key_exists('d',$args)==true)
    $extra->domain($appname.'-'.$args['d']);
 else
   {
    echo 'Please enter domain: ';
    $extra->domain($appname.'-'.trim(fgets(STDIN)));
   } 

 echo "uploading ".$bigfilename.' into '.$sengine."\n";
 $uploader=new FUpload($ra['akey'],$ra['skey']);
 $uploader->setChunkSize(1024*256);
 $uploader->setProgressOn();
 $uploader->setProgressStep(5);
 if($http_proxy!=NULL)
    $uploader->setProxy($http_proxy_host,$http_proxy_port,$http_proxy_username,$http_proxy_password);
 if(!$uploader->upload($bigfilename,$extra->toString(),true))
    echo $uploader->errno(). " ". $uploader->error(). "\n";
 else 
    echo $bigfilename." upload finished!\n";
}//end function bigfileupload
//-------------------------------------------------------------------------------------------------

function Upgrade($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php upgrade [args]\n";
 echo "
ARGS
       -d 
          only download updated files, not replace the existed files
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
      ";
    return;
   }
 $downloadpath=ROOT.'tmp/download/';
 if(is_dir($downloadpath))
    removeDir($downloadpath);
 $succ=true;
 $http_proxy=NULL;
 $http_proxy_host='';
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 $link='';
 $cu=CheckSDKUpdate($link,$http_proxy);
 if($cu==false)
   {
    echo "the SDK you using is lastest version\n\n";
    return true;
   }
 else
    echo 'SDK '.$cu." available, start upgrading\n\n";
 $url='http://xhprof.tools.sinaapp.com/sdk_upgrade/?os=';
 if(IsWindows()==false)
    $url.='linux&act=list';
 else
    $url.='windows&act=list';
 $files=false;

 $content=file_get_contents($url,false,$http_proxy);
 $files=explode("\n",$content);
 foreach($files as &$file)
        {
         $file=explode("\t",$file);
         array_push($file,0);
        }
 foreach($files as &$file)
        {
         if(is_file(ROOT.$file[0])==false||md5_file(ROOT.$file[0])!=$file[2])
            $file[5]=1;
        }
 foreach($files as &$file)
        {
         if($file[5]==1)
           {
            $dl=file_get_contents($file[3],false,$http_proxy);
            if(md5($dl)!=$file[2])
              {
               $succ=false;
               break;
              }
            $dirn=dirname($downloadpath.$file[0]);
            if(is_dir($dirn)==false)
               mkdir_r($dirn);
            file_put_contents($downloadpath.$file[0],$dl);
            if(filesize($downloadpath.$file[0])!=$file[4])
              {
               $succ=false;
               break;
              }
            echo 'downloaded '.$downloadpath.$file[0].' '.$file[4]." bytes\n";
           }
        }//end foreach
 if($succ==false)
   {
    removeDir($downloadpath);
    echo "\nupgrade unsuccessfully\n";
    return false;
   }
 echo "download successfully\n";
 if(array_key_exists('d',$args)==true)
    return true;
 foreach($files as &$file)
        {
         if($file[5]!=1)
            continue;
         $dirn=dirname(ROOT.$file[0]);
         if(is_dir($dirn)==false)
            mkdir_r($dirn);
         copy($downloadpath.$file[0],ROOT.$file[0]);
        }//end foreach
 removeDir($downloadpath);
 $succ=true;
 echo "\nupgraded successfully\n";
 return true; 
}
//-------------------------------------------------------------------------------------------------

function DiagnoseNew($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php diagnose [args]\n";
 echo "
ARGS
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234
       -e account
          set email account
       -n appname
          set app name\n";
    return;
   }
 $email='';
 $appname='';
 if(array_key_exists('e',$args)==true)
    $email=$args['e'];
 if(array_key_exists('n',$args)==true)
    $appname=$args['n'];
 echo "Diagnosing network connection...\n\n";
 $http_proxy=NULL;
 $http_proxy_host=false;
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 $host = 'sae.sina.com.cn';
 $download_url = 'http://diag.sae.sina.com.cn/download_test.zip';
 $tmp_path = ROOT."tmp/";
 $tmp_file = tempnam($tmp_path, 'sae_diagnose_df');
 $http_proxy_host=false;
$http_proxy_port=0;
$http_proxy_username='';
$http_proxy_password='';

$result = array();	//用来保存诊断结果的数组

 if(IsWindows()) 
   {
	$pingshell = 'ping '.$host;
	$tracertshell = 'tracert '.$host;
	$ifcfgshell = 'ipconfig /all';
   } 
 else
   {
	$pingshell = find_unix_shell('ping') . ' ' . $host . ' -c 5';
	$ifcfgshell = find_unix_shell('ifconfig') . ' -a';
	$tracertshell = find_unix_shell('traceroute');
	if ($tracertshell === false) $tracertshell = find_unix_shell('tracepath');
   }

$result['OS_TYPE'] = PHP_OS;
if (file_exists(SDK_INTERNAL_PATH.'email'))
	$result['email'] = trim(file_get_contents(SDK_INTERNAL_PATH.'email'));

$time_start = microtime(1);
echo "[OS]\n";
$ret = php_uname();
print_r($ret);
echo "\n";
$time_end = microtime(1);
$lasted_time = $time_end - $time_start;
$result['OS']['value'] = print_r($ret, true);
$result['OS']['lasted'] = $lasted_time;
echo "lasted time: " . $lasted_time . "\n\n";

$time_start = microtime(1);
echo "[ipconfig]\n";
exec($ifcfgshell, $ret);
$ret = join("\n", $ret);
print_r($ret);
echo "\n";
$time_end = microtime(1);
$lasted_time = $time_end - $time_start;
$result['ipconfig']['value'] = print_r($ret, true);
$result['ipconfig']['lasted'] = $lasted_time;
echo "lasted time: " . $lasted_time . "\n\n";

 $time_start = microtime(1);
 echo "[gethostbyname]\n";
 $ret = gethostbynamel($host);
 $ret = join(',', $ret);
 print_r($ret);
 echo "\n";
 $time_end = microtime(1);
 $lasted_time = $time_end - $time_start;
 $result['gethostbyname']['value'] = print_r($ret, true);
 $result['gethostbyname']['lasted'] = $lasted_time;
 echo "lasted time: " . $lasted_time . "\n\n";

 $time_start = microtime(1);
 echo "[ping]\n";
 exec($pingshell, $ret);
 $ret = join("\n", $ret);
 print_r($ret);
 echo "\n";
 $time_end = microtime(1);
 $lasted_time = $time_end - $time_start;
 $result['ping']['value'] = print_r($ret, true);
 $result['ping']['lasted'] = $lasted_time;
 echo "lasted time: " . $lasted_time . "\n\n";

 if($tracertshell != false)
   {
	$time_start = microtime(1);
	echo "[traceroute]\n";
	exec($tracertshell . ' ' . $host, $ret);
	$ret = join("\n", $ret);
	print_r($ret);
	echo "\n";
	$time_end = microtime(1);
	$lasted_time = $time_end - $time_start;
	$result['traceroute']['value'] = print_r($ret, true);
	$result['traceroute']['lasted'] = $lasted_time;
	echo "lasted time: " . $lasted_time . "\n\n";
   }

 $time_start = microtime(1);
 echo "[download]\n";
 $crv=myCurl($download_url, $tmp_file, $http_proxy_host, $http_proxy_port, $http_proxy_username, $http_proxy_password);
 echo "\n";
 $time_end = microtime(1);
 if($crv!=false)
   {
    $lasted_time = $time_end - $time_start;
    $size = filesize($tmp_file) / 1024;
    $speed = round($size / $lasted_time, 2);
    $result['download']['value'] = "Size: $size KB. Speed: $speed KB/s.";
    $result['download']['lasted'] = $lasted_time;
    echo "Downloaded Size: $size KB\n";
    echo "Download Speed: $speed KB/s\n";
    echo "lasted time: " . $lasted_time . "\n\n";
   }
 else
   {
    echo "Download failed\n";
   }
 @unlink($tmp_file);
 $time_start = microtime(1);
 echo "[http]\n";
 $result['http']['value'] = '';
 $url=SAE_CODE_DEPOSITION_ADDRESS.'?action=sdkversion';
 $url=parse_url($url);
 $path=isset($url['path'])?$url['path']:'/';
 $query=isset($url['query'])?$url['query']:'';
 $host=isset($url['host'])?$url['host']:'';
 if($url['scheme'] === 'http')
	$port=isset($url['port'])?$url['port']:80;
 elseif ($url['scheme'] === 'https')
	$port=443;
 $fp=false;
 if($http_proxy_host==false)
	$fp=fsockopen($url['host'],$port,$errno,$err,SAE_TCP_CONNECTION_TIMEOUT_SECS);
 else
	$fp=fsockopen($http_proxy_host,$http_proxy_port,$errno,$err,SAE_TCP_CONNECTION_TIMEOUT_SECS);
 if(!$fp)
   {
	if($http_proxy_port==false) {
		$result['http']['value'] .= 'failed to connect '.$http_proxy_host.':'.$http_proxy_port.' @'.$errno.' '.$err;
		echo 'failed to connect '.$http_proxy_host.':'.$http_proxy_port.' @'.$errno.' '.$err;
	} else {
		$result['http']['value'] .= 'failed to connect '.$url['host'].':'.$port.' @'.$errno.' '.$err;
		echo 'failed to connect '.$url['host'].':'.$port.' @'.$errno.' '.$err;
	}
   }
else
   {
	if($http_proxy_port==false) {
		$result['http']['value'] .= 'sock open '.$url['host'].':'.$port." OK\n";
		echo 'sock open '.$url['host'].':'.$port." OK\n";
	} else {
		$result['http']['value'] .= 'sock open '.$http_proxy_host.':'.$http_proxy_port." OK\n";
		echo 'sock open '.$http_proxy_host.':'.$http_proxy_port." OK\n";
	}

	$header="GET $path?$query HTTP/1.1\r\n";
	$header.="Host:$host\r\n";
	if($http_proxy_username!=false)
		$headers.='Proxy-Authorization: '.'Basic '.base64_encode($http_proxy_username.':'.$http_proxy_password)."\r\n";
	$header.="Connection:close\r\n\r\n";
	echo "\nHTTP Header:\n";
	$result['http']['value'] .= "\nHTTP Header:\n";
	print_r($header);
	$result['http']['value'] .= print_r($header, true);
	echo "\n";
	$result['http']['value'] .= "\n";
	if(fwrite($fp,$header)==false) {
		echo 'failed to write header';
		$result['http']['value'] .= 'failed to write header';
	} else {
		$s='';
		while(!feof($fp))
		{
			$s=fgets($fp);
			echo $s;
			$result['http']['value'] .= $s;
			break;
		}//end while
		if(strstr($s,"OK")==false) {
			echo "http reponse error";
			$result['http']['value'] .= "http reponse error";
		}
	}	
   }
 $time_end = microtime(1);
 $lasted_time = $time_end - $time_start;
 $result['http']['lasted'] = $lasted_time;
 echo "lasted time: " . $lasted_time . "\n\n";
 $args['data'] = serialize($result);

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
 $snoopy->submit(SAE_DIAGNOSE_ADDRESS,$args);
}
//-------------------------------------------------------------------------------------------------

function Diagnose($args)
{
 if(array_key_exists('h',$args))
   {
    echo "usage: php am.php diagnose [args]\n";
 echo "
ARGS
       -x host:port:username:password
          set http proxy host,port(default 80),username(default empty),password(default empty)
          example -x127.0.0.1:80 or -x127.0.0.1:80:kobe:1234\n";
    return;
   }
 echo "Diagnosing network connection...\n\n";
 $http_proxy=NULL;
 $http_proxy_host=false;
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password); 
 $url=SAE_CODE_DEPOSITION_ADDRESS.'?action=sdkversion';
 $url=parse_url($url);
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
   {
    if($http_proxy_port==false)
       PrintError('failed to connect '.$http_proxy_host.':'.$http_proxy_port.' @'.$errno.' '.$err,true);
    else
       PrintError('failed to connect '.$url['host'].':'.$port.' @'.$errno.' '.$err,true);
   }
 else
   {
    if($http_proxy_port==false)
       echo 'sock open '.$url['host'].':'.$port." OK\n";
    else
       echo 'sock open '.$http_proxy_host.':'.$http_proxy_port." OK\n";
   }
 $header="GET $path?$query HTTP/1.1\r\n";
 $header.="Host:$host\r\n";
 if($http_proxy_username!=false)
    $headers.='Proxy-Authorization: '.'Basic '.base64_encode($http_proxy_username.':'.$http_proxy_password)."\r\n";
 $header.="Connection:close\r\n\r\n";
 echo "\n[http header]:\n";
 print_r($header);
 echo "\n";
 if(fwrite($fp,$header)==false)
    PrintError('failed to write header',true);
 $s='';
 while(!feof($fp))
      {
       $s=fgets($fp);
       echo $s;
       break;
      }//end while
 if(strstr($s,"OK")==false)
    PrintError("http reponse error");
 echo "Diagnose done\n";
 exit;
}
//-------------------------------------------------------------------------------------------------

function Account()
{
 $email_filename=SDK_INTERNAL_PATH.'email';
 $token_filename=SDK_INTERNAL_PATH.'token';
 if(file_exists($email_filename)) 
    @unlink($email_filename);
 if(file_exists($token_filename)) 
    @unlink($token_filename);
 echo 'Cleaned current user data except app code'."\n";
 sleep(1);
}
//-------------------------------------------------------------------------------------------------

function Quick($args)
{
 if(file_exists(SDK_INTERNAL_PATH.'last'))
    passthru(file_get_contents(SDK_INTERNAL_PATH.'last'));
 sleep(1);
}
//-------------------------------------------------------------------------------------------------

function main(&$argv)
{
 global $confinfor;
 $subcommand='';
 $args=array();
 if(AmGetopt($argv,$subcommand,$args)==false)
   {
    PrintError('wrong arguments',false);
    AmDisplayUsage();
    exit; 
   }
 switch($subcommand)
       {
        case 'create':
             Create($args);
             break;
        case 'upload':
             Upload($args);
             break;
        case 'download':
             Download($args);
             break;
        case 'downloadall':
             DownloadAll($args);
             break;
        case 'bigfileupload':
             BigFileUpload($args);
             break;
        case 'account':
             Account($args);
             break;
        case 'quick':
             Quick($args);
             break;
        case 'upgrade':
             Upgrade($args);
             exit;
        case 'diagnose':
             DiagnoseNew($args);
             break;
        case 'help':
             AmDisplayUsage();
             exit;
        default:
             {
             PrintError('wrong arguments',false);
             AmDisplayUsage();
             exit;
             }   
       };//end switch
 $http_proxy=NULL;
 $http_proxy_host='';
 $http_proxy_port=0;
 $http_proxy_username='';
 $http_proxy_password='';
 if(array_key_exists('x',$args)==true)
    $http_proxy=StringToProxyContext($args['x'],$http_proxy_host,$http_proxy_port,
                                     $http_proxy_username,$http_proxy_password);
 $link='';
 $ver=CheckSDKUpdate($link,$http_proxy);
 if($ver!=false)
    echo "\nSDK ".$ver." is now available!\nplease download via ".$link." manually or run 'php am.php upgrade'\n";
}
//-------------------------------------------------------------------------------------------------
main($argv);
//-------------------------------------------------------------------------------------------------
?>
