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
include('../class/manager.inc.php');
include('../lib/manager_config.inc.php');
include('../class/manager_log.inc.php');
include('../class/solo_manager_log.inc.php');
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

// get seasons
$db->select ( 'manager_subseasons', 'SEASON_ID', 'MSEASON_ID=' . $_POST['season_id']);
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


if (isset ( $_POST['update'] ) && ! $ro) {
	set_time_limit(6000);
	//  ignore_user_abort(true);
	echo ".......<br>";
	ob_flush ();
	flush ();
	
	$db->showquery = true;

        $manager = new Manager($_POST['season_id']);
        $three_percent = $manager->default_money * 0.03;

	$sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
		      AND MTR.NUMBER=" . $_POST['tour_id'];
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];

	// get start values for everybody
	$sql = "SELECT MPS.PLAYER_ID, MPS.CURRENT_VALUE_MONEY, MPS.START_VALUE_MONEY, MPS.KOEFF,
        	         0 AS TOTAL_POINTS, 0 AS PLAYED, MPS.TOTAL_POINTS AS TOTAL_POINTS_PREV, MPS.PLAYED AS PLAYED_PREV
	            FROM manager_player_stats MPS
			LEFT JOIN manager_market MM ON MM.SEASON_ID=MPS.SEASON_ID
				AND MM.USER_ID=MPS.PLAYER_ID
		    WHERE MPS.SEASON_ID=" . $_POST['season_id'] . " and MPS.TOUR_ID =" . ($_POST['tour_id'] - 1);
	$db->query ( $sql );
	$players=array();
	while ( $row = $db->nextRow ()) {
	  $players[$row['PLAYER_ID']] = $row;
	  if ($_POST['tour_id'] == 1)
	    $players[$row['PLAYER_ID']]['CURRENT_VALUE_MONEY'] = $players[$row['PLAYER_ID']]['START_VALUE_MONEY'];
	}
	$db->free ();
	// get start values for everybody
	$sql = "SELECT MPS.PLAYER_ID, 
                 SUM(MPS.TOTAL_POINTS) AS TOTAL_POINTS_PREV,
                 SUM(MPS.PLAYED) AS PLAYED_PREV
            FROM manager_player_stats MPS
		LEFT JOIN manager_market MM ON MM.SEASON_ID=MPS.SEASON_ID
			AND MM.USER_ID=MPS.PLAYER_ID
	    WHERE MPS.SEASON_ID=" . $_POST['season_id'] . " and MPS.TOUR_ID <" . ($_POST['tour_id']) . "
            GROUP BY MPS.PLAYER_ID";
	$db->query ( $sql );
	while ( $row = $db->nextRow ())	{
	  $players[$row['PLAYER_ID']]['TOTAL_POINTS_PREV'] = $row['TOTAL_POINTS_PREV'];
	  $players[$row['PLAYER_ID']]['PLAYED_PREV'] = $row['PLAYED_PREV'];
	  $players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_PREV'] = 0;
	  $players[$row['PLAYER_ID']]['FLOATING_PLAYED'] = 0;

	}
	$db->free ();

        // get last 4 games points ... floating_total_points_prev

	$sql = "select avg(TOTAL_POINTS) FLOATING_TOTAL_POINTS_PREV, SUM(PLAYED) FLOATING_PLAYED, PLAYER_ID from (
		select u.*, @rank:=CASE WHEN @class <> u.PLAYER_ID THEN 1 ELSE @rank+1 END AS rn
		, @class:=u.PLAYER_ID 
		from
		(SELECT MPS.PLAYER_ID, MPS.TOTAL_POINTS, MPS.PLAYED
			FROM 
				manager_player_stats MPS, (SELECT @class:=0) r2
			WHERE 
			       MPS.SEASON_ID=" . $_POST['season_id'] . "
				and MPS.PLAYED>0
				and MPS.TOUR_ID < ".$_POST['tour_id']."
                 ORDER BY MPS.PLAYER_ID, MPS.TOUR_ID DESC
	) u,  (SELECT @rank:=1) r1
	) d where rn <= 4 group by player_id";
	$db->query ( $sql );
	while ( $row = $db->nextRow ())	{
	  $players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_PREV'] = $row['FLOATING_TOTAL_POINTS_PREV'];
	  $players[$row['PLAYER_ID']]['FLOATING_PLAYED'] = $row['FLOATING_PLAYED'];
	}
	$db->free ();

	// turning point
	$sql = "select sum(TOTAL_POINTS) FLOATING_TOTAL_POINTS_NEXT, SUM(PLAYED) FLOATING_PLAYED_NEXT, PLAYER_ID from (
		select u.*, @rank:=CASE WHEN @class <> u.PLAYER_ID THEN 1 ELSE @rank+1 END AS rn
		, @class:=u.PLAYER_ID 
		from
		(SELECT MPS.PLAYER_ID, MPS.TOTAL_POINTS, MPS.PLAYED
			FROM 
				manager_player_stats MPS, (SELECT @class:=0) r2
			WHERE 
			       MPS.SEASON_ID=" . $_POST['season_id'] . "
				and MPS.PLAYED>0
                 ORDER BY MPS.PLAYER_ID, MPS.TOUR_ID DESC
	) u,  (SELECT @rank:=1) r1
	) d where rn <= 4 group by player_id";
	$db->query ( $sql );
	while ( $row = $db->nextRow ())	{
	  $players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_NEXT'] = $row['FLOATING_TOTAL_POINTS_NEXT'];
	  $players[$row['PLAYER_ID']]['FLOATING_PLAYED_NEXT'] = $row['FLOATING_PLAYED_NEXT'];
	}
	$db->free ();


        if ($manager->sport_id != 3) {
	  $sql = "SELECT G.GAME_ID, G.START_DATE, MT.NUMBER, 
             MT.START_DATE AS TOUR_START_DATE, MT.END_DATE AS TOUR_END_DATE, 
             G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2, COUNT(R.SCORE) RES,
             DATE_FORMAT(G.START_DATE, '%Y-%m-%d') GAME_DAY
	     FROM manager_tours MT, seasons S, games G
                  LEFT JOIN results R ON R.GAME_ID = G.GAME_ID
	    WHERE MT.season_id=" . $_POST['season_id'] . "
	      and S.season_id in (" . $ulist . ") 
	      and G.season_id=S.SEASON_ID
		AND G.PUBLISH='Y'
              and MT.NUMBER= " . $_POST['tour_id'] . "
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            GROUP BY G.GAME_ID
            ORDER BY MT.NUMBER, G.START_DATE";
	} else {
	  $sql = "SELECT G.GAME_ID, G.START_DATE, MT.NUMBER, 
             MT.START_DATE AS TOUR_START_DATE, MT.END_DATE AS TOUR_END_DATE, 
	     0 as SCORE1, 0 as SCORE2, COUNT(R.SCORE) RES,
             DATE_FORMAT(G.START_DATE, '%Y-%m-%d') GAME_DAY
	     FROM manager_tours MT, seasons S, games_races G
                  LEFT JOIN results_races R ON R.GAME_ID = G.GAME_ID
	    WHERE MT.season_id=" . $_POST['season_id'] . "
	      and S.season_id in (" . $ulist . ") 
	      and G.season_id=S.SEASON_ID
		AND G.PUBLISH='Y'
              and MT.NUMBER= " . $_POST['tour_id'] . "
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            GROUP BY G.GAME_ID
            ORDER BY MT.NUMBER, G.START_DATE";
        }
	$db->query ( $sql );
	$c = 0;
        $total_games = 0;
	while ( $row = $db->nextRow () )
	{
		//echo $row['TEAM_ID1']." - ".$row['TEAM_ID2']."<br>";
		if ($row ['RES'] > 0)
		{
			$glist [$c] ['GAME_ID'] = $row ['GAME_ID'];
			$glist [$c] ['SCORE1'] = $row ['SCORE1'];
			$glist [$c] ['SCORE2'] = $row ['SCORE2'];
			$glist [$c] ['TEAM_ID1'] = $row ['TEAM_ID1'];
			$glist [$c] ['TEAM_ID2'] = $row ['TEAM_ID2'];
			$glist [$c] ['GAME_DAY'] = $row ['GAME_DAY'];
			$c ++;
		}
		$total_games++;
	}
	$db->free ();

        unset($sdata);
        $sdata['TOTAL_GAMES'] = $total_games;
        $sdata['COUNTED_GAMES'] = $c;
        $db->update('manager_tours', $sdata, "season_id=" . $_POST['season_id'] . " AND NUMBER= " . $_POST['tour_id']);
        unset($sdata);
	
	foreach($players as &$player) {
  		$player['SCORE'] = 0;
		$player['ASSISTS'] = 0;
		$player['REBOUNDS'] = 0;
		$player['BLOCKS'] = 0;
		$player['STEALS'] = 0;
		$player['ACCURACY'] = 0;
		$player['2ACCURACY'] = 0;
		$player['UNFAULS'] = 0;
		$player['PT_SCORED'] = 0;
		$player['PT_THROWN'] = 0;
		$player['PT1_SCORED'] = 0;
		$player['PT1_THROWN'] = 0;
		$player['PT2_SCORED'] = 0;
		$player['PT2_THROWN'] = 0;
		$player['PT3_SCORED'] = 0;
		$player['PT3_THROWN'] = 0;
        }
        unset($player);

	for($i = 0; $i < $c; $i ++)
	{
             if ($manager->sport_id != 3) {
	       $sql = "SELECT MP.PLAYER_ID, MP.START_VALUE, G.GAME_ID, 
                 R.*, MM.POSITION_ID1
         	 FROM games G, manager_market MM, members M, manager_players MP
               		LEFT JOIN results R ON R.GAME_ID = " . $glist [$i] ['GAME_ID'] . " 
                                  AND R.USER_ID = MP.PLAYER_ID
	         WHERE MP.SEASON_ID=" . $_POST['season_id'] . "
        	   AND MM.SEASON_ID=" . $_POST['season_id'] . "
	           AND MM.USER_ID=MP.PLAYER_ID
        	   AND G.GAME_ID = " . $glist [$i] ['GAME_ID'] . "
		   AND M.USER_ID = MP.PLAYER_ID
		   AND M.TEAM_ID = R.TEAM_ID
		   AND (M.DATE_STARTED < G.START_DATE AND (M.DATE_EXPIRED > G.START_DATE OR M.DATE_EXPIRED IS NULL))
                 GROUP BY MP.PLAYER_ID
        	 ORDER BY G.START_DATE";
             } else {
   	         $sql = "SELECT MP.PLAYER_ID, MP.START_VALUE, G.GAME_ID, 
                 R.*, MM.POSITION_ID1
         	 FROM games_races G, manager_market MM, members M, manager_players MP
               		LEFT JOIN results_races R ON R.GAME_ID = " . $glist [$i] ['GAME_ID'] . " 
                                  AND R.USER_ID = MP.PLAYER_ID
	         WHERE MP.SEASON_ID=" . $_POST['season_id'] . "
        	   AND MM.SEASON_ID=" . $_POST['season_id'] . "
	           AND MM.USER_ID=MP.PLAYER_ID
        	   AND G.GAME_ID = " . $glist [$i] ['GAME_ID'] . "
		   AND M.USER_ID = MP.PLAYER_ID
		   AND M.TEAM_ID = R.TEAM_ID
		   AND (M.DATE_STARTED < G.START_DATE AND (M.DATE_EXPIRED > G.START_DATE OR M.DATE_EXPIRED IS NULL))
                 GROUP BY MP.PLAYER_ID
        	 ORDER BY G.START_DATE";
             }

		echo $glist [$i]['TEAM_ID1'] ." " . $glist [$i]['TEAM_ID2'] . "<br>";
		$db->query ( $sql );
		$x = 0;
		
		while ( $row = $db->nextRow () )
		{
			// stuff
			$players [$row ['PLAYER_ID']]['PLAYER_ID'] = $row ['PLAYER_ID'];                         
		        isset($players [$row ['PLAYER_ID']]['PLAYED']) ? $players [$row ['PLAYER_ID']]['PLAYED']++ : $players [$row ['PLAYER_ID']]['PLAYED'] = 0;
 			isset($players [$row ['PLAYER_ID']]['TOTAL_POINTS']) ? $players [$row ['PLAYER_ID']]['TOTAL_POINTS'] += $row ['KOEFF'] : $players [$row ['PLAYER_ID']]['TOTAL_POINTS'] = $row ['KOEFF'];		
	             	if ($manager->sport_id != 3) {
			  $players [$row ['PLAYER_ID']]['KOEFF'] = ($players [$row ['PLAYER_ID']]['TOTAL_POINTS_PREV'] + $players [$row ['PLAYER_ID']] ['TOTAL_POINTS'] + $row ['START_VALUE'] - 1) / ($players[$row['PLAYER_ID']]['PLAYED'] + $players[$row['PLAYER_ID']]['PLAYED_PREV'] + 1);
			  $players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'] = (($row['START_VALUE'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS_PREV'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS'] + 1) / ($players[$row['PLAYER_ID']]['PLAYED'] + $players[$row['PLAYER_ID']]['PLAYED_PREV'] + 1)) * 1000;
		          //$players [$row ['PLAYER_ID']]['TURNING_POINT'] = round(($players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY']*($players [$row ['PLAYER_ID']]['PLAYED']+2)- ($row['START_VALUE'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS']+1) * 1000)/1000, 2);
				  //       $player['TURNING_POINT'] = round(($row['CURRENT_VALUE_MONEY']*($row['PLAYED']+2)- ($row['START_VALUE'] + $row['TP']+1) * 1000)/1000, 2);
                        } else {
print_r($players [$row ['PLAYER_ID']]);
/*                          if ($players[$row['PLAYER_ID']]['PLAYED_PREV'] > 3) {
echo 1;
  			    $players [$row ['PLAYER_ID']]['KOEFF'] = ($players [$row ['PLAYER_ID']]['FLOATING_TOTAL_POINTS_PREV'] + $players [$row ['PLAYER_ID']] ['TOTAL_POINTS']) / ($players[$row['PLAYER_ID']]['FLOATING_PLAYED'] + 1);
    			    $players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'] = (($players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_PREV']*4 + $players[$row['PLAYER_ID']]['TOTAL_POINTS']) / ($players[$row['PLAYER_ID']]['FLOATING_PLAYED'] + 1)) * 1000;
			    $players [$row ['PLAYER_ID']]['TURNING_POINT'] = $players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_PREV']*4 + $players [$row ['PLAYER_ID']] ['TOTAL_POINTS'] - $players[$row['PLAYER_ID']]['FLOATING_TOTAL_POINTS_NEXT'];
                          }
  			  else {
echo 2;*/

			    $players [$row ['PLAYER_ID']]['KOEFF'] = ($players [$row ['PLAYER_ID']]['TOTAL_POINTS_PREV'] + $players [$row ['PLAYER_ID']] ['TOTAL_POINTS'] + $row ['START_VALUE'] - 1) / ($players[$row['PLAYER_ID']]['PLAYED'] + $players[$row['PLAYER_ID']]['PLAYED_PREV'] + 1);
			    $players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'] = (($row['START_VALUE'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS_PREV'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS'] + 1) / ($players[$row['PLAYER_ID']]['PLAYED'] + $players[$row['PLAYER_ID']]['PLAYED_PREV'] + 1)) * 1000;
//			    $players [$row ['PLAYER_ID']]['TURNING_POINT'] = round(($players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY']*($players [$row ['PLAYER_ID']]['PLAYED_PREV']+$players [$row ['PLAYER_ID']]['PLAYED']+2)- ($row['START_VALUE'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS']+ $players[$row['PLAYER_ID']]['TOTAL_POINTS_PREV']+1) * 1000)/1000, 2);

//                          }
print_r($players [$row ['PLAYER_ID']]);
                        }
                        if ($manager->sport_id == 1) {
			  // 6    
  			  isset($players [$row ['PLAYER_ID']]['SCORE']) ? $players [$row ['PLAYER_ID']]['SCORE'] += $row['SCORE'] : $players [$row ['PLAYER_ID']]['SCORE'] = $row['SCORE'];		
			  isset($players [$row ['PLAYER_ID']]['ASSISTS']) ? $players [$row ['PLAYER_ID']]['ASSISTS'] += $row ['ASSISTS'] : $players [$row ['PLAYER_ID']]['ASSISTS'] = $row ['ASSISTS'];
			  isset($players [$row ['PLAYER_ID']]['REBOUNDS']) ? $players [$row ['PLAYER_ID']]['REBOUNDS'] += $row ['REBOUNDS'] : $players [$row ['PLAYER_ID']]['REBOUNDS'] = $row ['REBOUNDS'];
			  isset($players [$row ['PLAYER_ID']]['BLOCKS']) ? $players [$row ['PLAYER_ID']]['BLOCKS'] += $row ['BLOCKS'] : $players [$row ['PLAYER_ID']]['BLOCKS'] = $row ['BLOCKS'];
			  isset($players [$row ['PLAYER_ID']]['STEALS']) ? $players [$row ['PLAYER_ID']]['STEALS'] += $row ['STEALS'] : $players [$row ['PLAYER_ID']]['STEALS'] = $row ['STEALS'];
			  isset($players [$row ['PLAYER_ID']]['PT_SCORED']) ? $players [$row ['PLAYER_ID']]['PT_SCORED'] += $row ['PT3_SCORED'] + $row ['PT2_SCORED'] + $row ['PT1_SCORED'] : $players [$row ['PLAYER_ID']]['PT_SCORED'] = $row ['PT3_SCORED'] + $row ['PT2_SCORED'] + $row ['PT1_SCORED'];
			  isset($players [$row ['PLAYER_ID']]['PT_THROWN']) ? $players [$row ['PLAYER_ID']]['PT_THROWN'] += $row ['PT3_THROWN'] + $row ['PT2_THROWN'] + $row ['PT1_THROWN'] : $players [$row ['PLAYER_ID']]['PT_THROWN'] = $row ['PT3_THROWN'] + $row ['PT2_THROWN'] + $row ['PT1_THROWN'];

			  // +2
			  isset($players [$row ['PLAYER_ID']]['PT3_SCORED']) ? $players [$row ['PLAYER_ID']]['PT3_SCORED'] += $row ['PT3_SCORED'] : $players [$row ['PLAYER_ID']]['PT3_SCORED'] = $row ['PT3_SCORED'];
			  isset($players [$row ['PLAYER_ID']]['PT3_THROWN']) ? $players [$row ['PLAYER_ID']]['PT3_THROWN'] += $row ['PT3_THROWN'] : $players [$row ['PLAYER_ID']]['PT3_THROWN'] = $row ['PT3_THROWN'];
			  isset($players [$row ['PLAYER_ID']]['PT2_SCORED']) ? $players [$row ['PLAYER_ID']]['PT2_SCORED'] += $row ['PT2_SCORED'] : $players [$row ['PLAYER_ID']]['PT2_SCORED'] = $row ['PT2_SCORED'];
			  isset($players [$row ['PLAYER_ID']]['PT2_THROWN']) ? $players [$row ['PLAYER_ID']]['PT2_THROWN'] += $row ['PT2_THROWN'] : $players [$row ['PLAYER_ID']]['PT2_THROWN'] = $row ['PT2_THROWN'];
			  // +2
			  isset($players [$row ['PLAYER_ID']]['UNFAULS']) ? $players [$row ['PLAYER_ID']]['UNFAULS'] += $row ['UNFAULS'] : $players [$row ['PLAYER_ID']]['UNFAULS'] = $row ['UNFAULS'];
			  isset($players [$row ['PLAYER_ID']]['PT1_SCORED']) ? $players [$row ['PLAYER_ID']]['PT1_SCORED'] += $row ['PT1_SCORED'] : $players [$row ['PLAYER_ID']]['PT1_SCORED'] = $row ['PT1_SCORED'];
			  isset($players [$row ['PLAYER_ID']]['PT1_THROWN']) ? $players [$row ['PLAYER_ID']]['PT1_THROWN'] += $row ['PT1_THROWN'] : $players [$row ['PLAYER_ID']]['PT1_THROWN'] = $row ['PT1_THROWN'];
                        } else if ($manager->sport_id == 2) { 
			  // 6    
  			  isset($players [$row ['PLAYER_ID']]['SCORE']) ? $players [$row ['PLAYER_ID']]['SCORE'] += $row ['SCORE'] : $players [$row ['PLAYER_ID']]['SCORE'] = $row ['SCORE'];		
			  isset($players [$row ['PLAYER_ID']]['ASSISTS']) ? $players [$row ['PLAYER_ID']]['ASSISTS'] += $row ['ASSISTS'] : $players [$row ['PLAYER_ID']]['ASSISTS'] = $row ['ASSISTS'];
			  isset($players [$row ['PLAYER_ID']]['PT2_SCORED']) ? $players [$row ['PLAYER_ID']]['PT2_SCORED'] += $row ['PT2_SCORED'] : $players [$row ['PLAYER_ID']]['PT2_SCORED'] = $row ['PT2_SCORED'];
			  isset($players [$row ['PLAYER_ID']]['PT2_THROWN']) ? $players [$row ['PLAYER_ID']]['PT2_THROWN'] += $row ['PT2_THROWN'] : $players [$row ['PLAYER_ID']]['PT2_THROWN'] = $row ['PT2_THROWN'];
  			  isset($players [$row ['PLAYER_ID']]['BLOCKS']) ? $players [$row ['PLAYER_ID']]['BLOCKS'] += $row ['BLOCKS'] : $players [$row ['PLAYER_ID']]['BLOCKS'] = $row ['BLOCKS'];
  			  isset($players [$row ['PLAYER_ID']]['UNFAULS']) ? $players [$row ['PLAYER_ID']]['UNFAULS'] += $row ['UNFAULS'] : $players [$row ['PLAYER_ID']]['UNFAULS'] = $row ['UNFAULS'];	
                        } else if ($manager->sport_id == 3) { 
			  // 6    
  			  isset($players [$row ['PLAYER_ID']]['SCORE']) ? $players [$row ['PLAYER_ID']]['SCORE'] += $row ['SCORE'] : $players [$row ['PLAYER_ID']]['SCORE'] = $row ['SCORE'];		
                        }

			if (!isset($players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY']))
                          echo $row ['PLAYER_ID'];
			if (abs($players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'] - $players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY']) < $three_percent)
			  $players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY'] = $players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'];
                        else if ($players [$row ['PLAYER_ID']]['NEW_VALUE_MONEY'] > $players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY'])
			   $players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY'] += $three_percent;
                        else $players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY'] -= $three_percent;

			if ($players [$row ['PLAYER_ID']] ['CURRENT_VALUE_MONEY'] < 0)
				$players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY'] = 1;		
		        $players [$row ['PLAYER_ID']]['TURNING_POINT'] = round(($players [$row ['PLAYER_ID']]['CURRENT_VALUE_MONEY']*($players [$row ['PLAYER_ID']]['PLAYED_PREV']+$players [$row ['PLAYER_ID']]['PLAYED']+2)- ($row['START_VALUE'] + $players[$row['PLAYER_ID']]['TOTAL_POINTS']+ $players[$row['PLAYER_ID']]['TOTAL_POINTS_PREV']+1) * 1000)/1000, 2);
print_r($players [$row ['PLAYER_ID']]);
		}
	}

	foreach ( $players as $player )
	{
		$sql = "REPLACE INTO manager_player_stats
                          (PLAYER_ID, TOTAL_POINTS, KOEFF, CURRENT_VALUE_MONEY, START_VALUE_MONEY, PLAYED,
			   TOTAL_POINTS_PREV, FLOATING_TOTAL_POINTS_PREV, SEASON_ID, TOUR_ID, PLAYED_PREV,
		           SCORE, ASSISTS, REBOUNDS, BLOCKS, STEALS, UNFAULS,
			   PT_SCORED, PT_THROWN, PT3_SCORED, PT3_THROWN, PT2_SCORED, PT2_THROWN, PT1_SCORED, PT1_THROWN)
	           VALUES (" . $player ['PLAYER_ID'] . 
			"," . $player ['TOTAL_POINTS'] . 
			"," . $player ['KOEFF'] . 
			"," . $player ['CURRENT_VALUE_MONEY'] . 
			"," . $player ['START_VALUE_MONEY'] . 
			"," . $player ['PLAYED'] . 
			"," . $player ['TOTAL_POINTS_PREV'] . 
			"," . $player ['FLOATING_TOTAL_POINTS_PREV'] . 
			"," . $_POST['season_id'] . 
			"," . $_POST['tour_id'] . 
			"," . $player ['PLAYED_PREV'] . 
			"," . $player ['SCORE'] . 
			"," . $player ['ASSISTS'] . 
			"," . $player ['REBOUNDS'] . 
			"," . $player ['BLOCKS'] . 
			"," . $player ['STEALS'] . 
			"," . $player ['UNFAULS'] . 
			"," . $player ['PT_SCORED'] . 
			"," . $player ['PT_THROWN'] . 
			"," . $player ['PT3_SCORED'] . 
			"," . $player ['PT3_THROWN'] . 
			"," . $player ['PT2_SCORED'] . 
			"," . $player ['PT2_THROWN'] . 
			"," . $player ['PT1_SCORED'] . 
			"," . $player ['PT1_THROWN'] . ")";
		//if ($player ['PLAYER_ID'] == 72091)
			//echo $sql;
			$db->query ( $sql );
	}

	// get who did not play and assign potential substitute

 	$sql = "SELECT MT.SEASON_ID, MU.USER_ID, ".$_POST['tour_id']." as TOUR_ID, MT.PLAYER_ID, MTS.PLAYER_ID as SUBST_PLAYER_ID, MPS3.TOTAL_POINTS
	        FROM manager_teams MT, manager_player_stats MPS, manager_player_stats MPS2, manager_player_stats MPS3, manager_users MU,
			 manager_teams_substitutes MTS, manager_market MM, manager_market MM2			
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS2.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS3.SEASON_ID=" . $_POST['season_id'] . "
  		      AND MPS.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
  		      AND MPS2.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
  		      AND MPS3.TOUR_ID=" . ($_POST['tour_id']) . "
	  	      AND MU.SEASON_ID=" . $_POST['season_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND MTS.PLAYER_ID=MPS2.PLAYER_ID 
        	      AND MTS.PLAYER_ID=MPS3.PLAYER_ID 
        	      AND '".$tour_start_date."' > MT.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
		      AND '".$tour_start_date."' > MTS.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MTS.SELLING_DATE OR MTS.SELLING_DATE IS NULL)
		      AND MTS.SEASON_ID=" . $_POST['season_id'] . "
		      AND MTS.USER_ID=MU.USER_ID
		      AND MT.PLAYER_ID=MM.USER_ID
		      AND MM.SEASON_ID = ". $_POST['season_id'] . "
		      AND MM2.SEASON_ID = ". $_POST['season_id'] . "
		      AND MTS.PLAYER_ID=MM2.USER_ID
		      AND MM.POSITION_ID1 = MM2.POSITION_ID1
		      AND MPS.CURRENT_VALUE_MONEY > MPS2.CURRENT_VALUE_MONEY";
echo $sql;
	$db->query ( $sql );
	$c = 0;
        $substitutes = array();
	while ( $row = $db->nextRow () ) {
//print_r($players [$row ['PLAYER_ID']]);
//print_r($players [$row ['SUBST_PLAYER_ID']]);
           if ($players [$row ['PLAYER_ID']]['PLAYED'] == 0 &&
		$players [$row ['SUBST_PLAYER_ID']]['PLAYED'] > 0 &&
		!isset($players [$row ['SUBST_PLAYER_ID']][$row ['USER_ID']])) {
             // needs subst and has subst
if ($row ['USER_ID'] == 328)
  print_r($row);
             $substitutes[] = $row;   
	     $players [$row ['SUBST_PLAYER_ID']][$row ['USER_ID']] = $row ['USER_ID'];
           }
        }
	// print_r($players);

//print_r($substitutes);
	$db->delete("manager_player_substitute_stats", "SEASON_ID=".$_POST['season_id']." AND TOUR_ID=".$_POST['tour_id']);
	foreach ( $substitutes as $substitute )
	{
		$sql = "INSERT INTO manager_player_substitute_stats
	           VALUES (" . $_POST['season_id'] . 
			"," . $substitute ['USER_ID'] . 
			"," . $_POST['tour_id'] . 
			"," . $substitute ['PLAYER_ID'] . 
			"," . $substitute ['SUBST_PLAYER_ID'] .
			"," . $substitute ['TOTAL_POINTS'] . ")";
//			echo $sql;
			$db->query ( $sql );
	}

	echo "<br>";
	$sql = "SELECT SUM( MPS.TOTAL_POINTS ) as TOTAL_POINTS, MPS.PLAYER_ID, 
                  SUM(MPS.PLAYED) as PLAYED, MPS1.CURRENT_VALUE_MONEY, 
                  MPS1.TOTAL_POINTS_PREV, " . $_POST['season_id'] . " AS SEASON_ID, MPS1.KOEFF,
                  MPS2.CURRENT_VALUE_MONEY AS PREV_VALUE_MONEY
	     FROM manager_player_stats MPS
		LEFT JOIN manager_player_stats MPS1 ON 
				MPS1.PLAYER_ID=MPS.PLAYER_ID
				AND MPS1.SEASON_ID=MPS.SEASON_ID
				AND MPS1.TOUR_ID=" . $_POST['tour_id'] . "
		LEFT JOIN manager_player_stats MPS2 ON MPS2.PLAYER_ID = MPS.PLAYER_ID
				AND MPS2.SEASON_ID = MPS.SEASON_ID
				AND MPS2.TOUR_ID =" . ($_POST['tour_id'] - 1) . "
		WHERE MPS.SEASON_ID =" . $_POST['season_id'] . "
		GROUP BY MPS.PLAYER_ID";
	//echo $sql;
	$db->query ( $sql );
	$market_players='';
	while ( $row = $db->nextRow () )
	{
		$market_players [$row ['PLAYER_ID']] = $row;
	}
	$db->free ();
	foreach ( $market_players as $marketplayer )
	{
		//print_r($marketplayer);
		$sdata ['TOTAL_POINTS'] = $marketplayer ['TOTAL_POINTS'];
		$sdata ['USER_ID'] = $marketplayer ['PLAYER_ID'];
		$sdata ['PLAYED'] = $marketplayer ['PLAYED'];
		$sdata ['KOEFF'] = $marketplayer ['KOEFF'];
		$sdata ['TURNING_POINT'] = $players [$marketplayer['PLAYER_ID']]['TURNING_POINT'];
		$sdata ['CURRENT_VALUE_MONEY'] = $marketplayer ['CURRENT_VALUE_MONEY'];
		$sdata ['TOTAL_POINTS_PREV'] = $marketplayer ['TOTAL_POINTS_PREV'];
		$sdata ['PREV_VALUE_MONEY'] = $marketplayer ['PREV_VALUE_MONEY'];
		$sdata ['SEASON_ID'] = $_POST['season_id'];
		$db->update ( "manager_market", $sdata, "USER_ID=" . $marketplayer ['PLAYER_ID'] . " AND SEASON_ID=" . $_POST['season_id'] );
		echo ".";
	}
	$db->free ();
	unset ( $sdata );
	echo "<br>........<br>";
	ob_flush ();
	flush ();
        
	// update users
	if ($_POST['tour_id'] > 1)
	{
		$sql = "SELECT MU.USER_ID, ".$_POST['tour_id'].", SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS, 
			SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS_MAIN, 
			COUNT(MPS.TOTAL_POINTS) VALID_TEAM, MUT1.USED_CHANGES,
			sum(MPS.CURRENT_VALUE_MONEY)+MU.MONEY AS WEALTH
	        FROM manager_teams MT
			left join manager_captain MC on MT.ENTRY_ID=MC.ENTRY_ID
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL)
			, manager_player_stats MPS, manager_users MU
        	     LEFT JOIN manager_users_tours MUT ON MUT.USER_ID=MU.USER_ID 
                                               	AND MUT.SEASON_ID=" . $_POST['season_id'] . "
						AND MUT.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
        	     LEFT JOIN manager_users_tours MUT1 ON MUT1.USER_ID=MU.USER_ID 
                                               	AND MUT1.SEASON_ID=" . $_POST['season_id'] . "
						AND MUT1.TOUR_ID=" . ($_POST['tour_id']) . "
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
  		      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
	  	      AND MU.SEASON_ID=" . $_POST['season_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND '".$tour_start_date."' > MT.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
		GROUP BY MU.USER_ID";
	} else {
		$sql = "SELECT MU.USER_ID, ".$_POST['tour_id'].", SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS, 
			SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS_MAIN, 
			0 AS USED_CHANGES, sum(MPS.CURRENT_VALUE_MONEY)+MU.MONEY AS WEALTH
	        FROM manager_teams MT
			left join manager_captain MC on MT.ENTRY_ID=MC.ENTRY_ID
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL)
			, manager_player_stats MPS, manager_users MU        	     
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
  		      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
	  	      AND MU.SEASON_ID=" . $_POST['season_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND '".$tour_start_date."' > MT.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
	GROUP BY MU.USER_ID";
	}
