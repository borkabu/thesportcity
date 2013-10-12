<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class Chat {
  
   function Chat() {
   }

   function getNickMetadata() {
     global $db;
     global $auth;
     global $_SESSION;
     global $langs;
     global $genders;

     $metadata = '';
   
     $metadata[$langs['LANG_SEX_U']] = $genders[$_SESSION['_user']['GENDER']];
     $metadata[$langs['LANG_COMMENT_TRUST_POINTS_U']] = $_SESSION['_user']['COMMENT_TRUST'];
     return $metadata;
   }

   function getChannelsList() {
      global $_SESSION;    

      $channels = array();
      array_push ($channels, 'TheSportCity.Net');
      array_push ($channels, 'TheSportCity.Net en');
      array_push ($channels, 'TheSportCity.Net lt');
//      $channels = array_merge($channels, $this->getAllUsersPrivateLeagues());
      return $channels;
   }

   function getCurrentChannels() {
      global $_SESSION;
      global $_GET;
      if (isset($_GET['channel']))
        return array($_GET['channel']);
      else
        return array('TheSportCity.Net '.$_SESSION['_lang']);
   }

   function getAllUsersPrivateLeagues() {
     global $db;
     global $auth;
     global $_SESSION;

     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.SEASON_ID, U.USER_NAME, MSD.SEASON_TITLE, PC.USERS
             FROM manager_leagues_members MLM, manager_seasons M
		  left JOIN manager_seasons_details MSD ON M.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'].",  
		  manager_leagues ML
                  LEFT JOIN manager_leagues_members MLM1 ON ML.LEAGUE_ID=MLM1.LEAGUE_ID AND (MLM1.STATUS=1)
                  LEFT JOIN users U on MLM1.USER_ID=U.USER_Id and MLM1.STATUS=1 
		  left join 
			(SELECT count(*) users, subgroup
				FROM `phpfreechat`
				WHERE `group` = 'channelid-to-nickid'
				and subgroup <> 'SERVER'
				group by subgroup) PC 
			ON PC.subgroup = REPLACE(CONCAT('ch_', ML.TITLE, '_', ML.LEAGUE_ID, '_', ML.SEASON_ID), ' ' , '_')
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=M.SEASON_ID
               AND M.START_DATE < NOW() 
               AND M.END_DATE > NOW() 
               AND MLM.USER_ID=".$auth->getUserId()."
               AND (MLM.STATUS=2 OR MLM.STATUS=1)
	     GROUP BY MLM.LEAGUE_ID ORDER BY MLM.STATUS ASC";
    $db->query($sql);    
 
    $channels = array();

    $c = 0;
    while ($row = $db->nextRow()) {
      $channels[$c]['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $channels[$c]['CHANNEL_LINK'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
      $channels[$c]['CHANNEL_TITLE'] = $row['TITLE']." (".$row['USER_NAME'].")";
      if (!empty($row['USERS'])) 
        $channels[$c]['USERS'] = $row['USERS'];
      else $channels[$c]['USERS'] = 0;
      $c++;
    }
    return $channels;
   }

   function getUsersCount() {
     global $db;

     $sql="SELECT COUNT( DISTINCT USER_ID) USERS FROM chat_stats where CHECKIN_TIME > DATE_ADD(NOW(), INTERVAL -5 MINUTE )";
     $db->query($sql);    
     $row = $db->nextRow();

     return $row['USERS'];
   }
}
?>