<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
season_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit tournament seasons
  - create new tournament season

TABLES USED: 
  - BASKET.SEASONS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
ob_start ();
// includes
include ('../class/conf.inc.php');
include ('../class/func.inc.php');
include ('../class/adm_menu.php');
include ('../class/update.inc.php');

// classes
include ('../class/db.class.php');
include ('../class/template.class.php');
include ('../class/language.class.php');
include ('../class/form.class.php');

// connections
include ('../class/db_connect.inc.php');
$tpl = new template ( );
$frm = new form ( );

// security layer
include('../smarty/libs/Smarty.class.php');
 $smarty = new Smarty;
 //$smarty->debugging = true;
 $smarty->registerPlugin("function","translate", "get_translation");


include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/manager.inc.php');
include('../class/box.inc.php');
include('../class/email.inc.php');
include('../class/pm.inc.php');
include('../class/user.inc.php');
include('../class/notification.inc.php');
include('../lib/manager_config.inc.php');
include('../class/manager_log.inc.php');
include('../class/manager_rating.inc.php');
include('../class/manager_tournament_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

//==== UPDATE!!!!!!!!!!!!!!!!!

if (isset ( $_GET['invite'] ) && ! $ro && isset($_GET['season_id'] )) {
	set_time_limit(6000);
	//  ignore_user_abort(true);
	echo ".......<br>";
	ob_flush ();
	flush ();
	
	$db->showquery = true;

        $manager = new Manager($_GET['season_id']);
        $sql = "SELECT SEASON_TITLE, LANG_ID from manager_seasons_details where SEASON_ID=".$_GET['season_id'];
        $db->query($sql);
        $titles = array();
        while ($row = $db->nextRow()) { 
          $titles[$row['LANG_ID']] = $row['SEASON_TITLE'];
        }

        $sql = "SELECT MT.START_DATE FROM manager_tours MT
			WHERE MT.SEASON_ID=".$_GET['season_id']."
				AND MT.NUMBER = 1";
echo $sql;
        $db->query($sql);
        $row = $db->nextRow();
        $start_date = $row['START_DATE'];

        $sql="SELECT U.USER_NAME, U.USER_ID, L.ID, U.LAST_LANG FROM users U, languages L 
			WHERE L.SHORT_CODE=U.LAST_LANG
			 AND U.ACTIVE='Y'
			 AND U.USER_ID NOT IN (select USER_ID from manager_users where season_id=".$_GET['season_id'].")";
        $db->query($sql);
echo $sql;

        $email = new Email($langs, $_SESSION['_lang']);

        $users = array();
        while ($row = $db->nextRow()) { 
          $user = $row;
	  $user['SEASON_TITLE'] = $titles[$row['ID']];
          $users[] = $user;
        }

        foreach($users as $user) {
          include($conf_home_dir.'class/ss_lang_'.$user['LAST_LANG'].'.inc.php');
          $edata['USER_NAME'] = $user['USER_NAME'];
          $edata['SEASON_TITLE'] = $user['SEASON_TITLE'];
	  $edata['START_DATE'] = $start_date;
	  $edata['URL'] = "f_manager_control.php?mseason_id=".$_GET['season_id'];
          if ($manager->allow_solo) {
   	    $edata['URL_SOLO'] = "solo_manager_control.php?mseason_id=".$_GET['season_id'];
          }
	  $subject = $langs['LANG_EMAIL_MANAGER_INVITE_SUBJECT'];
    	  $descr = $email->getEmailFromTemplate ('email_manager_invite', $edata) ;
          $pm = new PM();
	  $pm->createSystemPM($user['USER_ID'], $subject, $descr);
          echo "user ".$edata['USER_NAME']." invited<br>";
	  ob_flush ();
  	  flush ();
  	} 
    echo "Done<br>";    

    unset($sdata);
    $sdata['INVITED'] = 1;
    $db->update("manager_seasons", $sdata, "SEASON_ID=".$_GET['season_id']);
}

?>