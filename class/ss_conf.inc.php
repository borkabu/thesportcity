<?php

 if (isset($auth) && $auth->userOn()) {

   $db->select("ss_users", "*, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(LAST_VISIT) AS TIMECHECK", "USER_ID=".$_SESSION["_user"]['USER_ID']);
   if ($row = $db->nextRow()) {
     $_SESSION["_user"]['SS'][0] = $row;
   }  
   $db->free();
   unset($sdata);
   $sdata['LAST_VISIT'] = "NOW()";
   $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);     
   $db->select("ss_battle_status", "*", "STATUS < 3 AND USER_ID=".$auth->getUserId());
   if (($row = $db->nextRow()) && $_SERVER["PHP_SELF"] != $root_prefix."/ss_char.php") {
     if (($_SERVER["PHP_SELF"] != $root_prefix."/ss_battle.php") && $row['STATUS'] > 0) {
       header("location: ss_battle.php");
       exit; 
     } else if ($_SERVER["PHP_SELF"] != $root_prefix."/ss_battles.php" && $row['STATUS'] == 0) { 
       header("location: ss_battles.php");
       exit; 
     }
   }
   else {
   // not in battle, update stamina
     $stam_diff = $_SESSION["_user"]['SS'][0]['STAMINA'] + $_SESSION["_user"]['SS'][0]['TIMECHECK']/24;
  //   echo $stam_diff." ".($_SESSION["_user"]['SS'][0]['TIMECHECK'])." ";
     if ($stam_diff > 100)
       $stam_diff = 100;
     unset($sdata);
     $sdata['STAMINA'] = $stam_diff;
     $_SESSION["_user"]['SS'][0]['STAMINA'] = $sdata['STAMINA'];
     $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   

     // move to location
//echo $_SESSION["_user"]['SS'][0]['LOCATION'];
//echo $_SERVER['HTTP_REFERER'] . $root_prefix."/".$locations[$_SESSION["_user"]['SS'][0]['LOCATION']].strpos($_SERVER['HTTP_REFERER'] , $root_prefix."/".$locations[$_SESSION["_user"]['SS'][0]['LOCATION']]);
     if ($_SERVER["PHP_SELF"] != $root_prefix."/ss_char.php") {
       if ($_SERVER["PHP_SELF"] != $root_prefix."/".$locations[$_SESSION["_user"]['SS'][0]['LOCATION']] 
         && (!isset($_SERVER['HTTP_REFERER'])  || strpos($_SERVER['HTTP_REFERER'] , $root_prefix."/".$locations[$_SESSION["_user"]['SS'][0]['LOCATION']]) === false)) {
         header("location: ".$locations[$_SESSION["_user"]['SS'][0]['LOCATION']]);
         exit; 
       }
     }
   }
   
 } 
 else if (!$auth->userOn()) {
  header("location: ss_manager.php");
  exit; 
 }

?>