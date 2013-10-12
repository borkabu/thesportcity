<?php
/*
===============================================================================
tot_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit totalizator records
  - review totalizator votes
  - select totalizator winners
  - create new totlizator record

TABLES USED: 
  - BASKET.TOTALIZATORS
  - BASKET.TOTALIZATOR_VOTES
  - BASKET.GAMES
  - BASKET.USERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');
 
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
include('../class/session.inc.php');
include('../class/headers.inc.php');

include('../class/wager_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_WAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // update fields
    $loaded = 0;
    for ($c = 0; $c < sizeof($_POST['game_id']); $c++) {
     if (isset($_POST['include'][$c])) {
      unset($sdata);
      $upd = FALSE;
      $sdata['WSEASON_ID'] = $_POST['season_id'];
      //$sdata['DESCR'] = "'".$descr."'";
      $sdata['PUBLISH'] = "'Y'";
      $sdata['GAME_ID'] = $_POST['include'][$c];
      $sdata['START_DATE'] = "NOW()";
$db->showquery = true;
      $db->insert('wager_games', $sdata); 
      $loaded++;
     }
    }

    if ($loaded > 0) {
      $wager_log = new WagerLog();
      $wager_log->logEvent(1, $loaded, $_POST['season_id']);
    }
    
/*    if (!$error) {
      // redirect to list page
      $db->close();
      header('Location: '.$HTTP_POST_VARS['referer']);
      exit;
    }*/
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
  // edit
  $sql = "SELECT 
            SUBSTRING(G.START_DATE, 1, 16) END_DATE, SD.SEASON_TITLE aS SEASON_TITLE,
            G.TEAM_ID1, G.TEAM_ID2, G.GAME_ID,
            CONCAT(T1.TEAM_NAME, ' - ', T2.TEAM_NAME) AS TITLE
         FROM
            wager_subseasons WS, manager_seasons MS, manager_subseasons MSS,
	    manager_tours MT, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G 
              LEFT JOIN teams T1 ON T1.TEAM_ID=G.TEAM_ID1 
              LEFT JOIN teams T2 ON T2.TEAM_ID=G.TEAM_ID2
           WHERE
            G.START_DATE > NOW()
            AND G.GAME_ID NOT IN (SELECT GAME_ID FROM wager_games WG WHERE WG.WSEASON_ID=".$_GET['season_id'].")
            AND G.SEASON_ID=S.SEASON_ID
	    AND S.SEASON_ID=MSS.season_id
	    AND MSS.MSEASON_ID=MS.SEASON_ID
	    AND MS.SEASON_ID=WS.SEASON_ID
	    AND WS.WSEASON_ID=".$_GET['season_id']."
	    AND MT.SEASON_ID=MS.SEASON_ID
	    AND G.START_DATE > MT.START_DATE
	    AND G.START_DATE < MT.END_DATE
         ORDER BY G.START_DATE";
  $db->query($sql);
echo $sql;                 		            
//                                                    
  $c = 0; 
  while ($row = $db->nextRow()) {
    $data['GAMES'][$c] = $row;
    $data['GAMES'][$c]['INCLUDE'] = $frm->getField('adm_tot', 'include[]', $row['GAME_ID'], 'N');
    $c++;
  }
  $db->free();
   
    // new record
  $PRESET_VARS['publish'] = 'Y';


  $data['WSEASON_ID'] = inputWagerSeasons('season_id', $season_id);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/wager_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
