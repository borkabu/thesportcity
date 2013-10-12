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

class User {
   var $user_id;
   var $user_name;
   var $email;
   var $email_verified;
   var $last_lang;
   var $pm_email;
   var $stock_profit_email;
   var $groups;
   var $group_str;
   var $country;
   var $town;
   var $postcode;
   var $address1;
   var $address2;
  
   function User($user_id = '') {
     $this->user_id= $user_id;
     $this->groups = '';
     $this->group_str = '';
   }

   function getUserIdFromEmail($email) {
     global $db;
 
     $where = "ACTIVE='Y' AND UPPER(EMAIL) LIKE UPPER('".$email."') "; 
     $db->select('users', 'USER_ID', $where);
     if ($row = $db->nextRow()) {
       $this->user_id = $row['USER_ID'];
     }
     else $this->user_id = -1;
     return $this->user_id;
   }

   function getUserIdFromUsername($user_name) {
     global $db;
 
     $sql = "SELECT U.USER_ID, U.EMAIL, U.LAST_LANG, U.PM_EMAIL, U.STOCK_PROFIT_EMAIL, U.EMAIL_VERIFIED
		FROM users U
		WHERE U.ACTIVE='Y' 
			AND UPPER(U.USER_NAME) LIKE UPPER('".$user_name."') "; 
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $this->user_id = $row['USER_ID'];
       $this->email = $row['EMAIL'];
       $this->email_verified = $row['EMAIL_VERIFIED'];
       $this->last_lang = $row['LAST_LANG'];
       $this->pm_email = $row['PM_EMAIL'];
       $this->stock_profit_email = $row['STOCK_PROFIT_EMAIL'];
     }
     else $this->user_id = -1;
     return $this->user_id;
   }


   function getUserIdFromId($user_id) {
     global $db;
 
     $sql = "SELECT U.USER_ID, U.EMAIL, U.LAST_LANG, U.PM_EMAIL, U.USER_NAME, U.EMAIL_VERIFIED
		FROM users U
		WHERE U.ACTIVE='Y' 
			AND U.USER_ID=".$user_id; 
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $this->user_id = $row['USER_ID'];
       $this->email = $row['EMAIL'];
       $this->email_verified = $row['EMAIL_VERIFIED'];
       $this->last_lang = $row['LAST_LANG'];
       $this->user_name = $row['USER_NAME'];
       $this->pm_email = $row['PM_EMAIL'];
     }
     else $this->user_id = -1;
     return $this->user_id;
   }

   function getUserData() {
     global $db;
 
     $sql = "SELECT U.USER_ID, U.EMAIL, U.LAST_LANG, U.USER_NAME ,
			U.COMMENT_TRUST, U.CONTENT_TRUST, U.LEAGUE_OWNER_RATING,
			U.REG_DATE, U.LAST_LOGIN, C.CCTLD, CD.COUNTRY_NAME,
			U.POSTCODE, U.ADDRESS1, U.ADDRESS2, U.TOWN, U.LAST_LANG, U.CREDIT, U.EMAIL_VERIFIED
		FROM users U, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		WHERE U.ACTIVE='Y' 
	       	        AND U.COUNTRY = C.ID
			AND U.USER_ID=".$this->user_id; 
     $db->query($sql);
     $data = '';
     if ($row = $db->nextRow()) {
       $data = $row;
       if (!empty($row['CCTLD'])) {
          $data['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
       }
       $data['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
       //get clan data
       $data['CLAN'] = $this->getUserClan();
     }
     return $data;
   }

   function getManagerData() {
     global $db;
     global $tpl;
     global $data;
     global $_SESSION;

     $sql="SELECT MS.PLACE, MS.POINTS, MS.MSEASON_ID, MSD.SEASON_TITLE, ROUND(MS.RATING, 2) as RATING
                    FROM manager_standings MS, manager_seasons MSS 
                       		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
			WHERE 
                          MS.USER_ID =".$this->user_id." AND MSS.SEASON_ID=MS.MSEASON_ID
		ORDER BY MS.MSEASON_ID";
     $db->query($sql);
     $c = 0;
     while ($row = $db->nextRow()) {
       $data['MANAGER_DATA'][0]['MANAGER'][$row['MSEASON_ID']] = $row;
       $c++; 
     }

     $tpl->setCacheLevel(TPL_CACHE_NOTHING);
     $tpl->setTemplateFile('tpl/bar_user_manager_results.tpl.html');
     $tpl->addData($data);

     if ($c== 0)
       return '';
     return $tpl->parse();
   }

   function getWagerData() {
     global $db;
     global $tpl;
     global $data;

     $sql="SELECT MS.PLACE, MS.WEALTH, MS.SEASON_ID, MSD.TSEASON_TITLE
                    FROM wager_standings MS, wager_seasons MSS 
                       		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
			WHERE 
                          MS.USER_ID =".$this->user_id." AND MSS.SEASON_ID=MS.SEASON_ID
		ORDER BY MS.SEASON_ID";
     $db->query($sql);
     $c = 0;
     while ($row = $db->nextRow()) {
       $data['WAGER_DATA'][0]['WAGER'][$row['SEASON_ID']]['WEALTH'] = $row['WEALTH'];
       $data['WAGER_DATA'][0]['WAGER'][$row['SEASON_ID']]['PLACE'] = $row['PLACE'];        
       $data['WAGER_DATA'][0]['WAGER'][$row['SEASON_ID']]['TSEASON_TITLE'] = $row['TSEASON_TITLE'];        
       $c++; 
     }

     $tpl->setCacheLevel(TPL_CACHE_NOTHING);
     $tpl->setTemplateFile('tpl/bar_user_wager_results.tpl.html');
     $tpl->addData($data);

     if ($c== 0)
       return '';
     return $tpl->parse();
   }

   function getArrangerData() {
     global $db;
     global $tpl;
     global $data;

     $sql="SELECT MS.PLACE, MS.POINTS, MS.MSEASON_ID, MSD.TSEASON_TITLE
                    FROM bracket_standings MS, bracket_seasons MSS 
                       		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
			WHERE 
                          MS.USER_ID =".$this->user_id." AND MSS.SEASON_ID=MS.MSEASON_ID
		ORDER BY MS.MSEASON_ID";
     $db->query($sql);
     $c = 0;
     while ($row = $db->nextRow()) {
       $data['ARRANGER_DATA'][0]['ARRANGER'][$row['MSEASON_ID']]['PLACE'] = $row['PLACE'];        
       $data['ARRANGER_DATA'][0]['ARRANGER'][$row['MSEASON_ID']]['POINTS'] = $row['POINTS'];        
       $data['ARRANGER_DATA'][0]['ARRANGER'][$row['MSEASON_ID']]['TSEASON_TITLE'] = $row['TSEASON_TITLE'];        
       $c++; 
     }

     $tpl->setCacheLevel(TPL_CACHE_NOTHING);
     $tpl->setTemplateFile('tpl/bar_user_arranger_results.tpl.html');
     $tpl->addData($data);

     if ($c== 0)
       return '';
     return $tpl->parse();
   }

   function getUserProfile() {
     global $data;	
  
     $data['USER_DATA']=$this->getUserData();
     $data['MANAGER_DATA'] = $this->getManagerData(); 
     $data['WAGER_DATA'] = $this->getWagerData(); 
     $data['ARRANGER_DATA'] = $this->getArrangerData(); 

     return $data;
   }

   function getPM_Email() {
     if ($this->pm_email == 'N' || $this->email_verified == 'N') 
       return false;
     return true;
   }	

   function getStockProfitEmail() {
     if ($this->stock_profit_email == 'N') 
       return false;
     return true;
   }	

   function getGroupsData($moderate = false) {
     global $db;
     global $tpl;
     global $data;
     global $_SESSION;
     global $group_member_level;

     $where_moder = "";
     if ($moderate)
       $where_moder = " AND UA.LEVEL IN (1,3) ";
     $sql='SELECT UA.USER_ID, UA.DATE_JOINED, UA.GROUP_ID, AD.GROUP_NAME, 
		UA.LEVEL, A.GROUP_MEMBERS
             FROM forum_groups_members UA, forum_groups A
		LEFT JOIN forum_groups_details AD ON
			AD.GROUP_ID=A.GROUP_ID and AD.LANG_ID='.$_SESSION['lang_id'].'
             WHERE A.GROUP_ID=UA.GROUP_ID
                  AND UA.USER_ID='.$this->user_id.$where_moder.'
            ORDER BY AD.GROUP_NAME ASC';
     $db->query($sql);
     $c = 0;
//echo $sql;
     while ($row = $db->nextRow()) {
       $data['GROUPS_DATA'][0]['GROUP'][$c] = $row;
       if (isset($_GET['group_id']) && $_GET['group_id'] == $row['GROUP_ID']) 
         $data['GROUPS_DATA'][0]['GROUP'][$c]['SELECTED'][0]['X'] = 1;

       $data['GROUPS_DATA'][0]['GROUP'][$c]['LEVEL'] = $group_member_level[$row['LEVEL']];
       $c++; 
     }

     if ($c== 0)
       $data['NO_GROUPS'][0]['X'] = 1;

     $tpl->setCacheLevel(TPL_CACHE_NOTHING);
     if ($moderate)
       $tpl->setTemplateFile('tpl/bar_user_groups_moderate.tpl.html');
     else
       $tpl->setTemplateFile('tpl/bar_user_groups.tpl.html');
     $tpl->addData($data);

     return $tpl->parse();
   }


   function getGroups() {
     global $db;
     global $_SESSION;

     $sql='SELECT UA.USER_ID, UA.DATE_JOINED, UA.GROUP_ID, AD.GROUP_NAME, UA.LEVEL
             FROM forum_groups_members UA, forum_groups A
		LEFT JOIN forum_groups_details AD ON
			AD.GROUP_ID=A.GROUP_ID and AD.LANG_ID='.$_SESSION['lang_id'].'
             WHERE A.GROUP_ID=UA.GROUP_ID
                  AND UA.USER_ID='.$this->user_id.'
            ORDER BY AD.GROUP_NAME ASC';
     $db->query($sql);
     $c = 0;
     $pre = '';
//echo $sql;     
     while ($row = $db->nextRow()) {
       $this->groups[$c] = $row['GROUP_ID'];
       $this->group_str .= $pre . $row['GROUP_ID'];
       $pre = ',';
       $c++; 
     }

     return $this->group_str;
   }

  function getClubs() {
    global $db;
    global $_SESSION;
    global $auth;

    $sql = "SELECT FGD.GROUP_NAME, FG.GROUP_ID
		FROM forum_groups FG, forum_groups_details FGD, forum_groups_members FGM
		 where FG.type=2 
		    AND FGD.LANG_ID=".$_SESSION['lang_id']."		    
		    and FG.GROUP_ID=FGD.GROUP_ID
		    and FG.GROUP_ID=FGM.GROUP_ID
		    and FGM.USER_ID=".$auth->getUserId();
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
       $_SESSION['_user']['CLUBS'][$c] = $row;
       $c++; 
    }    
    return $c;

  }

  function getClan() {
    global $db;
    global $_SESSION;
    global $auth;

    $sql = "SELECT CM.USER_ID, C.CLAN_ID, CM.STATUS 
		FROM clan_members CM, clans C 
		where C.CLAN_ID=CM.CLAN_ID
		      AND CM.STATUS in (1, 2)
		      AND CM.USER_ID=".$auth->getUserId();
//echo $sql;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $_SESSION['_user']['CLAN'] = $row;
      return $row['CLAN_ID'];
    }
    return -1;
  }

  function getUserClan() {
    global $db;

    $sql = "SELECT CM.USER_ID, C.CLAN_ID, CM.STATUS, C.CLAN_NAME 
		FROM clan_members CM, clans C 
		where C.CLAN_ID=CM.CLAN_ID
		      AND CM.STATUS in (1, 2)
		      AND CM.USER_ID=".$this->user_id;
//echo $sql;
    $db->query($sql);
    $data = "";
    if ($row = $db->nextRow()) {
      $data = $row;
    }
    return $data;
  }

   function getClansData() {
     global $db;
     global $auth;
     global $smarty;
     global $_SESSION;

     $sql= "SELECT MLM.CLAN_ID, MLM.STATUS,
		sum(if (MS.END_DATE is NULL, 0, 1) * if (CTM.USER_ID is NULL, 0, 1) ) TEAMS
            FROM clan_members MLM
		   LEFT JOIN clan_teams CT ON MLM.CLAN_ID=CT.CLAN_ID
		   LEFT JOIN manager_seasons MS ON CT.SEASON_ID=MS.SEASON_ID AND MS.END_DATE > NOW()
		   LEFT JOIN clan_team_members CTM ON MLM.user_id=CTM.user_id and CTM.STATUS=1 and CT.TEAM_ID=CTM.TEAM_ID		
           WHERE MLM.STATUS IN (1,2)
		AND MLM.USER_ID=".$auth->getUserId()."
           GROUP BY MLM.CLAN_ID, MLM.STATUS"; 
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $clan_id = $row['CLAN_ID'];
       $teams = $row['TEAMS'];
     }

     $sql = "SELECT CM.USER_ID, C.CLAN_ID, C.CLAN_NAME , CM.DATE_JOINED, CM.DATE_LEFT, CM.STATUS, 0 as TEAMS
		FROM clan_members CM, clans C 
		where C.CLAN_ID=CM.CLAN_ID
		      AND CM.STATUS in (1, 2, 4)
		      AND CM.USER_ID=".$auth->getUserId()."
		ORDER BY CM.DATE_LEFT ASC";
     $db->query($sql);