//echo $sql;
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
        // include substs
        $sql = "SELECT MPSS.USER_ID, MPSS.TOUR_ID, SUM(IF(MPSS.TOTAL_POINTS IS NULL, 0, MPSS.TOTAL_POINTS)) as TOTAL_POINTS
	        FROM manager_player_substitute_stats MPSS
	        WHERE                                           	
		   MPSS.SEASON_ID=" . $_POST['season_id'] . "
		   AND MPSS.TOUR_ID=" . ($_POST['tour_id']) . "
		GROUP BY MPSS.USER_ID";
//echo $sql;
	$db->query ( $sql );
	while ( $row = $db->nextRow () )
	{
		$musers [$row['USER_ID']]['POINTS'] += $row['TOTAL_POINTS'];
		echo ".";
		ob_flush ();
		flush ();
	}

	//print_r($musers); exit;
	$db->free ();
	foreach($musers as $muser) {
		//      $sdata['POINTS'] = $musers[$i]['POINTS'];
		if (empty ( $muser ['USED_CHANGES'] ))
			$muser ['USED_CHANGES'] = 0;
		$sql = "REPLACE INTO manager_users_tours
                       (USER_ID, TOUR_ID, SEASON_ID, USED_CHANGES, PLACE, PLACE_TOUR, POINTS, POINTS_MAIN, MONEY, RATING)
	              VALUES (" . $muser ['USER_ID'] . 
				"," . $_POST['tour_id'] . 
				"," . $_POST['season_id'] . 
				"," . $muser ['USED_CHANGES'] . 
				",0,0," . $muser ['POINTS'] . 
				"," . $muser ['POINTS_MAIN'] . 
				",". $muser ['WEALTH'] .  ", 0)";
		$db->query ( $sql );
	}
	unset ( $sdata );
	$db->delete ( "manager_standings", "MSEASON_ID=" . $_POST['season_id'] );
	
	$sql = "REPLACE INTO manager_standings 
            SELECT MTR.USER_ID, SUM(MTR.POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, MTR2.MONEY, MTR1.MONEY AS WEALTH_PREV, 0
		FROM manager_users_tours MTR
			LEFT JOIN manager_users_tours MTR1
				ON MTR.SEASON_ID=MTR1.SEASON_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN manager_users_tours MTR2
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
	

	$sql = "SELECT USER_ID , POINTS, WEALTH
            FROM manager_standings         
           WHERE MSEASON_ID=" . $_POST['season_id'] . "
           ORDER BY POINTS DESC, WEALTH ASC";
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
	}
	$db->free ();

        unset($sdata);
        $sdata['MAX_POINTS'] = $people[0]['POINTS'];
        $max_points = $people[0]['POINTS'];
	$db->update ( 'manager_statistics', $sdata, "SEASON_ID=" . $_POST['season_id']);
        unset($sdata);
	
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE'] = $people [$i] ['PLACE'];
		$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		$sdata ['RATING'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$db->update ( 'manager_standings', $sdata, "MSEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
		unset ( $sdata );
	}
	$db->free ();

	$sql = "SELECT USER_ID, POINTS 
            FROM manager_users_tours         
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

        $max_points = $people[0]['POINTS'];
        unset($sdata);
	
        $manager_rating = new ManagerRating($_POST['season_id']);
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE_TOUR'] = $i+1;
		$sdata ['RATING'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		unset ( $sdata );

		$sdata ['RATING_POINTS'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$sdata ['SEASON_ID'] = $_POST['season_id'];
		$sdata ['USER_ID'] = $people [$i] ['USER_ID'];
		$sdata ['TOUR_ID'] = $_POST['tour_id'];
		$sdata ['SPORT_ID'] = $manager->sport_id;
		$sdata ['TOURNAMENT_ID'] = $manager_rating->tournament_id;				
		$sdata ['TOUR_END_DATE'] = "'".substr($tour_end_date, 0, 10)."'";				
		$db->replace ( 'manager_rating_points', $sdata);
		unset ( $sdata );
	}

    $manager_log = new ManagerLog();
    $manager_log->logEvent('', 3, $_POST['tour_id'], $_POST['season_id'], '', '');

    foreach ($clients as $key => $value) {
      unset ( $sdata );
      $db->delete ( "manager_standings_external", "MSEASON_ID=" . $_POST['season_id']. " AND SOURCE='".$key."'" );

      $sql = "REPLACE INTO manager_standings_external
            SELECT MS.USER_ID, MS.POINTS, 0, " . $_POST['season_id'] . ",
                0 AS PLACE_PREV, MS.WEALTH, MS.WEALTH_PREV, MS.RATING, '".$key."'
		FROM manager_standings MS, manager_users MU
                WHERE MS.MSEASON_ID=" . $_POST['season_id'] . "
                      and MU.SEASON_ID=" . $_POST['season_id'] . "
                      and MU.SOURCE='".$key."'
			AND MU.USER_ID=MS.USER_ID
		ORDER BY POINTS DESC";
	$db->query ( $sql );
	echo "update standings $key<br>";

	$sql = "SELECT USER_ID , POINTS, WEALTH
            FROM manager_standings_external
           WHERE MSEASON_ID=" . $_POST['season_id'] . "
                 and SOURCE='".$key."'		
           ORDER BY POINTS DESC, WEALTH ASC";
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
	   $people_external [$c] = $row;
  	   $people_external [$c] ['PLACE'] = $c + 1;
	   $c ++;
	}
	$db->free ();

	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE'] = $people_external [$i] ['PLACE'];
		//$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		$db->update ( 'manager_standings_external', $sdata, "MSEASON_ID=" . $_POST['season_id'] . " AND SOURCE='".$key."' AND USER_ID=" . $people_external [$i] ['USER_ID'] );
		unset ( $sdata );
	}
	$db->free ();

    }	

    echo "Updating ratings<br>";
    // update ratings  
    $rating = new ManagerRating($_POST['season_id']);
    $rating->updateSportRating();
    $rating->updateTournamentRating();
    $rating->updateTotalRating();


    echo "Updating Clans standings<br>";
    $sql = "SELECT CT.TEAM_ID, SUM(MUT.POINTS) as POINTS
		FROM clan_teams CT, clan_team_members CTM, manager_users_tours MUT 
			where CT.SEASON_ID=".$_POST['season_id']."
    	        	      AND '".$tour_start_date."' > CTM.DATE_JOINED 
		      	      AND ('".$tour_end_date."' < CTM.DATE_LEFT OR CTM.DATE_LEFT = '0000-00-00 00:00:00')
		      	      AND MUT.SEASON_ID=".$_POST['season_id']."
		      	      AND MUT.TOUR_ID=".$_POST['tour_id']."
		      	      AND MUT.USER_ID=CTM.USER_ID
				and CTM.TEAM_ID=CT.TEAM_ID
                 GROUP BY CT.TEAM_ID";

    $db->query ( $sql );
    $c = 0;
    while ( $row = $db->nextRow () )  {
	$mteams [$row['TEAM_ID']] = $row;
	$c ++;
	echo ".";
	ob_flush ();
	flush ();
    }
    $db->free ();
    foreach($mteams as $mteam) {
	$sql = "REPLACE INTO manager_clan_teams_tours
               (TEAM_ID, TOUR_ID, SEASON_ID, CHANGED, PLACE, PLACE_TOUR, POINTS, RATING)
	         VALUES (" . $mteam ['TEAM_ID'] . 
			"," . $_POST['tour_id'] . 
			"," . $_POST['season_id'] . 
			",0,0,0," . $mteam ['POINTS'] . ",0)";
	$db->query ( $sql );
    }


    unset ( $sdata );
    $db->delete ( "manager_clan_teams_standings", "MSEASON_ID=" . $_POST['season_id'] );
	
    $sql = "REPLACE INTO manager_clan_teams_standings 
            SELECT MTR.TEAM_ID, SUM(MTR.POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, 0
		FROM manager_clan_teams_tours MTR
			LEFT JOIN manager_clan_teams_tours MTR1
				ON MTR.SEASON_ID=MTR1.SEASON_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.TEAM_ID=MTR1.TEAM_ID
			LEFT JOIN manager_clan_teams_tours MTR2
				ON MTR.SEASON_ID=MTR2.SEASON_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.TEAM_ID=MTR2.TEAM_ID
                WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.TEAM_ID
		ORDER BY POINTS DESC";
    $db->query ( $sql );
    echo "update clan standings<br>";


    $sql = "SELECT TEAM_ID , POINTS
     	   FROM manager_clan_teams_standings          
	      WHERE MSEASON_ID=" . $_POST['season_id'] . "
	      ORDER BY POINTS DESC";
    $db->query ( $sql );
    $c = 0;
    while ( $row = $db->nextRow () ) {
	$clan_team [$c] = $row;
	$clan_team [$c] ['PLACE'] = $c + 1;
	$c++;
    }
    $db->free ();
    $max_points = $clan_team[0]['POINTS'];

    unset($sdata);
	
	//$db->showquery = true;
    for($i = 0; $i < $c; $i ++) {
	$sdata ['PLACE'] = $clan_team [$i] ['PLACE'];
	$db->update ( 'manager_clan_teams_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND TEAM_ID=" . $clan_team [$i] ['TEAM_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
	$sdata ['RATING'] = 100 * $clan_team [$i] ['POINTS'] / $max_points;
	$db->update ( 'manager_clan_teams_standings', $sdata, "MSEASON_ID=" . $_POST['season_id'] . " AND TEAM_ID=" . $clan_team [$i] ['TEAM_ID'] );
	unset ( $sdata );
    }
    $db->free ();

    $sql = "SELECT TEAM_ID, POINTS 
            FROM manager_clan_teams_tours         
           WHERE SEASON_ID=" . $_POST['season_id'] . "
		 AND TOUR_ID=" . $_POST['tour_id'] . "
           ORDER BY POINTS DESC";
//echo $sql;
    $db->query ( $sql );
    $c = 0;
    while ( $row = $db->nextRow () ) {
 	$clan_team [$c] = $row;
	$clan_team [$c] ['PLACE'] = $c + 1;
	$c ++;
    }
    $db->free ();
    $max_points = $clan_team [0]['POINTS'];

	//$db->showquery = true;
    for($i = 0; $i < $c; $i ++)	{
	$sdata ['PLACE_TOUR'] = $i+1;
	$sdata ['RATING'] = 100 * $clan_team [$i] ['POINTS'] / $max_points;
	$db->update ( 'manager_clan_teams_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND TEAM_ID=" . $clan_team [$i] ['TEAM_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
	unset ( $sdata );
    }

    echo "Updating Fantasy Leagues<br>";

    // get all leagues
    $sql = "SELECT LEAGUE_ID, RESERVE_SIZE, TEAM_SIZE, SEASON_ID FROM rvs_manager_leagues
		WHERE SEASON_ID=".$_POST['season_id']."
		 AND DRAFT_DATE IS NOT NULL
		 AND STATUS in (2,3)
		 AND START_TOUR <= ".$_POST['tour_id']."
		 AND END_TOUR >= ".$_POST['tour_id'];
echo $sql;
    $db->query($sql);
    $c = 0;
    $leagues = array();
    while ( $row = $db->nextRow () ) {
      $leagues[$c] = $row;
      $c++;
    }
//exit;
    foreach($leagues as $league) {
	$db->delete("rvs_manager_teams_tours", "LEAGUE_ID=".$league['LEAGUE_ID']." AND TOUR_ID=".$_POST['tour_id']);
	$sql = "INSERT INTO rvs_manager_teams_tours
		(USER_ID, PLAYER_ID, LEAGUE_ID, TOUR_ID)
		select d.user_id, d.player_id, ".$league['LEAGUE_ID'].", ".$_POST['tour_id']." from (
		select u.*, @rank:=CASE WHEN @class <> u.user_ID THEN 1 ELSE @rank+1 END AS rn
		, @class:=u.user_id 
		from
		(SELECT MU.USER_ID, MPS.PLAYER_ID, 1, MPS.TOTAL_POINTS
			FROM rvs_manager_teams MT, 
				manager_player_stats MPS, rvs_manager_leagues_members MU, (SELECT @class:=0) r2
			WHERE MT.USER_ID=MU.USER_ID
                              AND MU.LEAGUE_ID=".$league['LEAGUE_ID']."
			      AND MU.STATUS in (1,2)
       	       	       	      AND MT.LEAGUE_ID=".$league['LEAGUE_ID']."
			      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
  	  	  	      AND MPS.TOUR_ID=".$_POST['tour_id']."
                              AND MT.PLAYER_ID=MPS.PLAYER_ID 
                              AND '".$tour_start_date."' > MT.BUYING_DATE 
			      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
                 ORDER BY MU.USER_ID, MPS.TOTAL_POINTS DESC
	) u,  (SELECT @rank:=1) r1
	) d where rn <= ".($league['TEAM_SIZE']-$league['RESERVE_SIZE']);

echo $sql;
	$db->query ( $sql );
	$c = 0;
      }	

//    foreach($leagues as $league) {
	$sql = "SELECT ML.LEAGUE_ID, ML.LEAGUE_TYPE, MU.USER_ID, ".$_POST['tour_id'].", MUT1.USED_DISCARDS,
			MUT1.USED_FREE_TRANSFERS, 
			SUM(MPS.TOTAL_POINTS) AS POINTS, 
			SUM(MPS.SCORE) AS SCORE, 
			SUM(MPS.REBOUNDS) AS REBOUNDS, 
			SUM(MPS.ASSISTS) AS ASSISTS, 
			SUM(MPS.BLOCKS) AS BLOCKS, 
			SUM(MPS.STEALS) AS STEALS, 
			SUM(MPS.PT_SCORED)*100/SUM(MPS.PT_THROWN) AS ACCURACY, 
			SUM(MPS.PT3_SCORED)*100/SUM(MPS.PT3_THROWN) AS 3ACCURACY, 
			SUM(MPS.PT2_SCORED)*100/SUM(MPS.PT2_THROWN) AS 2ACCURACY, 
			SUM(MPS.PT1_SCORED)*100/SUM(MPS.PT1_THROWN) AS 1ACCURACY, 
			SUM(MPS.UNFAULS) AS UNFAULS
			FROM rvs_manager_leagues ML, rvs_manager_teams MT, rvs_manager_teams_tours RMTT,
				manager_player_stats MPS, rvs_manager_leagues_members MU
        	     		LEFT JOIN rvs_manager_users_tours MUT1 ON MUT1.USER_ID=MU.USER_ID 
                                               	AND MUT1.LEAGUE_ID=MU.LEAGUE_ID
						AND MUT1.TOUR_ID=" . ($_POST['tour_id']) . "
			WHERE MT.USER_ID=MU.USER_ID
                              AND ML.LEAGUE_ID=MU.LEAGUE_ID
			      AND MU.STATUS in (1,2)
			      AND ML.STATUS in (2,3) 
       	       	       	      AND ML.SEASON_ID=" . $_POST['season_id'] . "
       	       	       	      AND MT.LEAGUE_ID=ML.LEAGUE_ID
			      AND MPS.SEASON_ID=ML.SEASON_ID
  	  	  	      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
                              AND MT.PLAYER_ID=MPS.PLAYER_ID 
			      AND ML.START_TOUR <= ".$_POST['tour_id']."
		              AND ML.END_TOUR >= ".$_POST['tour_id']."
                              AND '".$tour_start_date."' > MT.BUYING_DATE 
			      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
			      AND RMTT.LEAGUE_Id=ML.LEAGUE_ID
  	  	  	      AND RMTT.TOUR_ID=" . $_POST['tour_id'] . "
			      AND RMTT.PLAYER_ID=MT.PLAYER_ID
			      AND RMTT.USER_ID=MT.USER_ID
			GROUP BY ML.LEAGUE_ID, MU.USER_ID";
//echo $sql;
  	echo "process users<br>";
	$db->query ( $sql );
	$c = 0;
        $leagues = array();
	while ( $row = $db->nextRow () )
	{
		$leagues[$row['LEAGUE_ID']."_".$row['USER_ID']] = $row;
		$c ++;
		echo ".";
	}
	ob_flush ();
	flush ();


	foreach($leagues as $league) {
		//      $sdata['POINTS'] = $musers[$i]['POINTS'];
		if (empty ( $league ['USED_DISCARDS'] ))
			$league ['USED_DISCARDS'] = 0;
		if (empty ( $league ['USED_FREE_TRANSFERS'] ))
			$league ['USED_FREE_TRANSFERS'] = 0;

		$sql = "REPLACE INTO rvs_manager_users_tours
                           (USER_ID, TOUR_ID, USED_DISCARDS, USED_FREE_TRANSFERS, PLACE, PLACE_TOUR, POINTS, LEAGUE_ID,
		            SCORE, REBOUNDS, ASSISTS, BLOCKS, STEALS, ACCURACY, 3ACCURACY, 2ACCURACY, 1ACCURACY, UNFAULS)
	              VALUES (" . $league ['USER_ID'] . 
				"," . $_POST['tour_id'] . 
				"," . $league ['USED_DISCARDS'] . 
				"," . $league ['USED_FREE_TRANSFERS'] . 
				",0,0," . $league ['POINTS'] . 
				",". $league ['LEAGUE_ID'] .  
				",". $league ['SCORE'] .  
				",". $league ['REBOUNDS'] .  
				",". $league ['ASSISTS'] .  
				",". $league ['BLOCKS'] .  
				",". $league ['STEALS'] .  
				",". ($league ['ACCURACY']=='' ? 0 :$league ['ACCURACY']) .  
				",". ($league ['3ACCURACY']=='' ? 0 :$league ['3ACCURACY']) .  
				",". ($league ['2ACCURACY']=='' ? 0 :$league ['2ACCURACY']) .  
				",". ($league ['1ACCURACY']=='' ? 0 :$league ['1ACCURACY']) .  
				",". $league ['UNFAULS'] . ")";
		$db->query ( $sql );
//echo $sql;
	}

	foreach($leagues as $league) {
          $league_points = array(); 
	  if ($league['LEAGUE_TYPE']  > 1) {
	    $sql = "SELECT RMUT.* FROM rvs_manager_users_tours RMUT, rvs_manager_leagues_members RMLM
			WHERE RMUT.LEAGUE_ID=".$league ['LEAGUE_ID']."
			      AND RMLM.LEAGUE_ID=RMUT.LEAGUE_ID
			      AND RMLM.USER_ID=RMUT.USER_ID
                              AND RMLM.STATUS IN (1,2)
				AND RMUT.TOUR_ID=".$_POST['tour_id'];
//echo $sql;
            $db->query ( $sql );
            $league_users = array();
   	    while ( $row = $db->nextRow () ) {
              $league_users[$row['USER_ID']] = $row; 
  	      $league_points[$row['USER_ID']]['USER_ID'] = $row['USER_ID'];
  	      $league_points[$row['USER_ID']]['TOUR_ID'] = $_POST['tour_id'];
  	      $league_points[$row['USER_ID']]['LEAGUE_ID'] = $league ['LEAGUE_ID'];
  	      $league_points[$row['USER_ID']]['PLACE'] = 0;
  	      $league_points[$row['USER_ID']]['PLACE_TOUR'] = 0;
            } 
//print_r($league_users);
            $arr = array();
            for ($i = 0; $i < $league['LEAGUE_TYPE']; $i++) {
              $arr[$i] = array();
              foreach($league_users as $luser) {
                $arr[$i][$luser['USER_ID']] = $luser[$fl_category[$manager->sport_id][$i]];
              }
            }
//print_r($arr);
            for ($i = 0; $i < $league['LEAGUE_TYPE']; $i++) {
              arsort($arr[$i]);
            }
//print_r($arr);

            for ($i = 0; $i < $league['LEAGUE_TYPE']; $i++) {
                $c = count($arr[$i]);
                $prev = $c;
                $prev_value = 0;
              	foreach(array_keys($arr[$i]) as $ar) {
                  //echo $ar.'<br />';
                  if ($arr[$i][$ar] == $prev_value)
  		     $league_points[$ar][$fl_category[$manager->sport_id][$i]] = $prev;
                  else {
  		     $league_points[$ar][$fl_category[$manager->sport_id][$i]] = $c;
                     $prev = $c;
                  }
                  $prev_value = $arr[$i][$ar];
                  $c--;
                }
            }
//print_r($league_points);
              $db->delete ( "rvs_manager_users_tours_categories", "LEAGUE_ID=" . $league ['LEAGUE_ID'] . " AND TOUR_ID=" .$_POST['tour_id']);
              foreach($league_points as $league_point) {
                $db->insert("rvs_manager_users_tours_categories", $league_point);
              }
            unset ($sdata);
            $sdata['TOUR_POINTS'] = "SCORE + REBOUNDS + ASSISTS + BLOCKS + STEALS + ACCURACY + 3ACCURACY + 2ACCURACY + 1ACCURACY + UNFAULS + EFF_POINTS";
            $db->update ( "rvs_manager_users_tours_categories", $sdata, "LEAGUE_ID=" . $league ['LEAGUE_ID'] . " AND TOUR_ID=" .$_POST['tour_id']);
          } else {
            $order_by  = "RMUT.POINTS";
            if ($league['LEAGUE_TYPE'] == 1)
              $order_by  = "RMUT.SCORE";
       
	    $sql = "SELECT RMUT.* FROM rvs_manager_users_tours RMUT, rvs_manager_leagues_members RMLM
			WHERE RMUT.LEAGUE_ID=".$league ['LEAGUE_ID']."
			      AND RMLM.LEAGUE_ID=RMUT.LEAGUE_ID
			      AND RMLM.USER_ID=RMUT.USER_ID
                              AND RMLM.STATUS IN (1,2)
				AND RMUT.TOUR_ID=".$_POST['tour_id']."
			ORDER BY ".$order_by." DESC";

            $db->query ( $sql );
            $people = array();          
            $c = 0;
   	    while ( $row = $db->nextRow () ) {
              $people[$c] = $row; 
  	      $c++;
            } 

            unset($sdata);
 	    for($i = 0; $i < $c; $i ++)	{
	      $sdata ['PLACE_TOUR'] = $i+1;
              $db->update("rvs_manager_users_tours", $sdata, "LEAGUE_ID=" . $league ['LEAGUE_ID'] . " AND USER_ID=".$people[$i]['USER_ID']." AND TOUR_ID=" .$_POST['tour_id']);
              unset($sdata);
            }
          }
        }

//	$db->delete ( "rvs_manager_standings", "MSEASON_ID=" . $_POST['season_id'] );

	$sql = "REPLACE INTO rvs_manager_standings 
                  (USER_ID, POINTS, PLACE, MSEASON_ID, PLACE_PREV, LEAGUE_ID, RATING)
            SELECT MTR.USER_ID, SUM(MTR.POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, MTR.LEAGUE_ID, 0
		FROM rvs_manager_leagues ML, rvs_manager_users_tours MTR
			LEFT JOIN rvs_manager_users_tours MTR1
				ON MTR.LEAGUE_ID=MTR1.LEAGUE_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN rvs_manager_users_tours MTR2
				ON MTR.LEAGUE_ID=MTR2.LEAGUE_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.USER_ID=MTR2.USER_ID
                WHERE MTR.LEAGUE_ID=ML.LEAGUE_ID
		      AND ML.SEASON_ID=" . $_POST['season_id'] . "
		      AND ML.STATUS in (2,3)
		      AND ML.LEAGUE_TYPE = 0
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.LEAGUE_ID, MTR.USER_ID
		ORDER BY POINTS DESC";
echo $sql;
	$db->query ( $sql );

	$sql = "REPLACE INTO rvs_manager_standings 
                  (USER_ID, POINTS, PLACE, MSEASON_ID, PLACE_PREV, LEAGUE_ID, RATING)
            SELECT MTR.USER_ID, SUM(MTR.TOUR_POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, MTR.LEAGUE_ID, 0
		FROM rvs_manager_leagues ML, rvs_manager_users_tours_categories MTR
			LEFT JOIN rvs_manager_users_tours_categories MTR1
				ON MTR.LEAGUE_ID=MTR1.LEAGUE_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN rvs_manager_users_tours_categories MTR2
				ON MTR.LEAGUE_ID=MTR2.LEAGUE_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.USER_ID=MTR2.USER_ID
                WHERE MTR.LEAGUE_ID=ML.LEAGUE_ID
		      AND ML.SEASON_ID=" . $_POST['season_id'] . "
		      AND ML.STATUS in (2,3)
		      AND ML.LEAGUE_TYPE > 1
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.LEAGUE_ID, MTR.USER_ID
		ORDER BY POINTS DESC";
echo $sql;
	$db->query ( $sql );

	$sql = "REPLACE INTO rvs_manager_standings 
                  (USER_ID, POINTS, PLACE, MSEASON_ID, PLACE_PREV, LEAGUE_ID, RATING)
            SELECT MTR.USER_ID, SUM(MTR.SCORE) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, MTR.LEAGUE_ID, 0
		FROM rvs_manager_leagues ML, rvs_manager_users_tours MTR
			LEFT JOIN rvs_manager_users_tours MTR1
				ON MTR.LEAGUE_ID=MTR1.LEAGUE_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN rvs_manager_users_tours MTR2
				ON MTR.LEAGUE_ID=MTR2.LEAGUE_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.USER_ID=MTR2.USER_ID
                WHERE MTR.LEAGUE_ID=ML.LEAGUE_ID
		      AND ML.SEASON_ID=" . $_POST['season_id'] . "
		      AND ML.STATUS in (2,3)
		      AND ML.LEAGUE_TYPE = 1 
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.LEAGUE_ID, MTR.USER_ID
		ORDER BY POINTS DESC";
echo $sql;
	$db->query ( $sql );

	echo "update standings<br>";

	$sql = "SELECT MS.LEAGUE_ID, MS.USER_ID, MS.POINTS, ML.LEAGUE_TYPE
	            FROM rvs_manager_standings MS, rvs_manager_leagues ML
        	WHERE ML.SEASON_ID=" . $_POST['season_id'] . "
			 AND ML.STATUS in (2,3)
			 AND MS.LEAGUE_ID=ML.LEAGUE_ID
	           ORDER BY LEAGUE_ID ASC, POINTS DESC";
	$db->query ( $sql );
	$c = 0;
        $p = 0;
	while ( $row = $db->nextRow () )
	{
                if ($c > 0 && $row['LEAGUE_ID'] != $people [$c-1]['LEAGUE_ID']) {
                  $p = 0;
                }
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $p + 1;
		$c++;
                $p++; 
	}
	$db->free ();
	
	$db->showquery = true;
        unset($sdata);
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE'] = $people [$i] ['PLACE'];
                if ($people [$i] ['LEAGUE_TYPE'] == 0 || $people [$i] ['LEAGUE_TYPE'] == 1)
  		  $db->update ( 'rvs_manager_users_tours', $sdata, "LEAGUE_ID=" . $people [$i]['LEAGUE_ID'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
  		else $db->update ( 'rvs_manager_users_tours_categories', $sdata, "LEAGUE_ID=" . $people [$i]['LEAGUE_ID'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		$db->update ( 'rvs_manager_standings', $sdata, "LEAGUE_ID=" . $people [$i]['LEAGUE_ID'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
		unset ( $sdata );
	}
	$db->free ();

    echo "Updating tournaments<br>";
    $db->showquery = true;
    $sql = "SELECT MT_ID, START_TOUR, END_TOUR FROM manager_tournament 
		where season_id=".$_POST['season_id']." AND STATUS=2
			AND START_TOUR <=".$_POST['tour_id'] ;
    $db->query ( $sql );
    $tournaments = array();
    while ($row = $db->nextRow ()) {
      $tournament = $row;
      $mt_id = $row['MT_ID'];
      $start_tour = $row['START_TOUR'];
      $end_tour = $row['END_TOUR'];
      $tournaments[] = $tournament;
    }

    $sql = "SELECT * FROM manager_tours MTT
	      WHERE MTT.NUMBER=" . $_POST['tour_id'];
    $db->query ( $sql );
    $row = $db->nextRow ();
    $tour_start_date = $row['START_DATE'];
    $tour_end_date = $row['END_DATE'];

    foreach ($tournaments as $tournament) {

	// update users
        $sql = "SELECT MU.USER_ID, MU.POINTS
	        FROM manager_tournament_users MTU
                       left join manager_users_tours MU on MU.SEASON_ID=".$_POST['season_id']."
							AND MU.TOUR_ID=".($_POST['tour_id'])."
							and MU.USER_ID=MTU.USER_ID
	        WHERE MTU.MT_ID=" . $tournament['MT_ID']."
			AND MTU.TOUR=".($_POST['tour_id'] - $tournament['START_TOUR'] + 1);

	echo $sql;
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$musers [$c] = $row;
		$c ++;
	}
	//print_r($musers); exit;

	for($i = 0; $i < $c; $i ++)
	{
		unset ( $sdata );		
		$sdata['SCORE'] = $musers[$i]['POINTS'];
                $db->update("manager_tournament_results", $sdata, "USER_ID= ".$musers[$i]['USER_ID']." AND mt_id=".$tournament['MT_ID']." AND tour=".($_POST['tour_id'] - $tournament['START_TOUR'] + 1)." AND round=1");
	}

      $manager_tournament_log = new ManagerTournamentLog();
      $manager_tournament_log->logEvent('', 3, ($_POST['tour_id'] - $tournament['START_TOUR'] + 1), 1, $tournament['MT_ID']);
      echo "Tournament ".$tournament['MT_ID']." updated<br>";

    }

    // Updating solo manager
    echo "Updating Solo manager<br>";

    if ($manager->allow_solo) {
      $days = array();
      $solo_manager_log = new SoloManagerLog();
      foreach($glist as $game) {
        $sql = "SELECT R.USER_ID, R.KOEFF FROM results R 
		    WHERE R.USER_ID > 0 AND R.GAME_ID=".$game['GAME_ID'];
        echo "Solo manager: updating game ". $game['GAME_ID'] ."<br>";
  	$db->query ( $sql );
        $players = array(); 
        while ( $row = $db->nextRow () ) {
	    $players[] = $row;
        } 
        foreach($players as $player) {
            unset($sdata);
            $sdata['KOEFF'] = $player['KOEFF'];    
            $db->update("solo_manager_players", $sdata, "PLAYER_ID=".$player['USER_ID']." AND GAME_DAY='".$game['GAME_DAY']."'");
            $days[$game['GAME_DAY']] = $game['GAME_DAY'];
        }         
      }

      foreach($days as $day) {
        echo $day;
        $solo_manager_log->logEvent('', 9, 0, $_POST['season_id'], '', '', $day);
      }
        // standings
	
      $sql = "REPLACE INTO solo_manager_standings 
            SELECT  " . $_POST['season_id'] . ", MTR.USER_ID, SUM(MTR.KOEFF) as POINTS, 0, 0
		FROM solo_manager_players MTR
                WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
		GROUP BY MTR.USER_ID
		ORDER BY POINTS DESC";
      $db->query ( $sql );
      echo "update standings<br>";
	//    $db->free();
	

      $sql = "SELECT USER_ID, POINTS
            FROM solo_manager_standings         
           WHERE SEASON_ID=" . $_POST['season_id'] . "
           ORDER BY POINTS DESC";
      $db->query ( $sql );
      $c = 0;
      while ( $row = $db->nextRow () ) {
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
      }
      $db->free ();

      unset ( $sdata );
      for($i = 0; $i < $c; $i ++) {
		$sdata ['PLACE'] = $people [$i] ['PLACE'];
		$db->update ( 'solo_manager_standings', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
		unset ( $sdata );
      }
      $db->free ();

      }

    echo "Done<br>";    
}

?>