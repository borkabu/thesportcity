<?php
/*
===============================================================================
team_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit team records
  - edit keywords
  - create new team record

TABLES USED: 
  - BASKET.TEAMS
  - BASKET.TEAM_TOURNAMENTS
  - BASKET.TOURNAMENTS
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
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

if (empty($_SESSION["_admin"][MENU_ACTIONS_ARRANGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$db->showquery=true;
$ro = false;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'RO') == 0)
  $ro = TRUE;

if (isset($_GET['del2']) && !$ro && strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'FA') == 0) {
  $db->delete('bracket_subseasons', 'ID='.$_GET['del2']);
}

if (isset($_GET['del']) && ! $ro && strcmp ( $_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'FA' ) == 0)
{
	$db->delete( 'bracket_tours', 'SEASON_ID='.$_GET['season_id'].' AND TOUR_ID='.$_GET['del'] );
}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = array ('pic_location', 'pic2_location' );
  $i_fields = array ('prize_fund', 'newsletter_id');
  $d_fields = array('start_date', 'end_date');
  $c_fields = array('publish');
  
  $s_fields_d = array('tseason_title', 'prizes');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');

  $r_fields = array('tseason_title', 
                    'start_date_y', 'start_date_m', 'start_date_d',
                    'end_date_y', 'end_date_m', 'end_date_d');

  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
    
    // proceed to database updates
    if (!empty($_GET['season_id'])) {
      // UPDATE
      $db->update('bracket_seasons', $sdata, "SEASON_ID=".$_GET['season_id']);
      $tdata['season_id'] = $_GET['season_id'];
      $db->select('bracket_seasons_details', "*", "SEASON_ID=".$_GET["season_id"]." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
	  $db->update('bracket_seasons_details', $tdata, "SEASON_ID=".$_GET["season_id"]." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('bracket_seasons_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('bracket_seasons', $sdata);
      $tdata['season_id'] = $db->id();
      $db->insert('bracket_seasons_details',$tdata);
    }

    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}

if(isset($_POST['load_tours'])&&!$ro){
  $tours = explode("\n", $_POST['descr']);

$db->showquery=true;
  $rowcorr =0;
  $rowwrong =0;
  $length= count($tours); 
  for ($i=0; $i<$length; $i++) {
    $fields=explode(",", $tours[$i]);

    unset($sdata);
    $sdata['season_id'] = $_POST['season_id'];
    $sdata['number'] = $fields[2];
    $sdata['start_date'] = "'".$fields[0]."'";
    $sdata['end_date'] = "'".$fields[1]."'";
    
    $db->insert('bracket_tours', $sdata);
  }
}

// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['season_id'])) {
  // edit
  $sql = "SELECT TS.SEASON_ID, TSD.TSEASON_TITLE, TSD.PRIZES, TS.PUBLISH, 
           TS.NEWSLETTER_ID, TS.PRIZE_FUND, TS.PIC_LOCATION, TS.PIC2_LOCATION, 
           SUBSTRING(START_DATE, 1, 10) START_DATE, 
           SUBSTRING(END_DATE, 1, 10) END_DATE
        FROM bracket_seasons  TS
		left JOIN bracket_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
        WHERE TS.SEASON_ID=".$_GET['season_id'];

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: bracket_season.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
  $data['SEASON_ID'] = $_GET['season_id'];  
  $data['NEWSLETTER_ID'] = inputBracketNewsletters('newsletter_id', isset($PRESET_VARS['newsletter_id']) ? $PRESET_VARS['newsletter_id'] : '');
  // subseasons
  $sql = "SELECT FD.SEASON_TITLE, WSS.*
        FROM bracket_subseasons WSS, bracket_seasons WS, seasons MS
		LEFT JOIN seasons_details FD ON FD.SEASON_ID=MS.SEASON_ID AND FD.LANG_ID=".$_SESSION['lang_id']."
        WHERE WSS.WSEASON_ID=WS.SEASON_ID
              AND WS.SEASON_ID=".$_GET['season_id']."
              AND WSS.SEASON_ID=MS.SEASON_ID";
//echo $sql;
 $db->query($sql);
 $t = 0;
 while ( $row = $db->nextRow () ) {
	$data['SUBSEASONS'][$t] = $row;
	if (strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'FA' ) == 0)
		$data['SUBSEASONS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del2', $row['ID']);
	if ($t & 2 > 0)
		$data['SUBSEASONS'][$t]['ODD'][0]['X'] = 1;
	$t++;
 }
 if ($t == 0) {
   $data['SUBSEASON_NORECORDS'][0]['X'] = 1;
 }

  // tours
   $sql = 'SELECT MS.*, MT.*, MT.START_DATE < NOW() AS UPDATABLE
        FROM bracket_seasons MS, bracket_tours MT
        WHERE MS.SEASON_ID=MT.SEASON_ID
              AND MS.SEASON_ID='.$_GET['season_id'].'
        ORDER BY MT.START_DATE DESC';

   $db->query ( $sql );
   $t = 0;
   while ( $row = $db->nextRow () ) {
	$data ['TOURS'][$row['NUMBER']] = $row;
	if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'FA') == 0)
  	  $data ['TOURS'] [$row['NUMBER']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);
//	if (isset($data['MARKET_OPEN'][0]['MARKET']) && $data['MARKET_OPEN'][0]['MARKET'] == "Y" && $row['UPDATABLE'] == 1) {
 	  $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
	  $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['SEASON_ID'] = $row['SEASON_ID'];
//	}

	if ($t & 2 > 0)
	  $data['TOURS'][$row['NUMBER']]['ODD'][0]['X'] = 1;
	$t++;
   }
   if ($t == 0) {
     $data['TOUR_NORECORDS'][0]['X'] = 1;
   }

}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

// get common inputs
$params['onclick'] = 'formtag();';
$data['YF'] = date('Y');
$data['YT'] = $data['YF']+1;

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/bracket_season_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>