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

// --- BEGIN UPDATE -----------------------------------------------------------

if (isset($_GET['del2']) && ! $ro && strcmp ( $_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA' ) == 0)
{
	$db->delete('manager_subseasons', 'ID='.$_GET['del2']);
}

if (isset($_GET['del']) && ! $ro && strcmp ( $_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA' ) == 0)
{
	$db->delete( 'manager_tours', 'SEASON_ID='.$_GET['season_id'].' AND TOUR_ID='.$_GET['del'] );
}

$manager = new Manager($_GET['season_id']);
if (isset($_POST['market_open'] ) && ! $ro)
{
  $manager->openMarket();
}

if (isset($_POST['market_close'] ) && ! $ro)
{
  $manager->closeMarket();
}

/*
if (isset($_POST['season_over'] ) && ! $ro)
{
  unset($sdata);
  $sdata['MARKET'] = 'N';
  $db->update('manager_statistics', $sdata, 'SEASON_ID='.$_POST['season_id']);
}

if (isset($_POST['season_not_over'] ) && ! $ro)
{
  unset($sdata);
  $sdata['MARKET'] = 'Y';
  $db->update('manager_statistics', $sdata, 'SEASON_ID='.$_POST['season_id']);
}
*/
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
//$db->showquery=true;
	// required fields
	$s_fields = array ('pic_location', 'pic2_location');
	$i_fields = array ('money', 'money_stock', 'transactions', 'prize_fund', 'donated', 'newsletter_id', 'max_players', 'sport_id', 'rvs_leagues_last_tour');
	$d_fields = array ('start_date', 'end_date' );
	$c_fields = array ('publish', 'captaincy', 'allow_substitutes', 'allow_stock', 'allow_rvs_leagues', 'allow_clan_teams', 'allow_solo');
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
            if (!empty($_GET['season_id']) && !empty($_POST["lang_id"])) {
		// UPDATE
		$db->update('manager_seasons', $sdata, "SEASON_ID=".$_GET['season_id']);
	        $tdata['season_id'] = $_GET["season_id"];
	        $db->replace('manager_seasons_details', $tdata);
	    } else {
		// INSERT
		$db->insert ('manager_seasons', $sdata );
	        $tdata['season_id'] = $db->id();
	        $db->insert('manager_seasons_details',$tdata);
		unset ( $sdata );
		$sdata ['SEASON_ID'] = $tdata['season_id'];
		$sdata ['DATE_CREATED'] = "NOW()";
		$sdata ['MAXIMUM'] = 0;
		$sdata ['MINIMUM'] = 0;
		$sdata ['AVER'] = 0;
		$sdata ['AGGREG'] = 0;
		$sdata ['MARKET'] = "'N'";
		$sdata ['STATE'] = 0;
		$sdata ['MAX_POINTS'] = 0;
		$db->insert ( 'manager_statistics', $sdata );
  	    } 
		
	  // redirect to news page
	  header ( 'Location: '.$_POST['referer'] );
	  exit ();
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
    
    $db->insert('manager_tours', $sdata);
  }
}
// --- END SAVE ---------------------------------------------------------------

$db->select("manager_statistics", "*", "SEASON_ID=".$_GET['season_id']);

$row = $db->nextRow();

if ($row['MARKET'] == 'Y') {
  $data['MARKET_CLOSE'][0]['MARKET'] = 'Y';
  $data['MARKET_CLOSE'][0]['SEASON_ID'] = $_GET['season_id'];
}
else {
  $data['MARKET_OPEN'][0]['MARKET'] = 'Y';
  $data['MARKET_OPEN'][0]['SEASON_ID'] = $_GET['season_id'];
}

/*$can_price_adjustment = false;
if ($row['PRICE_ADJUSTMENT'] == '0') {
 $can_price_adjustment = true;
} */

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

//$db->showquery=true;
// new or edit?
if (isset($_GET['season_id'])) {
  $sql = "SELECT F.SEASON_ID, FD.SEASON_TITLE, F.PUBLISH, F.MONEY, F.MONEY_STOCK, F.TRANSACTIONS, F.PRIZE_FUND, F.DONATED,
		F.MAX_PLAYERS, F.SPORT_ID, F.CAPTAINCY, F.ALLOW_SUBSTITUTES, F.ALLOW_STOCK, F.ALLOW_RVS_LEAGUES, F.ALLOW_CLAN_TEAMS, F.ALLOW_SOLO,
                SUBSTRING(F.START_DATE, 1, 10) START_DATE, F.PIC_LOCATION, F.PIC2_LOCATION,F.NEWSLETTER_ID,
                SUBSTRING(F.END_DATE, 1, 10) END_DATE, FD.PRIZES, F.RVS_LEAGUES_LAST_TOUR
			FROM manager_seasons F LEFT JOIN manager_seasons_details FD ON FD.SEASON_ID=F.SEASON_ID AND FD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE F.SEASON_ID=".$_GET['season_id'] ;
  $db->query($sql);
//echo $sql;
  if (! $row = $db->nextRow ()) {
	// ERROR! No such record. redirect to list
	header ( 'Location: manager_season.php' );
	exit ();
  } else {
	// populate $PRESET_VARS with data so form class can use their values
	while (list($key, $val) = each($row)) {
	    $PRESET_VARS [strtolower ( $key )] = $val;
	}
  }
  $db->free ();
	
  // tours
   $sql = "SELECT MS.*, MT.*, 
            MT.START_DATE < NOW() AND DATE_ADD(MT.END_DATE, INTERVAL 1 DAY ) > NOW() 
	    AND MSS.MARKET='N' AS UPDATABLE, MT.END_DATE < MS.END_DATE NO_ERRORS
        FROM manager_seasons MS, manager_tours MT, manager_statistics MSS
        WHERE MS.SEASON_ID=MT.SEASON_ID
              AND MSS.SEASON_ID=MT.SEASON_ID
              AND MS.SEASON_ID=".$_GET['season_id']."
        ORDER BY MT.START_DATE DESC";

//echo $sql;
   $db->query ( $sql );
   $t = 0;
//   $data['PRICE_ADJUSTMENT'][0]['SEASON_ID'] = $row['SEASON_ID'];
   while ( $row = $db->nextRow () ) {
	$data ['TOURS'][$row['NUMBER']] = $row;
        if ($row['NO_ERRORS'] == 0)
          $data['TOUR_ERRORS'][0]['X'] = 1;
	if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
  	  $data ['TOURS'] [$row['NUMBER']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);
//	if (isset($data['MARKET_OPEN'][0]['MARKET']) && $data['MARKET_OPEN'][0]['MARKET'] == "Y" && $row['UPDATABLE'] == 1) {
          if ($row['SPORT_ID'] != 4) {
  	    $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
	    $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['SEASON_ID'] = $row['SEASON_ID'];
	    $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOTAL_GAMES'] = $row['TOTAL_GAMES'];
	    $data['TOURS'][$row['NUMBER']]['UPDATE'][0]['COUNTED_GAMES'] = $row['COUNTED_GAMES'];
  	    $data['TOURS'][$row['NUMBER']]['UPDATE_RATING'][0]['TOUR_ID'] = $row['NUMBER'];
	    $data['TOURS'][$row['NUMBER']]['UPDATE_RATING'][0]['SEASON_ID'] = $row['SEASON_ID'];
          } else {
  	    $data['TOURS'][$row['NUMBER']]['UPDATE_IND'][0]['TOUR_ID'] = $row['NUMBER'];
	    $data['TOURS'][$row['NUMBER']]['UPDATE_IND'][0]['SEASON_ID'] = $row['SEASON_ID'];
  	    $data['TOURS'][$row['NUMBER']]['LOAD_RES'][0]['TOUR_ID'] = $row['NUMBER'];
	    $data['TOURS'][$row['NUMBER']]['LOAD_RES'][0]['SEASON_ID'] = $row['SEASON_ID'];

          }
//	}
        if ($row['NUMBER'] >= 5) {
          $data['PRICE_ADJUSTMENT'][0]['SEASON_ID'] = $row['SEASON_ID'];
        }

	if ($t & 2 > 0)
	  $data['TOURS'][$row['NUMBER']]['ODD'][0]['X'] = 1;
	$t++;
   }
   if ($t == 0) {
     $data['TOUR_NORECORDS'][0]['X'] = 1;
   }

   $data['RECOMMENDED_TRANSACTIONS'] = $PRESET_VARS ['max_players']*$t;

   $sql = 'SELECT distinct MC.tour_id as NUMBER, MT.END_DATE < NOW() AS CHALLENGE_UPDATABLE
        FROM manager_challenges MC, manager_tours MT
        WHERE MC.SEASON_ID='.$_GET['season_id'].'
	      and MC.STATUS=2 
	      and MC.TOUR_ID=MT.number 
	      and MT.SEASON_ID='.$_GET['season_id'].'
        ORDER BY MT.NUMBER DESC';
   $db->query ( $sql );
   while ( $row = $db->nextRow () ) {
	if (isset($data['MARKET_CLOSE'][0]['MARKET']) && $data['MARKET_CLOSE'][0]['MARKET'] == "Y" && $row['CHALLENGE_UPDATABLE'] == 1) {
		$data['TOURS'][$row['NUMBER']]['UPDATE_CHALLENGES'][0]['TOUR_ID'] = $row['NUMBER'];
		$data['TOURS'][$row['NUMBER']]['UPDATE_CHALLENGES'][0]['SEASON_ID'] = $_GET['season_id'];
	}
   }

   $sql = 'SELECT distinct MC.tour_id as NUMBER, MT.END_DATE < NOW() AS CHALLENGE_UPDATABLE
        FROM manager_battles MC, manager_tours MT
        WHERE MC.SEASON_ID='.$_GET['season_id'].'
	      and MC.STATUS=2 
	      and MC.TOUR_ID=MT.number 
	      and MT.SEASON_ID='.$_GET['season_id'].'
        ORDER BY MT.NUMBER DESC';
   $db->query ( $sql );
   while ( $row = $db->nextRow () ) {
	if (isset($data['MARKET_CLOSE'][0]['MARKET']) && $data['MARKET_CLOSE'][0]['MARKET'] == "Y" && $row['CHALLENGE_UPDATABLE'] == 1) {
		$data['TOURS'][$row['NUMBER']]['UPDATE_CHALLENGES'][0]['TOUR_ID'] = $row['NUMBER'];
		$data['TOURS'][$row['NUMBER']]['UPDATE_CHALLENGES'][0]['SEASON_ID'] = $_GET['season_id'];
	}
   }

   $current_tour = $manager->getCurrentTour();
   $sql = 'SELECT distinct MT.NUMBER, MT.END_DATE < NOW() AS TOUR_COMPLETABLE
        FROM manager_tournament MC, manager_tours MT, manager_tournament_tours MTT
        WHERE MC.SEASON_ID='.$_GET['season_id'].'
	      and MC.STATUS=2 
              AND MTT.MT_ID=MC.MT_ID
	      and MTT.NUMBER=MT.NUMBER-MC.START_TOUR+1
	      and MT.SEASON_ID=MC.SEASON_ID
              and MTT.COMPLETED=0 
        ORDER BY MT.NUMBER DESC';
//echo $sql;
   $db->query ( $sql );
echo $data['MARKET_CLOSE'][0]['MARKET'];
   while ( $row = $db->nextRow () ) {
	if (((isset($data['MARKET_CLOSE'][0]['MARKET']) && $data['MARKET_CLOSE'][0]['MARKET'] == "Y") || $current_tour > $row['NUMBER']+1 ) && $row['TOUR_COMPLETABLE'] == 1) {
		$data['TOURS'][$row['NUMBER']]['COMPLETE_TOUR'][0]['TOUR_ID'] = $row['NUMBER'];
		$data['TOURS'][$row['NUMBER']]['COMPLETE_TOUR'][0]['SEASON_ID'] = $_GET['season_id'];
	}
   }


   $db->free ();
   // subseasons
  $sql = "SELECT FD.SEASON_TITLE, MSS.*
  	FROM manager_subseasons MSS, manager_seasons MS, seasons S
		LEFT JOIN seasons_details FD ON FD.SEASON_ID=S.SEASON_ID AND FD.LANG_ID=".$_SESSION['lang_id']."
	  WHERE MSS.MSEASON_ID=MS.SEASON_ID
        AND MS.SEASON_ID=".$_GET['season_id']."
        AND MSS.SEASON_ID=S.SEASON_ID";
  $db->query($sql);
  $t = 0;
  while ( $row = $db->nextRow () ) {
	$data['SUBSEASONS'][$t] = $row;
	if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA' ) == 0)
		$data['SUBSEASONS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del2', $row['ID']);
	if ($t & 2 > 0)
		$data['SUBSEASONS'][$t]['ODD'][0]['X'] = 1;
	$t++;
  }
  if ($t == 0) {
    $data['SUBSEASON_NORECORDS'][0]['X'] = 1;
  }

  $db->free ();
  $data ['SEASON_ID'] = $_GET['season_id'];

  $db->select ( "manager_statistics", "*", "SEASON_ID=" . $_GET['season_id']);
  $row = $db->nextRow ();

  if ($row ['MARKET'] == 'Y') {
  	$data ['MARKET_CLOSE'] [0] ['MARKET'] = 'Y';
  	$data ['MARKET_CLOSE'] [0] ['SEASON_ID'] = $_GET['season_id'];
  } else {
  	$data ['MARKET_OPEN'] [0] ['MARKET'] = 'Y';
	$data ['MARKET_OPEN'] [0] ['SEASON_ID'] = $_GET['season_id'];
  }
  
  $db->select ( "manager_market", "AVG((CURRENT_VALUE_MONEY/1000) - 1) as CURRENT_VALUE_MONEY ", "CURRENT_VALUE_MONEY > 4000 AND PUBLISH='Y' AND SEASON_ID=" . $_GET['season_id']);
  $row = $db->nextRow ();
    $data['AVG'] = $row['CURRENT_VALUE_MONEY'];
  $data['RECOMMENDED_MONEY'] = (1+$row['CURRENT_VALUE_MONEY']) * ($PRESET_VARS['max_players']) * 1000;	
} else {
  	// adding record
  	$PRESET_VARS ['publish'] = 'Y';
  }

  // get common inputs
  $data ['YF'] = date ( 'Y' );
  $data ['YT'] = $data ['YF'] + 1;
  $data['NEWSLETTER_ID'] = inputManagerNewsletters('newsletter_id', isset($PRESET_VARS['newsletter_id']) ? $PRESET_VARS['newsletter_id'] : '');
  
  $opt['class']= "input";
  $opt['options'] = $msports;
  
  $data['SPORT_ID'] = $frm->getInput(FORM_INPUT_SELECT, 'sport_id', isset($PRESET_VARS['sport_id']) ? $PRESET_VARS['sport_id'] : 0, $opt, isset($PRESET_VARS['sport_id']) ? $PRESET_VARS['sport_id'] : 0);   
  
  // content
  $tpl->setCacheLevel ( TPL_CACHE_NOTHING );
  $tpl->setTemplateFile ( '../tpl/adm/manager_season_edit.tpl.html' );
  $tpl->addData ( $data );
  echo $tpl->parse ();

  // close connections
  include ('../class/db_close.inc.php');
?>