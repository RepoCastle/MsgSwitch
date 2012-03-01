<?php
require_once('core.function.php');
//-------------------------------------------------------------------------------------------------
function ConsistentCheck($online_deploy_data_filename,$local_deploy_data_filename,
                         $apppath,$appname,$appversion)
{
 $online_deploy_data=@file_get_contents($online_deploy_data_filename);
 if($online_deploy_data==false)
    return true;
 $online_deploy_data=unserialize($online_deploy_data);
 $local_deploy_data=@file_get_contents($local_deploy_data_filename);
 if($local_deploy_data==false)
    return true;
 $local_deploy_data=unserialize($local_deploy_data);
 usort($online_deploy_data,array("FilePair","cmp_obj"));
 usort($local_deploy_data,array("FilePair","cmp_obj"));
 for($i=0;$i<count($online_deploy_data);$i++)
	 $online_deploy_data[$i]->filename=$apppath."/".$appname."/".$appversion."/code/".$online_deploy_data[$i]->filename;
 for($i=0;$i<count($local_deploy_data);$i++)
    {
     $s1=$local_deploy_data[$i]->filename;
     $s2="/config.yaml";
	 if(strlen($s1)<strlen($s2))
		continue;
     if(
		strcmp(
		       substr($s1,strlen($s1)-strlen($s2),strlen($s2)),$s2
		      )==0
	   )
       {
        unset($local_deploy_data[$i]);
        break;
       }
    }
 //var_dump($online_deploy_data);
 //var_dump($local_deploy_data);
 $odal=count($online_deploy_data);
 $ldal=count($local_deploy_data);
 if($odal!=$ldal)
    return false;
 for($i=0;$i<$odal;$i++)
    {
     if($online_deploy_data[$i]!=$local_deploy_data[$i])
        return false;
    }
 return true;
}
//-------------------------------------------------------------------------------------------------
//var_dump($argv);
if(count($argv)==6)
  {
   if(ConsistentCheck(trim($argv[1]),trim($argv[2]),trim($argv[3]),
	                  trim($argv[4]),trim($argv[5]))
	 )
     {
      if(file_exists($argv[1]))
         @unlink($argv[1]);
      echo "0";
      return 0;
     }
   echo "-1";
   return -1;
  }
echo "-2"; 
return -2;
?>
