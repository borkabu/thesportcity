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

class ClanBox extends Box{

  function ClanBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getClans ($page=1,$perpage=PAGE_SIZE) {
    global $auth;
    global $smarty;
    global $db;
    global $_SESSION;
    
    // content
    $clans = $this->getClansData($page, $perpage);
    $this->rows = count($clans);	

    if ($auth->userOn()) {
      $smarty->assign("clan_leader", $auth->isClanLeader());
      $smarty->assign("clan_member", $auth->isClanMember());
      $smarty->assign("clan_invite", $auth->getClanInvites());
    }

    $smarty->assign("clans", $clans);
    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/clans.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/clans.smarty'.($stop-$start);

    return $content;
  } 

  function getClanBox () {
    global $smarty;
    global $db;
    global $_SESSION;
    global $auth;    
    // content
     
    if ($auth->userOn()) { 
      $user = new User($auth->getUserId());
      $user->getClan();
      if (isset($_SESSION['_user']['CLAN']))
        $data = $this->getClanUserItemData($_SESSION['_user']['CLAN']['CLAN_ID']);
      else {
        $data['NOT_CLAN_MEMBER'] = 1;
        // show invites
        $clan_invites = $auth->getClanInvites();
        $smarty->assign("clan_invites", $clan_invites);
      }

      $smarty->assign("data", $data);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_clan.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_clan.smarty<br>'.($stop-$start);
      return $output;
    } else 
        return '';
  } 

  function getClanItem ($clan_id) {
    global $smarty;
    global $db;
    global $_SESSION;
    global $_GET;
    global $langs;
    global $pagingbox;
    
    // content
    $data = $this->getClanItemData($clan_id);

//echo $data;
//exit;
    if ($data != '') {
      $logbox = new LogBox($langs, $_SESSION["_lang"]);
      $clan_log = $logbox->getClanLogBox($clan_id, isset($_GET['page']) ? $_GET['page'] : 1);
      $clan_log_paging = $pagingbox->getPagingBox($logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);

      $smarty->assign("data", $data);
      $smarty->assign("clan_log",  $clan_log);
      $smarty->assign("clan_log_paging",  $clan_log_paging);

      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/clan_item.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/clan_item.smarty<br>'.($stop-$start);
      return $output;
    }
    else {
      return '';
    }
  } 

