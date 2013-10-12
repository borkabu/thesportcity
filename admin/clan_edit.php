<?php
/*
===============================================================================
user_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit people records
  - edit membership with teams
  - edit membership with tournaments
  - edit membership with organizations
  - edit keywords
  - create new person record

TABLES USED: 
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
===============================================================================
*/

ob_start();

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_USERS_EDIT]) || strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'RO') == 0)
  $ro = TRUE;

include('../class/clan_log.inc.php');

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = array('clan_name');
  $i_fields = array();
  $d_fields = array();
  $c_fields = array();
  $r_fields = array('clan_name');
  
  $dupe_fields = array('clan_name');
  $dupe_except = array('clan_id' => $_GET['clan_id']);
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  // check for duplicate records
  if (!dupeFieldsOk('clans', $dupe_fields, $_POST, $dupe_except)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = $langs['LANG_ERROR_DUPE_UNAME_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    
    // proceed to database updates
    if (!empty($_GET['clan_id'])) {
      // UPDATE
      $db->update('clans', $sdata, "USER_ID=".$_GET['user_id']);
    }
    
    // redirect to list page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

$db->showquery=true;
if (isset($_POST['award_clan']) && !$ro){
  if ($_POST['credits'] > 0) {
      // get all clan tours
    $db->select("clan_teams", "*", "CLAN_ID=".$_GET['clan_id']." and TEAM_ID=".$_POST['team_id']);
    if ($row = $db->nextRow()) {
      $season= $row['SEASON_ID']; 
    }
    // get all clan members
    $sql = "SELECT CM.USER_ID FROM clan_members CM , manager_users MU
		where CM.clan_id=".$_GET['clan_id']."
			and CM.status in (1,2,4)
			and MU.USER_ID=CM.USER_ID
			and MU.SEASON_ID=".$season;
    $db->query($sql);
    $clan_members = "";
    $clan_members_ar = array();
    $pre = "";
    while ($row = $db->nextRow()) {
      $clan_members .= $pre.$row['USER_ID'];
      $pre = ",";
      $clan_members_ar[] = $row['USER_ID'];
    }

    $sql = "SELECT distinct MCTT.TOUR_ID FROM manager_clan_teams_tours MCTT 
		where MCTT.team_id=".$_POST['team_id']." ORDER BY MCTT.TOUR_ID";
    $db->query($sql);
    $clan_team_tours = "";
    $clan_team_tours_ar = array();
    $pre = "";
    while ($row = $db->nextRow()) {
      $clan_team_tours .= $pre.$row['TOUR_ID'];
      $pre = ",";
      $clan_team_tours_ar[] = $row['TOUR_ID'];
    }
  
    $clan_team_members = "";
  
    $sql = "select U.USER_ID, U.USER_NAME, MUT.TOUR_ID, MUT.POINTS, A.USER_ID as IN_TEAM, MS.WEALTH AS MONEY 
		from manager_users MU,  users U, manager_users_tours MUT
		    LEFT JOIN (SELECT CTM.USER_ID, MT.NUMBER 
		    FROM clan_team_members CTM, manager_tours MT
			 WHERE 
			   ((MT.START_DATE > CTM.DATE_JOINED AND MT.END_DATE < CTM.DATE_LEFT) OR
			   (MT.START_DATE > CTM.DATE_JOINED AND CTM.DATE_LEFT = '0000-00-00 00:00:00'))
			   AND MT.SEASON_ID=".$season."
			    AND MT.NUMBER IN (".$clan_team_tours.")
			    AND CTM.TEAM_ID=".$_POST['team_id'].")  A ON A.USER_ID=MUT.USER_ID and A.NUMBER=MUT.TOUR_ID
                      LEFT JOIN manager_standings MS ON MS.USER_ID=MUT.USER_ID AND MS.MSEASON_ID=".$season."
	WHERE MU.USER_ID IN (".$clan_members.")
		AND MU.SEASON_ID=".$season."
		and U.USER_ID = MU.USER_ID
		and MUT.USER_ID=MU.USER_ID
		and MUT.SEASON_ID=MU.SEASON_ID
		and MUT.TOUR_ID IN (".$clan_team_tours.")";
  
    $db->query($sql);
   ////  echo $sql;
    $summary = array();
    $contribution = array();
    $total_points = 0;
    while ($row = $db->nextRow()) {
      $summary[$row['USER_ID']]['USER_NAME'] = $row['USER_NAME'];
      $summary[$row['USER_ID']]['MONEY'] = $row['MONEY'];
      $summary[$row['USER_ID']][$row['TOUR_ID']] = $row;
      if ($row['IN_TEAM'] > 0) {
        if (!isset($contribution[$row['USER_ID']]))
          $contribution[$row['USER_ID']]['POINTS'] = 0;
        $contribution[$row['USER_ID']]['POINTS'] += $row['POINTS'];
        $total_points += $row['POINTS'];
      }
    }
   
    foreach ($contribution as &$contr) {
      $contr['PERCENT'] = round($contr['POINTS']*100/$total_points,2);
    }

    $credits = new Credits();
    $credits->updateClanCredits($_GET['clan_id'], $_POST['credits']/2); 
    $clan_log = new ClanLog();
    $clan_log->logEvent($_GET['clan_id'], 7, $_POST['credits']/2, '', $_POST['team_id']);;

    $credit_log = new CreditsLog();
    foreach ($clan_members_ar as $user_id) {
      if (isset($contribution[$user_id]) && $contribution[$user_id]['PERCENT'] > 0) {
        $award_credits =  $_POST['credits']*$contribution[$user_id]['PERCENT']/200;
	echo $summary[$user_id]['USER_NAME'] . " => " . $award_credits . "<br>";        
        $credits->updateCredits($user_id, $award_credits, false); 
        $credit_log->logEvent($user_id, 27, $award_credits);
      }
    }

    unset($sdata);
    $sdata['AWARDED'] = $_POST['credits'];
    $db->update("clan_teams", $sdata, "TEAM_ID=".$_POST['team_id']. " AND CLAN_ID=".$_GET['clan_id']);
  }
}

// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['clan_id'])) {
  // edit

  $db->select('clans', 'CLAN_NAME', "CLAN_ID=".$_GET['clan_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: clans.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
  $data['CLAN_ID']=$_GET['clan_id'];


    $sql='SELECT UA.USER_ID, UA.DATE_JOINED, UA.LEVEL, U.USER_NAME
             FROM clan_members UA, users U
             WHERE UA.CLAN_ID='.$_GET['clan_id'].'
		AND UA.STATUS in (1,2)
		and U.USER_ID=UA.USER_ID
            ORDER BY U.USER_NAME ASC';
//echo $sql;
     $db->query($sql);

     $t=0;
     while ($row = $db->nextRow()) {
       $data['MEMBERS'][$t] = $row;
       if ($t & 2 > 0) 
         $data['MEMBERS'][$t]['ODD'][0]['X'] = 1;
       $t++;    
     }

    $sql='SELECT UA.CLAN_ID, UA.TEAM_ID, MSD.SEASON_TITLE, UA.TEAM_NAME, UA.AWARDED, NOW() > U.END_DATE as FINISHED
             FROM clan_teams UA, manager_seasons U
                 left join manager_seasons_details MSD on MSD.SEASON_ID=U.SEASON_ID
			AND MSD.LANG_ID='.$_SESSION['lang_id'].'
             WHERE UA.CLAN_ID='.$_GET['clan_id'].'
		and U.SEASON_ID=UA.SEASON_ID
            ORDER BY U.START_DATE DESC';
//echo $sql;
     $db->query($sql);

     $t=0;
     while ($row = $db->nextRow()) {
       $data['TEAMS'][$t] = $row;
       if ($row['AWARDED'] == 0 && $row['FINISHED'] == 1)
         $data['TEAMS'][$t]['CAN_AWARD'][0]['TEAM_ID'] = $row['TEAM_ID'];
       if ($t & 2 > 0) 
         $data['TEAMS'][$t]['ODD'][0]['X'] = 1;
       $t++;    
     }


}

ob_end_flush();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/clan_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>