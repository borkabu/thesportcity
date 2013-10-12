<?php
/*
===============================================================================
cat.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of categories
  - deletes categories

TABLES USED: 
  - BASKET.CATS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/manager_log.inc.php');

$ro = false;
// activate
$db->showquery=true;
if (isset($_GET['type']) && $_GET['type'] == 'manager_price' ) {
	// activate
	if (isset($_GET['activate']) && !$ro) {
	  $db->update('manager_players', array('PUBLISH' => "'Y'"),'PLAYER_ID='.$_GET['user_id'].' AND SEASON_ID='.$_GET['season_id']);
	  $db->update('manager_market', array('PUBLISH' => "'Y'"),'USER_ID='.$_GET['user_id'].' AND SEASON_ID='.$_GET['season_id']);
	  $manager_log = new ManagerLog();
	  $manager_log->logEvent($_GET['user_id'], 7, 0, $_GET['season_id'], '', '');
	  $data['ACTIVATED'][0]['TYPE'] = $_GET['type'];
	  $data['ACTIVATED'][0]['USER_ID'] = $_GET['user_id'];
	  $data['ACTIVATED'][0]['SEASON_ID'] = $_GET['season_id'];
	}
	// deactivate
	if (isset($_GET['deactivate']) && !$ro) {
	  $db->update('manager_players', array('PUBLISH' => "'N'"),'PLAYER_ID='.$_GET['user_id'].' AND SEASON_ID='.$_GET['season_id']);
	  $db->update('manager_market', array('PUBLISH' => "'N'"),'USER_ID='.$_GET['user_id'].' AND SEASON_ID='.$_GET['season_id']);
	  $manager_log = new ManagerLog();
	  $manager_log->logEvent($_GET['user_id'], 6, 0, $_GET['season_id'], '', '');
	  $data['DEACTIVATED'][0]['TYPE'] = $_GET['type'];
	  $data['DEACTIVATED'][0]['USER_ID'] = $_GET['user_id'];
	  $data['DEACTIVATED'][0]['SEASON_ID'] = $_GET['season_id'];
	}
}
// --- END DELETE -------------------------------------------------------------

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/activate_deactivate.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>