  function getClanItemData($clan_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;
    global $PRESET_VARS;
    global $forumbox;

    $data='';

    if (is_numeric($clan_id)) {
       $clan = new Clan($clan_id);
       $sql= "SELECT ML.CLAN_ID, MLM.USER_ID, MLM.LEVEL, ML.CLAN_NAME, ML.PIC_LOCATION,
		U.USER_NAME, C.CCTLD, CD.COUNTRY_NAME, ML.DESCR, ML.CLAN_FUND, ML.FORUM_ID,
		sum(if (MS.END_DATE is NULL, 0, 1) * if (CTM.USER_ID is NULL, 0, 1) ) TEAMS
            FROM clans ML, clan_members MLM
		   LEFT JOIN clan_teams CT ON MLM.CLAN_ID=CT.CLAN_ID
		   LEFT JOIN manager_seasons MS ON CT.SEASON_ID=MS.SEASON_ID AND MS.END_DATE > NOW()
		   LEFT JOIN clan_team_members CTM ON MLM.user_id=CTM.user_id and CTM.STATUS=1 and CT.TEAM_ID=CTM.TEAM_ID,
		users U, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.CLAN_ID=".$clan_id."
	        AND MLM.CLAN_ID=ML.CLAN_ID
                AND MLM.USER_ID=U.USER_ID
   	        AND U.COUNTRY = C.ID
                   and MLM.STATUS IN (1,2)
           GROUP BY ML.CLAN_ID, MLM.USER_ID
           ORDER BY U.USER_NAME ASC"; 
//echo $sql;
      $db->query($sql);     
      $c=1;    
      $prezident = false;      
      while ($row = $db->nextRow()) {
        $member = $row;
        $member['LOCAL_PLACE'] = $c;

        if (!empty($row['CCTLD'])) {
          $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
        if ($row['LEVEL'] == 3 || $row['LEVEL'] == 1) {
          $data['CLAN']['CLAN_NAME'] = $row['CLAN_NAME'];
          $data['CLAN']['FORUM_ID'] = $row['FORUM_ID'];
          $data['CLAN']['CLAN_FUND'] = $row['CLAN_FUND'];
          $data['CLAN']['PIC_LOCATION'] = $row['PIC_LOCATION'];
          if ($row['LEVEL'] == 3)
            $data['CLAN']['PREZIDENT'] = $row['USER_NAME'];
	  $data['CLAN']['CLAN_ID'] = $clan_id;
          if ($auth->userOn() && $row['USER_ID'] == $auth->getUserId()) {
            $prezident = true;
	    $data['CLAN']['MANAGEMENT'] = $row;
  	    $data['CLAN']['MANAGEMENT']['CLAN_ID'] = $clan_id;
	    $data['CLAN']['MANAGEMENT']['LANG_ID'] = $_SESSION['lang_id'];
//	    $PRESET_VARS['descr'] = $row['DESCR'];
          }  
        }
        else if ($row['LEVEL'] == 2) {
          $member['CURRENT_MEMBERS'] = 1;
        }
        if (!empty($row['RULES']))  
          $data['CLAN']['RULES'] = $row['RULES'];
        $data['CLAN']['DESCR'] = $row['DESCR'];
  
        $data['CLAN']['MEMBERS'][] = $member;
        $html_page->page_title = $row['CLAN_NAME'];
      }
      $db->free();

      if ($auth->userOn()) {
        if ($clan->hasMember($auth->getUserId())) {
          $data['CLAN']['MEMBERSHIP']['MEMBER'] = $row;
        } else {
          $data['CLAN']['MEMBERSHIP']['NO_MEMBER'] = $row;
          // get invite
          if ($clan->hasInvite($auth->getUserId())) {
            $invites =  $auth->getClanInvites($clan_id);
            $data['CLAN']['INVITE'] = $invites[0];
          }
        }
      }

      $data['CLAN']['FORUM'] = $forumbox->getTopicsData($data['CLAN']['FORUM_ID'],1,5);
      $data['CLAN']['CLAN_TEAMS'] = $clan->getClanTeams();
    }      

//print_r($data);
    return $data;
  }

  function getClansData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;

    $where = "1=1";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(N.CLAN_ID) ROWS
                   FROM clans N
                   WHERE ".$where; 

    $db->query($sql_count);
    if ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }

    $sql = "SELECT N.CLAN_ID, N.CLAN_NAME, N.MEMBERS, U.USER_NAME
              FROM clans N left join users U on N.USER_ID=U.USER_ID
            WHERE 
              ".$where."
            ORDER BY 
              N.CLAN_NAME ASC, N.CLAN_ID DESC
            ".$limitclause;

    $db->query($sql);

    $clans = array();
    while ($row = $db->nextRow()) {
      $clan = $row; 
      $clan['LANG'] = $_SESSION['_lang']; 
      $clans[] = $clan;
    }

    return $clans;
  }

  function getClanUserItemData($clan_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;
    global $PRESET_VARS;
    global $forumbox;

    $data='';

    if (is_numeric($clan_id) && $auth->userOn()) {
       $clan = new Clan($clan_id);
       $clan->getClanData();

       if ($clan->hasMember($auth->getUserId())) {
          $data['CLAN'] = $clan->clan_data;
       } else {
          // get invite
          if ($clan->hasInvite($auth->getUserId())) {
            $invites =  $auth->getClanInvites($clan_id);
            $data['CLAN']['INVITE'] = $invites[0];
          }
       }

       $data['CLAN']['TEAMS'] = $clan->getClanUserTeam($auth->getUserId());
       $data['CLAN']['FORUM'] = $forumbox->getTopicsData($data['CLAN']['FORUM_ID'],1,5);
    }      

//print_r($data);
    return $data;
  }

}   
?>