//echo $sql;
     $clans = array();
     while ($row = $db->nextRow()) {
       $clan = $row;
       if ($row['STATUS'] != 4)
         $clan['DATE_LEFT'] = '-';
       if (isset($clan_id) && $clan_id == $row['CLAN_ID']) {
         $clan['TEAMS'] = $teams;
         if ($teams == 0 && $row['STATUS'] == 2) {
           $clan['CAN_LEAVE'] = 1;
         }
       }

       $clans[] = $clan;
     }

     $smarty->assign("clans", $clans);
     $start = getmicrotime();
     $output = $smarty->fetch('smarty_tpl/bar_user_clans.smarty');    
     $stop = getmicrotime();
     if (isset($_GET['debugphp']))
       echo 'smarty_tpl/bar_user_clans.smarty'.($stop-$start);

     return $output;
   }

   function leaveClan() {
     global $db;
     global $auth;

     $clan_id = $this->getClan();

     if ($clan_id > -1 && $_SESSION['_user']['CLAN']['STATUS'] == 2) {
       $sdata['STATUS'] = 4;
       $sdata['DATE_LEFT'] = 'NOW()';
       $db->update('clan_members', $sdata, 'CLAN_ID='.$clan_id.' AND USER_ID='.$auth->getUserId().' AND STATUS=2');
       unset($sdata);
       $sdata['MEMBERS'] = 'MEMBERS-1';
       $db->update('clans', $sdata, "CLAN_ID=".$clan_id);

       $sql="SELECT F.GROUP_ID
             FROM clans ML, clan_members MLM, forum F
             WHERE ML.CLAN_ID=MLM.CLAN_ID
	       AND ML.CLAN_ID=".$clan_id."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=2
	       AND ML.FORUM_ID=F.FORUM_ID";
       $db->query($sql);    
       if ($row = $db->nextRow()) {
         $db->delete('forum_group_members', 'USER_ID='.$auth->getUserId().' AND GROUP_ID='.$row['GROUP_ID']);  
       }

       // log it
       $clan_log = new ClanLog();
       $clan_log->logEvent ($clan_id, 3, 0, $auth->getUserId(), '');

       $clan_user_log = new ClanUserLog();
       $clan_user_log->logEvent ($auth->getUserId(), 2, $clan_id);

       $credits = new Credits();
       $credits->updateClanCredits($clan_id, -1);
     }
   }


  function getUserTimezoneName($timezone = 0) {
    global $timezones;

    if ($timezone != 0)
      return substr($timezones[$timezone], 1, strpos($timezones[$timezone], "]") - 1); 
    else return "+00:00";
  }

}
?>