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

$sql = "SELECT SEASON_ID FROM manager_tournament where mt_id=".$_POST['mt_id'];
$db->query ( $sql );
$row = $db->nextRow ();
$season_id = $row['SEASON_ID'];

// get seasons
$db->select ( 'manager_subseasons', 'SEASON_ID', 'MSEASON_ID=' . $season_id);
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

//==== DRAW!!!!!!!!!!!!!!!!!
$sql = "SELECT SEASON_ID FROM manager_tournament where mt_id=".$_POST['mt_id'];
$db->query ( $sql );
$row = $db->nextRow ();
$season_id = $row['SEASON_ID'];

if (isset ( $_POST['update'] ) && ! $ro) {
	$db->showquery = true;

        $sql = "SELECT * FROM manager_tournament_tours MTT
		WHERE MTT.MT_ID=" . $_POST['mt_id'] . "
		      AND MTT.NUMBER=" . $_POST['tour_id'] . "
		      AND MTT.ROUND=" . $_POST['round'];
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];

	$sql = "SELECT G.GAME_ID, G.START_DATE, MT.NUMBER, 
             MT.START_DATE AS TOUR_START_DATE, MT.END_DATE AS TOUR_END_DATE, 
             G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2, COUNT(R.SCORE) RES
	     FROM manager_tournament_tours MT, seasons S, games G
                  LEFT JOIN results R ON R.GAME_ID = G.GAME_ID
	    WHERE MT.mt_id=" . $_POST['mt_id'] . "
	      and S.season_id in (" . $ulist . ") 
	      and G.season_id=S.SEASON_ID
              and MT.NUMBER= " . $_POST['tour_id'] . "
              and MT.ROUND= " . $_POST['round'] . "
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
			$glist [$c] ['GAME_ID'] = $row ['GAME_ID'];
			$glist [$c] ['SCORE1'] = $row ['SCORE1'];
			$glist [$c] ['SCORE2'] = $row ['SCORE2'];
			$glist [$c] ['TEAM_ID1'] = $row ['TEAM_ID1'];
			$glist [$c] ['TEAM_ID2'] = $row ['TEAM_ID2'];
			$c ++;
		}
	}
	$db->free ();

	for($i = 0; $i < $c; $i ++)
	{
	 $sql = "SELECT MP.PLAYER_ID, MP.START_VALUE, G.GAME_ID, 
                 R.*, MM.POSITION_ID1 
         	 FROM games G, manager_market MM, members M, manager_players MP
               		LEFT JOIN results R ON R.GAME_ID = " . $glist [$i] ['GAME_ID'] . " 
                                  AND R.USER_ID = MP.PLAYER_ID
	         WHERE MP.SEASON_ID=" . $season_id . "
        	   AND MM.SEASON_ID=" . $season_id . "
	           AND MM.USER_ID=MP.PLAYER_ID
        	   AND G.GAME_ID = " . $glist [$i] ['GAME_ID'] . "
		   AND M.USER_ID = MP.PLAYER_ID
		   AND M.TEAM_ID = R.TEAM_ID
		   AND (M.DATE_STARTED < G.START_DATE AND (M.DATE_EXPIRED > G.START_DATE OR M.DATE_EXPIRED IS NULL))
                 GROUP BY MP.PLAYER_ID
        	 ORDER BY G.START_DATE";
		echo $sql . "<br>";
		$db->query ( $sql );
		$x = 0;
		
		while ( $row = $db->nextRow () )
		{
			// stuff
			$players [$row ['PLAYER_ID']]['PLAYER_ID'] = $row ['PLAYER_ID'];
                        if (!isset($players [$row ['PLAYER_ID']]['TOTAL_POINTS']))
			  $players [$row ['PLAYER_ID']]['TOTAL_POINTS'] = 0;
			$players [$row ['PLAYER_ID']]['TOTAL_POINTS'] += $row ['KOEFF'];				
		}
	}
	foreach ( $players as $player )
	{
		$sql = "REPLACE INTO manager_tournament_player_stats
	           VALUES (" . $player ['PLAYER_ID'] . 
			"," . $player ['TOTAL_POINTS'] . 
			"," . $_POST['mt_id'] . 
			"," . $_POST['tour_id'] . 
			"," . $_POST['round'] . ")";
		//echo $sql;
		$db->query ( $sql );
	}

	// update users
 	    $sql = "SELECT MU.USER_ID, SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS
	        FROM manager_teams MT
			left join manager_captain MC on MT.ENTRY_ID=MC.ENTRY_ID
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL),
	      manager_tournament_player_stats MPS, manager_tournament_tours MTR,
	      manager_tournament_users MU
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $season_id . "
	  	      AND MTR.MT_ID =" . $_POST['mt_id'] . "
		      AND MTR.NUMBER=" . $_POST['tour_id'] . "
		      AND MTR.ROUND=" . $_POST['round'] . "
	  	      AND MPS.MT_ID=" . $_POST['mt_id'] . "
  		      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
  		      AND MPS.ROUND=" . $_POST['round'] . "
	  	      AND MU.MT_ID=" . $_POST['mt_id'] . "
	  	      AND MU.TOUR=" . $_POST['tour_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND MTR.START_DATE > MT.BUYING_DATE 
	      	      AND (MTR.END_DATE < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
	       GROUP BY MU.USER_ID";

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
                $db->update("manager_tournament_results", $sdata, "USER_ID= ".$musers[$i]['USER_ID']." AND mt_id=".$_POST['mt_id']." AND tour=".$_POST['tour_id']." AND round=".$_POST['round']);
	}

    $manager_tournament_log = new ManagerTournamentLog();
    $manager_tournament_log->logEvent('', 3, $_POST['tour_id'], $_POST['round'], $_POST['mt_id']);
    echo "Results recalculated";

}

?>