<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/rvs_manager_user.inc.php');
include('lib/manager_config.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 3);

  $manager = new Manager('', 'rvs');
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

  $current_tour = $manager->getCurrentTour();
  $manager_filter_box = $managerbox->getRvsManagerFilterBox($manager->mseason_id);

//$db->showquery=true;
 if ($auth->userOn()) {
   $has_league = false;
   $league_id = -1;
   $created_date = "";
   $draft_date = "";
   $draft_interval = 0;
   $draft_start_date = "";
   $drafting = false;
   $draft_set = false;
   $draft_type = 0;
   $draft_pick_order_type = 0;
   $format = 0;
   $duration = 0;
   $max_duration = 0;
   $market_size = $manager->getMarketSize();
   $market_teams = $manager->getMarketTeams();
   $tours = 0;
   $participants = 0;
   $team_size = 0;
   $reserve_size = 0;
   $free_transfer_fee = 0;
   $joined = 0;
   $can_create = 0;
   $can_update = 0;
   $can_update2 = 0;
   $can_invite = 0;
   $can_remove = 0;
   $can_force = 0;
   $can_delete = 0;

   $sql="SELECT count(tour_id) TOURS, max(number) TOTAL_TOURS, RVS_LEAGUES_LAST_TOUR 
		from manager_tours MT, manager_seasons MS
		where MS.SEASON_ID=".$manager->mseason_id." 
			and MS.SEASON_ID=MT.SEASON_ID 
			AND MT.start_date > now()";
   $db->query($sql); 
   $row = $db->nextRow();
   $tours = $row['TOURS'];      

   if ($row['RVS_LEAGUES_LAST_TOUR'] > 0)
     $tours = $row['TOURS'] - ($row['TOTAL_TOURS'] - $row['RVS_LEAGUES_LAST_TOUR']);       
   else 
     $tours = $row['TOURS'];

   $max_duration = $tours;
   if ($tours > 0)
     $can_create = 1;
  
   $sql= "SELECT ML.*, DRAFT_START_DATE < NOW() as DRAFTING
            FROM rvs_manager_leagues ML
           WHERE ML.USER_ID=".$auth->getUserId()." 
	       AND ML.STATUS in (1,2)
                AND ML.SEASON_ID = ".$manager->mseason_id; 
   $db->query($sql); 
   if ($row = $db->nextRow()) {
////   $has_league = true;
     $rvs_manager_user = new RvsManagerUser($manager->mseason_id, $row['LEAGUE_ID']);
     $created_date = $row['CREATED_DATE'];
     $draft_date = $row['DRAFT_DATE'];
     $draft_start_date = $row['DRAFT_START_DATE'];
     $draft_type = $row['DRAFT_TYPE'];
     $draft_pick_order_type = $row['DRAFT_PICK_ORDER_TYPE'];
     $drafting = $row['DRAFTING'];
     $draft_set = $row['DRAFT_START_DATE'];
     $format = $row['LEAGUE_TYPE'];
     $draft_interval = $row['DRAFT_INTERVAL'];
     $duration = $row['DURATION'];
     $participants = $row['PARTICIPANTS'];
     $joined = $row['JOINED'];
     $team_size = $row['TEAM_SIZE'];
     $reserve_size = $row['RESERVE_SIZE'];
     $free_transfer_fee = $row['FREE_TRANSFER_FEE'];
 
     if ($joined == 1) {
       $can_update = 1;
       $can_delete = 1;
     }
     if ($participants >= $joined && $joined > 1 && empty($draft_date)) {
       $can_update2 = 1;
     }

     if ($participants > $joined && $joined >= 2)
       $can_force = 1;

     if ($participants > $joined) {
       $can_invite = 1;
       $can_remove = 1;
     }
   } else {
     $rvs_manager_user = new RvsManagerUser($manager->mseason_id);
   }

   $sql= "SELECT ML.*
            FROM rvs_manager_leagues ML
           WHERE ML.USER_ID=".$auth->getUserId()." 
	       AND ML.STATUS = 3
                AND ML.SEASON_ID = ".$manager->mseason_id; 
   $db->query($sql); 
   $past_leagues = array();
   while ($row = $db->nextRow()) {
     $past_leagues[] = $row; 
   }

   if (count($past_leagues) > 0)
     $smarty->assign("past_leagues", $past_leagues);
}

if ($auth->userOn() && $manager->manager_trade_allow && $rvs_manager_user->league_id > 0 && isset($_POST['remove_user']) && isset($_POST['user_id']) && empty($draft_date)) {
  $db->select('rvs_manager_leagues_members', "*", 'USER_ID='.$_POST['user_id'].' AND STATUS=2 AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  if ($row= $db->nextRow()) {
    $udata['JOINED'] = 'JOINED-1';  
    $db->update('rvs_manager_leagues', $udata, 'LEAGUE_ID='.$rvs_manager_user->league_id);  
    unset($udata);
    $udata['STATUS'] = 4;  
    $udata['END_DATE'] = "NOW()";
    $db->update('rvs_manager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);  
    unset($udata);
    $db->select('rvs_manager_leagues_members', "*", 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);  
    $row= $db->nextRow();
    $rvs_manager_user_log = new RvsManagerUserLog();
    $rvs_manager_user_log->logEvent($_POST['user_id'], 4, $manager->mseason_id, $rvs_manager_user->league_id);
    $rvs_manager_log = new RvsManagerLog();
    $rvs_manager_log->logEvent ($_POST['user_id'], 6, $manager->mseason_id, $rvs_manager_user->league_id);
    $credits = new Credits();
    $credit_log = new CreditsLog();
    $credits->updateRvsLeagueCredits($rvs_manager_user->league_id, -$row['ENTRY_FEE']);
    $credits->updateCredits($_POST['user_id'], $row['ENTRY_FEE']); 
    $credit_log->logEvent ($_POST['user_id'], 19, $row['ENTRY_FEE']);
  }
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['remove_user2']) && isset($_POST['user_id'])) {
  $db->delete('rvs_manager_leagues_members', 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  unset($udata);
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['cancel_all_invites'])) {
  $db->delete('rvs_manager_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  unset($udata);
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['force_start'])) { 
  unset($udata);
  $udata['PARTICIPANTS'] = "JOINED";
  $udata['STATUS'] = 2;
  $db->update('rvs_manager_leagues', $udata, 'LEAGUE_ID='.$rvs_manager_user->league_id);  
  $db->delete('rvs_manager_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  unset($udata);
}
else if ($auth->userOn() && $can_delete && $rvs_manager_user->league_id > 0 && isset($_POST['delete_league'])) { 
  $db->delete('rvs_manager_leagues', 'LEAGUE_ID='.$rvs_manager_user->league_id);  
  $db->delete('rvs_manager_leagues_members', 'STATUS IN (1,3) AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  header("Location: rvs_manager_league_control.php");
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['tag_user']) && isset($_POST['user_id']) && isset($_POST['tag'])) {
  $udata['TAG'] = "'".$_POST['tag']."'";  
  $db->update('rvs_manager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  unset($udata);
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['draft_pick_order']) && isset($_POST['user_id']) && isset($_POST['draft_pick_order_user'])) {
  $udata['DRAFT_ORDER'] = $_POST['draft_pick_order'];  
  $db->update('rvs_manager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);  
  unset($udata);
}
else if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['set_rules'])) {
  if ($_POST['rules'] == "" && isset($_POST['simple_text']))
    $_POST['rules'] = $_POST['simple_text'];

  $s_fields = array('rules');
  $d_fields = '';
  $c_fields = array('real_prizes', 'moderate_transfers');

  $error = FALSE;
  // check for password matching

  if ($can_update) {
    $i_fields = array('entry_fee', 'country', 'participants', 'team_size', 'reserve_size', 'invite_type', 'draft_type', 'league_type', 'draft_interval', 'draft_pick_order_type', 'discards', 'free_transfers', 'duration', 'free_transfer_fee');
    if ($_POST['entry_fee'] < 0) {
      $error = TRUE;
      $conf_error['ERROR_RSV_LEAGUE_ENTRY_FEE'] = 1;
    }
  }

  if ($can_update2) {
    $i_fields = array('country', 'invite_type', 'draft_type', 'league_type', 'draft_interval', 'draft_pick_order_type', 'discards', 'free_transfers', 'free_transfer_fee');
    if ($_POST['discards'] < 1) {
      $error = TRUE;
      $conf_error['ERROR_RSV_LEAGUE_DISCARDS'] = 1;
    }
  }

  if (isset($conf_error))
    $smarty->assign("conf_error", $conf_error);
  if (!$error) {
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $db->update('rvs_manager_leagues', $udata, 'LEAGUE_ID='.$rvs_manager_user->league_id);  
    unset($udata);
//exit;
  }
}

if ($auth->userOn() && isset($_POST['create_league']) && isset($_POST['title']) && !empty($_POST['title'])
    && $auth->getCredits() >= 0 && $can_create) {
   $sql= "SELECT ML.LEAGUE_ID
           FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM
          WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$manager->mseason_id." 
               AND ML.STATUS in (1,2)
               AND MLM.STATUS=1"; 
   $db->query($sql); 
   if (!$row = $db->nextRow()) {    
     $sql="SELECT count(tour_id) TOURS, max(number) TOTAL_TOURS, RVS_LEAGUES_LAST_TOUR 
		from manager_tours MT, manager_seasons MS
		where MS.SEASON_ID=".$manager->mseason_id." 
			and MS.SEASON_ID=MT.SEASON_ID 
			AND MT.start_date > now()";
     $db->query($sql); 
     $row = $db->nextRow();
     if ($row['RVS_LEAGUES_LAST_TOUR'] > 0)
       $sdata['DURATION'] = $row['TOURS'] - ($row['TOTAL_TOURS'] - $row['RVS_LEAGUES_LAST_TOUR']);       
     else 
       $sdata['DURATION'] = $row['TOURS'];
     $sdata['SEASON_ID'] = $manager->mseason_id;
     $sdata['TITLE'] = "'".$_POST['title']."'";
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['STATUS'] = 1;
     $sdata['TEAM_SIZE'] = $manager_team_sizes[$manager->sport_id];
     $sdata['CREATED_DATE'] = "NOW()";
     $db->insert('rvs_manager_leagues', $sdata);
     unset($sdata);
     $db->select("rvs_manager_leagues", "LEAGUE_ID", "USER_ID=".$auth->getUserId()." AND STATUS= 1 AND SEASON_ID=".$manager->mseason_id);   
     if ($row = $db->nextRow()) {
       $sdata['LEAGUE_ID'] = $row['LEAGUE_ID'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 1;
       $db->insert('rvs_manager_leagues_members', $sdata);     
       unset($sdata);
       $rvs_manager_user = new RvsManagerUser($manager->mseason_id, $row['LEAGUE_ID']);
     }
    $rvs_manager_log = new RvsManagerLog();
    $rvs_manager_log->logEvent ($auth->getUserId(), 1, $manager->mseason_id, $row['LEAGUE_ID']);
    $rvs_manager_user_log = new RvsManagerUserLog();
    $rvs_manager_user_log->logEvent($auth->getUserId(), 5, $manager->mseason_id, $row['LEAGUE_ID']);

   }
   header('Location: rvs_manager_league_control.php');
}

if ($auth->userOn() && $rvs_manager_user->league_id > 0 && isset($_POST['invite']) && isset($_POST['user_name']) && isset($_POST['mseason_id'])) {
   $sql = "SELECT U.USER_ID, MU.USER_ID ISIN, MU.IGNORE_LEAGUES 
             FROM users U LEFT JOIN manager_users MU ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$_POST['mseason_id']."
            WHERE USER_NAME='".$_POST['user_name']."'";
   $db->query($sql);
   if ($row = $db->nextRow()) {
     if (empty($row['IGNORE_LEAGUES'])) {
       $invite['INVITE_ERROR']['NOTEAM'] = 1;
     }
     else if ($row['IGNORE_LEAGUES'] == 'Y') {
       $invite['INVITE_ERROR']['USERIGNORE'] = 1;
     } 
     else if (!empty($row['ISIN']) && $row['ISIN'] != "") {
       $sdata['LEAGUE_ID'] = $rvs_manager_user->league_id;
       $sdata['USER_ID'] = $row['USER_ID'];
       $sdata['STATUS'] = 3;
       // check that it is not already there
       $db->select("rvs_manager_leagues_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$rvs_manager_user->league_id);       
       if ($row = $db->nextRow()) {
         $invite['INVITE_ERROR']['USERDOUBLE'] = 1;
       } 
       else {
         $db->select("rvs_manager_leagues_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$rvs_manager_user->league_id);
         if ($row = $db->nextRow()) {
           $db->update('rvs_manager_leagues_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND LEAGUE_ID='.$rvs_manager_user->league_id);
         }
         else $db->insert('rvs_manager_leagues_members', $sdata);     
         unset($sdata);
       }
     }
     else {
      $invite['INVITE_ERROR']['NOTEAM'] = 1;
     }
   }
   else {
      $invite['INVITE_ERROR']['NOUSER'] = 1;
   }
}

if ($auth->userOn() && isset($_POST['set_draft_time'])) {
    unset($udata);
    $udata['DRAFT_START_DATE'] = "DATE_ADD('".$_POST['draft_start_date']."', INTERVAL -".$auth->getUserTimezone()." HOUR)";
    $db->update('rvs_manager_leagues', $udata, 'LEAGUE_ID='.$rvs_manager_user->league_id);  

    // inform by email about that
    $rvs_manager_user->informDraftTimeSet($rvs_manager_user->league_id);
}

if ($auth->userOn() && isset($_POST['run_draft'])) {
   // get market

  if ($draft_type == 0 && empty($draft_date)) {
    $market = array();
    $players = array();
    $c = 0;
    $where_price = "";
    if ($manager->sport_id == 1)    
      $where_price = " AND CURRENT_VALUE_MONEY > 4000";

  
    $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE
         FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
         WHERE MLM.LEAGUE_ID=".$rvs_manager_user->league_id."		
           AND MLM.STATUS in (1,2)
	   AND U.USER_ID=MLM.USER_ID
	   AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
   
    $db->query($sql);    
    $u = 0;
    while ($row = $db->nextRow()) {
      $players[$u] = $row['USER_ID'];
      $u++;
    }
    
    $rvs_manager_user_log = new RvsManagerUserLog();
    $rvs_manager_log = new RvsManagerLog();
//		AND PLAYED > 0
    for ($t = 0; $t< $team_size; $t++) {
      $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY, 0 as USED 
		FROM manager_market 
		WHERE season_id=".$manager->mseason_id."
		AND PLAYER_STATE = 0
		AND PUBLISH='Y'
		AND USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$rvs_manager_user->league_id." AND SELLING_DATE IS NULL)
		".$where_price."
                 ORDER BY CURRENT_VALUE_MONEY DESC
		LIMIT ".($t*5).", ".($participants*2+5);
      $db->query($sql); 
//echo $sql.$t;
      unset($market);
      $c=0;
      while ($row = $db->nextRow()) {
        $market[$c] = $row;
        $c++;
      }

      for ($p = 0; $p< $participants; $p++) {
        do { 
          $index = rand(0, $participants*2+4);
//          echo $index."<br>";   
        } while($market[$index]['USED'] == 1);
        $market[$index]['USED'] = 1;
    
        unset($sdata);
        $sdata['USER_ID'] = $players[$p];
        $sdata['LEAGUE_ID'] = $rvs_manager_user->league_id;
        $sdata['PLAYER_ID'] = $market[$index]['USER_ID'];
        $sdata['BUYING_PRICE'] = $market[$index]['CURRENT_VALUE_MONEY'];
        $sdata['SELLING_PRICE'] = $market[$index]['CURRENT_VALUE_MONEY'];
        $sdata['BUYING_DATE'] = "NOW()";
        $db->insert("rvs_manager_teams", $sdata);
        $rvs_manager_user_log->logEvent($players[$p], 2, $manager->mseason_id, $rvs_manager_user->league_id, $market[$index]['USER_ID']);
        $rvs_manager_log->logEvent ($players[$p], 10, $manager->mseason_id, $rvs_manager_user->league_id, $market[$index]['USER_ID']); 
      }
    }
    
    $rvs_manager_log->logEvent ($auth->getUserId(), 3, $manager->mseason_id, $rvs_manager_user->league_id); 
    $draft_date = 1;
   
    // update start tour and end tour
    $tours = $manager->getToursAmount();
    unset($sdata);
    $udata['START_TOUR'] = $current_tour;  
    if ($tours >= $current_tour + $duration - 1)
      $udata['END_TOUR'] = $current_tour + $duration - 1;  
    else {
      $udata['END_TOUR'] = $tours;  
      $udata['DURATION'] = $tours - $current_tour + 1;  
    }
    $udata['DRAFT_STATE'] = 3;
    $udata['DRAFT_DATE'] = "NOW()";
    $udata['DRAFT_START_DATE'] = "NOW()";
    $db->update('rvs_manager_leagues', $udata, 'LEAGUE_ID='.$rvs_manager_user->league_id);  


    $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE, RML.DRAFT_START_DATE, RML.DRAFT_DATE, RML.LEAGUE_ID, U.TIMEZONE, U.LAST_LANG, U.EMAIL
         FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
         WHERE MLM.LEAGUE_ID=".$rvs_manager_user->league_id."		
           AND MLM.STATUS in (1,2)
	   AND U.USER_ID=MLM.USER_ID
	   AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
   
    $db->query($sql);    
    $u = 0;
    unset($players);
    while ($row = $db->nextRow()) {
      $players[$u] = $row;
      $u++;
    }

    for ($p = 0; $p< $participants; $p++) {
       $manager->sendDraftEndEmail($players[$p]);
    }
  } else {
    // start manual draft
  }
}

if ($auth->userOn()) {
  // initialize user team
    $sql= "SELECT ML.*, U.USER_NAME,
		DATE_ADD(DRAFT_START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS DRAFT_START_DATE_UTC
          FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM, users U
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
	       AND MLM.USER_ID=U.USER_ID
               AND ML.SEASON_ID=".$manager->mseason_id." 
	       AND ML.STATUS in (1,2)
               AND MLM.STATUS=1"; 
  // echo $sql;
    $db->query($sql); 
    if (!$row = $db->nextRow()) {
      if ($can_create) {
        $smarty->assign("create_league_offer", true);
      } else {
        $error['MSG'] = $langs['LANG_MANAGER_CREATE_TOO_LATE_U'];
      }
    }
    else $has_league = true;

if ($has_league) {
  $league  = $row;
  $league['MAX_DURATION'] = $max_duration;
  $league['MAX_PARTICIPANTS'] = 40;
  $league['MAX_TEAM_SIZE'] = $manager_team_sizes[$manager->sport_id];
  $league['MAX_RESERVE_SIZE'] = $manager_team_sizes[$manager->sport_id] - 1;
  $PRESET_VARS['rules'] = $row['RULES'];
  if (!$can_update)
    $league['READONLY'] = 1;

  $league['COUNTRY'] = inputCountries('country', $row['COUNTRY']);
  $league['INVITE_TYPE'] = inputLeagueInviteTypes('invite_type', $row['INVITE_TYPE']);
  if ($row['INVITE_TYPE'] == 1 && empty($row['INVITE_CODE'])) {
    unset($sdata);
    $sdata['INVITE_CODE'] = "'".gen_rand_string(0, 8)."'";
    $db->update("rvs_manager_leagues", $sdata, "LEAGUE_ID=".$row['LEAGUE_ID']);
    $league['INVITE_CODE'] = $sdata['INVITE_CODE'];
  } else if ($row['INVITE_TYPE'] == 1 && !empty($row['INVITE_CODE'])) {
    $league['INVITE_CODE'] = $row['INVITE_CODE'];
  } else {
    unset($league['INVITE_CODE']);
  }

  $league['DRAFT_TYPE'] = inputLeagueDraftTypes('draft_type', $row['DRAFT_TYPE']);
  $league['DRAFT_TYPE_VALUE'] = $row['DRAFT_TYPE'];
  if ($league['DRAFT_TYPE_VALUE'] == 1) {
    $league['DRAFT_INTERVAL'] = inputLeagueDraftIntervals('draft_interval', $row['DRAFT_INTERVAL']);
    $league['DRAFT_PICK_ORDER_TYPE'] = inputLeagueDraftPickOrderTypes('draft_pick_order_type', $row['DRAFT_PICK_ORDER_TYPE']);
    $league['DRAFT_PICK_ORDER_TYPE_VALUE'] = $row['DRAFT_PICK_ORDER_TYPE'];
  } else {
    unset($league['DRAFT_INTERVAL']);
  }

  if ($league['DRAFT_TYPE_VALUE'] == 1 && empty($league['DRAFT_START_DATE'])) {
    $db->query("SELECT NOW() CURT FROM users LIMIT 1");
    $row2 = $db->nextRow();
    $todayDate = $row2['CURT'];

//    $todayDate = date("Y-m-d g:i a");// current date
    $currentTime = time($todayDate); //Change date into time
//echo $todayDate;
    $timeAfterOneHour = $currentTime+60*60*$auth->getUserTimezone();
    $league['DATE']['YEAR'] = date("Y", $timeAfterOneHour);
    $league['DATE']['MONTH'] = date("m", $timeAfterOneHour);
    $league['DATE']['DAY'] = date("d", $timeAfterOneHour);
    $league['DATE']['HOUR'] = date("H", $timeAfterOneHour);
    $league['DATE']['MINUTE'] = date("i", $timeAfterOneHour);
  }
  else if ($league['DRAFT_TYPE_VALUE'] == 1) {
//      $league['DRAFT_START_DATE']
  }
  $league['DATE']['UTC'] = $auth->getUserTimezoneName();

  $league['FORMAT'] = inputLeagueFormat('league_type', $row['LEAGUE_TYPE']);
  
  foreach ($row as $key => $val) {
    $PRESET_VARS[strtolower($key)] = $val;
  }

  $owner = true;
  $db->free();
  // get members
  $sql= "SELECT MLM.*, U.USER_NAME, MS.POINTS, MS.PLACE
          FROM users U, rvs_manager_leagues_members MLM
		left join rvs_manager_standings MS
			ON MS.MSEASON_ID=".$manager->mseason_id."
			AND MS.USER_ID=MLM.USER_ID
			AND MS.LEAGUE_ID=".$rvs_manager_user->league_id."
         WHERE MLM.LEAGUE_ID=".$rvs_manager_user->league_id."
               AND MLM.USER_ID=U.USER_ID"; 
  $db->query($sql); 
  while ($row = $db->nextRow()) {
   if ($row['STATUS'] == 1) {
     $league_members['OWNER'] = $row;
   }
   else if ($row['STATUS'] == 2) {
     $current_member = $row;
     if ($can_remove) {
       $current_member['CAN_REMOVE'] = $row; 
     }
     $league_members['CURRENT_MEMBERS'][] = $current_member;
   }
   else if ($row['STATUS'] == 3) {
     $league_members['INVITED_MEMBERS'][] = $row;
   }
   else if ($row['STATUS'] == 4) {
     $league_members['FORMER_MEMBERS'][] = $row;
   }
   else if ($row['STATUS'] == 5) {
     $league_members['DECLINE_MEMBERS'][] = $row;
   }

  }
  $db->free();

  if ($owner && $can_invite) {
    // create invitation form
    $invite['LEAGUE_ID'] = $rvs_manager_user->league_id;
    $invite['SEASON_ID'] = $manager->mseason_id;
    $invite['OWNER'] = $auth->getUserId();
    $smarty->assign("invite_form", $invite);
  }
  if ($owner && $can_delete) {
    $smarty->assign("delete_form", 1);
  }
  if ($owner && $can_force) {
    $smarty->assign("force_form", 1);
  }

  $smarty->assign("league", $league);
  $smarty->assign("league_members", $league_members);
 }

 // allow draft
//($current_tour > 1 || $draft_type== 1) && 
  if (empty($draft_start_date) && $participants == $joined && $manager->manager_trade_allow) {
    $smarty->assign("can_draft", true);
  } else if (empty($draft_start_date) && $draft_type==0 && ($participants > $joined || $current_tour <= 1 || !$manager->manager_trade_allow)) {
    $smarty->assign("draft_wait", true);
  } else if (empty($draft_start_date) && $draft_type==1 && ($participants > $joined || !$manager->manager_trade_allow)) {
    $smarty->assign("draft_wait_manual", true);
  } else if (!empty($draft_date)) {
    $smarty->assign("drafted", $draft_date);
  } else if (!$drafting && !empty($draft_start_date) && $draft_type==1) {
    $smarty->assign("draft_set_wait", true);
  } else if ($drafting && empty($draft_date) && $draft_type==1) {
    $smarty->assign("drafting", true);
  } else {

  };

  if ($auth->userOn())
    $smarty->assign("logged", $auth->userOn());

} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_RVS_MANAGER_LOGIN');
}

  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($error))
    $smarty->assign("error", $error);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_league_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_league_control.smarty'.($stop-$start);
  
// ----------------------------------------------------------------------------

// include common header
define("RVS_MANAGER", 1);
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>