<?php
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
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

include('../class/prepare.inc.php');
// --- BEGIN UPDATE -----------------------------------------------------------

if (isset($_GET['del']) && ! $ro && strcmp ( $_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA' ) == 0)
{
	$db->delete( 'manager_tournament_tours', 'MT_ID='.$_GET['mt_id'].' AND TOUR_ID='.$_GET['del'] );
}

if (isset($_POST['add_tour']) && ! $ro && strcmp ( $_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA' ) == 0) {
  if (isset($_GET['mt_id'])) {  
$db->showquery=true;
    $db->select('manager_tournament_tours', 'MAX(number) NUMBER', 'MT_ID='.$_GET['mt_id']);
    $row = $db->nextRow ();
    $sql="INSERT INTO manager_tournament_tours (NUMBER, MT_ID, START_DATE, END_DATE, ROUND, DRAWN, COMPLETED)
		SELECT ".($row['NUMBER']+1).", ".$_GET['mt_id'].", MT.START_DATE, MT.END_DATE, 1, 0, 0
		 FROM manager_tours MT WHERE MT.season_id=".$_POST['season_id']." AND MT.number=".$_POST['tour_id'];
    $db->query($sql);
  }
}
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
	// required fields
	$s_fields = '';
	$i_fields = array ('season_id', 'fee');
	$d_fields = array ('start_date', 'end_date', 'registration_end_date' );
	$c_fields = array ('publish' );
	$r_fields = array ('start_date_y', 'start_date_m', 'start_date_d', 'end_date_y', 'end_date_m', 'end_date_d' );

	$s_fields_d = array('season_title', 'prizes');
	$d_fields_d = '';
	$c_fields_d = '';
  	$i_fields_d = array('lang_id');
  	$r_fields_d = array('season_title');
	
	// check for required fields
	if (!requiredFieldsOk ( $r_fields, $_POST ) || 
            !requiredFieldsOk ( $r_fields_d, $_POST ))
	{
	    $error = TRUE;
	    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
	}
	
	if (!$error)
	{
		// get save data
	    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields,  $_POST);
	    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
		
	    // proceed to database updates
            if (!empty($_GET['mt_id']) && !empty($_POST["lang_id"])) {
		// UPDATE
		$db->update('manager_tournament', $sdata, "MT_ID=".$_GET['mt_id']);
	        $tdata['mt_id'] = $_GET["mt_id"];
		$db->select('manager_tournament_details', "*", "MT_ID=".$_GET["mt_id"]." AND LANG_ID=".$_POST['lang_id']);
		if ($row = $db->nextRow())
		  $db->update('manager_tournament_details', $tdata, "MT_ID=".$_GET["mt_id"]." AND LANG_ID=".$_POST['lang_id']);
		else $db->insert('manager_tournament_details', $tdata);
	    } else {
		// INSERT
		$db->insert ('manager_tournament', $sdata );
	        $tdata['mt_id'] = $db->id();
	        $db->insert('manager_tournament_details',$tdata);
  	    } 
		
	  // redirect to news page
	  header ( 'Location: '.$_POST['referer'] );
	  exit ();
       }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

//$db->showquery=true;
// new or edit?
if (isset($_GET['mt_id'])) {
  $sql = "SELECT F.MT_ID, FD.SEASON_TITLE, F.PUBLISH, F.FEE, F.REGISTRATION_END_DATE,
                F.START_DATE, F.SEASON_ID,
                F.END_DATE, FD.PRIZES
			FROM manager_tournament F LEFT JOIN manager_tournament_details FD ON FD.MT_ID=F.MT_ID AND FD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE F.MT_ID=".$_GET['mt_id'] ;
  $db->query($sql);

  if (! $row = $db->nextRow ()) {
	// ERROR! No such record. redirect to list
	header ( 'Location: manager_tournament_season.php' );
	exit ();
  } else {
	// populate $PRESET_VARS with data so form class can use their values
	while (list($key, $val) = each($row)) {
	    $PRESET_VARS [strtolower ( $key )] = $val;
	}
	$data ['MT_ID'] = $_GET['mt_id'];		
  }
  $db->free ();

  // tours
   $sql = 'SELECT MS.*, MT.*, MT.START_DATE < NOW() AS UPDATABLE,
           MT.DRAWN
        FROM manager_tournament MS, manager_tournament_tours MT
        WHERE MS.MT_ID=MT.MT_ID
              AND MS.MT_ID='.$_GET['mt_id'].'
        ORDER BY MT.START_DATE ASC';
	$db->query ( $sql );
	$t = 0;
        $draw_allowed = false;
	while ( $row = $db->nextRow () )
	{
		$data ['TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']] = $row;
		if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
			$data ['TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);

		if ($row['COMPLETED'] == 0 && $row['UPDATABLE'] == 1 && $row['DRAWN'] == 1)
		{
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['MT_ID'] = $row['MT_ID'];
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['ROUND'] = $row['ROUND'];
		        $data['TOURS'][$row['NUMBER']]['COMPLETE'][0] = $row;
		}

		if ($row['COMPLETED'] == 1)
                  $draw_allowed = true;

		if ($row['ROUND'] == 1) {
		  if ($row['DRAWN'] == 0 && $row['UPDATABLE'] == 1 && $draw_allowed)
  		  {
			$draw_allowed = false;
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW'][0]['TOUR_ID'] = $row['NUMBER'];
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW'][0]['MT_ID'] = $row['MT_ID'];
		  } 
		}
		if ($row['COMPLETED'] == 0 && $row['DRAWN'] == 1) {
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW_COMMENCED'][0]['X'] = 1;
                }

		if ($row['COMPLETED'] == 1) {
			$data['TOURS'][$row['NUMBER']]['ROUND'][$row['ROUND']]['TOUR_COMPLETED'][0]['X'] = 1;
                }
		$t++;
	}
	if ($t == 0) {
	  $data['TOUR_NORECORDS'][0]['X'] = 1;
	}	

   // host tours
   if (isset($PRESET_VARS['season_id'])) {
     $sql = 'SELECT MS.*, MT.*
        FROM manager_seasons MS, manager_tours MT
        WHERE MS.SEASON_ID=MT.SEASON_ID
              AND MS.SEASON_ID='.$PRESET_VARS['season_id'].'
        ORDER BY MT.START_DATE DESC';

     $db->query ( $sql );
     $t = 0;
     while ( $row = $db->nextRow () ) {
	$data ['HOST_TOURS'][$row['NUMBER']] = $row;
//	$data ['HOST_TOURS'][$row['NUMBER']]['MT_ID'] = $_GET['mt_id'];
	$t++;
     }
   }

} else
{
	// adding record
	$PRESET_VARS ['publish'] = 'Y';
}

// get common inputs
$data ['YF'] = date ( 'Y' );
$data ['YT'] = $data ['YF'] + 1;
$data['SEASON_ID'] = inputManagerSeasons('season_id', isset($PRESET_VARS['season_id']) ? $PRESET_VARS['season_id'] : '', 80, true);
// content
$tpl->setCacheLevel ( TPL_CACHE_NOTHING );
$tpl->setTemplateFile ( '../tpl/adm/manager_tournament_edit.tpl.html' );
$tpl->addData ( $data );
echo $tpl->parse ();

// close connections
include ('../class/db_close.inc.php');
?>