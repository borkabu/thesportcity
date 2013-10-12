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
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/bracket_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_ARRANGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

// get seasons
$db->select ( 'bracket_subseasons', 'SEASON_ID', 'WSEASON_ID=' . $_POST['season_id']);
$c = 0;
$ulist = '';
$pre = '';
while ( $row = $db->nextRow () )
{
	$ulist .= $pre . $row ['SEASON_ID'];
	$pre = ',';
	
	$c ++;
}
$db->free ();

//==== UPDATE!!!!!!!!!!!!!!!!!


if (isset ( $_POST['update'] ) && ! $ro)
{
	//set_time_limit(6000);
	//  ignore_user_abort(true);
	echo ".......<br>";
	ob_flush ();
	flush ();
	
	$db->showquery = true;

	$sql = "SELECT * FROM bracket_tours MTR
		WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
		      AND MTR.NUMBER=" . $_POST['tour_id'];
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];

	$db->free ();
	$sql = "SELECT G.GAME_ID, G.START_DATE, MT.NUMBER, 
             MT.START_DATE AS TOUR_START_DATE, MT.END_DATE AS TOUR_END_DATE, 
             COUNT(R.SCORE) RES
	     FROM bracket_tours MT, seasons S, games_races G
                  LEFT JOIN results_races R ON R.GAME_ID = G.GAME_ID
	    WHERE MT.season_id=" . $_POST['season_id'] . "
	      and S.season_id in (" . $ulist . ") 
	      and G.season_id=S.SEASON_ID
              and MT.NUMBER= " . $_POST['tour_id'] . "
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            GROUP BY G.GAME_ID
            ORDER BY MT.NUMBER, G.START_DATE";
	
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		//echo $row['TEAM_ID1']." - ".$row['TEAM_ID2']."<br>";
		if ($row ['RES'] > 0)
		{
			$glist [$c] = $row ['GAME_ID'];
			$c ++;
		}
	}
	$db->free ();

        $sql = "SELECT COUNT(R.PLACE) PILOTS
	        FROM games_races G, members M
		    LEFT JOIN results_races R ON M.USER_ID=R.USER_ID 
			AND R.GAME_ID =".$game_id."
	        WHERE G.GAME_ID=".$game_id."
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= G.START_DATE OR M.DATE_EXPIRED IS NULL)";
	$db->query ( $sql );
	if ( $row = $db->nextRow () ) {
          $pilots = $row['PILOTS'];
        }

	foreach ($glist as $game_id) {
             $sql = "SELECT BA.USER_ID, 
		  if (COUNT(BA.PLACE)-".$pilots." < 0, (COUNT(BA.PLACE)-".$pilots." < 0) * 5, 0) PENALTY, 
		  SUM(if (R.PLACE IS NULL, 0, if (5 - ABS(BA.PLACE - R.PLACE) > -5, 5 - ABS(BA.PLACE - R.PLACE), -5))) AS POINTS,
                 SUM(if (BA.PLACE = R.PLACE, 1, 0)) as MATCHES
	        FROM busers U, team_seasons TS, teams T, games_races G, members M
        	    LEFT JOIN bracket_arrangements BA ON BA.SEASON_ID=".$_POST['season_id']."
			AND BA.GAME_ID =".$game_id."
			AND BA.PILOT_ID=M.USER_ID 
		    LEFT JOIN results_races R ON M.USER_ID=R.USER_ID 
			AND R.GAME_ID =".$game_id."
	        WHERE TS.SEASON_ID=G.SEASON_ID
		  AND M.TEAM_ID =TS.TEAM_ID
        	  AND G.GAME_ID=".$game_id."
        	  AND M.USER_ID=U.USER_ID
  		  AND BA.PLACE is not null
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= G.START_DATE OR M.DATE_EXPIRED IS NULL)
		GROUP BY BA.USER_ID";
      echo $sql;
		echo "process users<br>";
		$db->query ( $sql );
		$c = 0;
		while ( $row = $db->nextRow () )
		{
			$musers [$row['USER_ID']] = $row;
			$c ++;
			echo ".";
			ob_flush ();
			flush ();
		}
        }
	//print_r($musers); exit;
	$db->free ();
	foreach($musers as $muser) {
		//      $sdata['POINTS'] = $musers[$i]['POINTS'];
		if (empty ( $muser ['USED_CHANGES'] ))
			$muser ['USED_CHANGES'] = 0;
		$sql = "REPLACE INTO bracket_users_tours
	              VALUES (" . $muser ['USER_ID'] . 
				"," . $_POST['tour_id'] . 
				"," . $_POST['season_id'] . 
				",0,0," . $muser ['POINTS'] - $muser ['PENALTY'].
				"," . $muser ['MATCHES'] . ")";
		$db->query ( $sql );
	}
	unset ( $sdata );
	$db->delete ( "bracket_standings", "MSEASON_ID=" . $_POST['season_id'] );
	
	$sql = "REPLACE INTO bracket_standings 
            SELECT MTR.USER_ID, SUM(MTR.POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, SUM(MTR.MATCHES) as MATCHES
		FROM bracket_users_tours MTR
			LEFT JOIN bracket_users_tours MTR1
				ON MTR.SEASON_ID=MTR1.SEASON_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN bracket_users_tours MTR2
				ON MTR.SEASON_ID=MTR2.SEASON_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.USER_ID=MTR2.USER_ID
                WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.USER_ID
		ORDER BY POINTS DESC";
	$db->query ( $sql );
	echo "update standings<br>";
	//    $db->free();
	

	$sql = "SELECT USER_ID 
            FROM bracket_standings         
           WHERE MSEASON_ID=" . $_POST['season_id'] . "
           ORDER BY POINTS DESC";
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
	}
	$db->free ();
	
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE'] = $people [$i] ['PLACE'];
		$db->update ( 'bracket_standings', $sdata, "MSEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
		$db->update ( 'bracket_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		unset ( $sdata );
	}
	$db->free ();

	$sql = "SELECT USER_ID, POINTS 
            FROM bracket_users_tours         
           WHERE SEASON_ID=" . $_POST['season_id'] . "
		 AND TOUR_ID=" . $_POST['tour_id'] . "
           ORDER BY POINTS DESC";
//echo $sql;
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
	}
	$db->free ();
	
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE_TOUR'] = $i+1;
		$db->update ( 'bracket_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		unset ( $sdata );
	}
	$db->free ();

    $bracket_log = new BracketLog();
    $bracket_log->logEvent(3, $_POST['season_id']);
}

?>