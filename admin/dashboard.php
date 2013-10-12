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

if (empty($_SESSION["_admin"][MENU_FORUM]) || strcmp($_SESSION["_admin"][MENU_FORUM], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_FORUM], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN DELETE -----------------------------------------------------------
// --- END DELETE -------------------------------------------------------------

//$db->showquery=true;
// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

$db->query("SELECT NOW() CURT FROM users LIMIT 1");
$row = $db->nextRow();
$data['CURRENT_TIME'] = $row['CURT'];
$data['SERVER_TIME'] = date('Y-m-d h:i:s');
$firstTime=strtotime($data['CURRENT_TIME']);
$lastTime=strtotime($data['SERVER_TIME']);
$data['DIFFERENCE'] = floor(($firstTime-$lastTime)/60/60);


  $sql = "SELECT F.SEASON_ID, FD.SEASON_TITLE, F.PUBLISH, 
                SUBSTRING(F.START_DATE, 1, 10) START_DATE,
                SUBSTRING(F.END_DATE, 1, 10) END_DATE,
 	        GROUP_CONCAT(FD2.LANG_ID) as LANGUAGES
	  FROM manager_seasons F 
		left JOIN manager_seasons_details FD ON F.SEASON_ID = FD.SEASON_ID AND FD.LANG_ID=".$_SESSION['lang_id']."
		left join manager_seasons_details FD2 ON FD2.SEASON_ID=F.SEASON_ID 
        WHERE 1=1 
		AND F.START_DATE < NOW()
		AND F.END_DATE > NOW()
	GROUP BY F.SEASON_ID
        ORDER BY FD.SEASON_ID";
  $db->query($sql);

$c = 0;
while ($row = $db->nextRow()) {
  $data['MANAGER'][0]['ITEM'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['MANAGER'][0]['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['MANAGER'][0]['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }
    else {
      $data['MANAGER'][0]['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['MANAGER'][0]['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
    $data['MANAGER'][0]['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['SEASON_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['MANAGER'][0]['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['SEASON_ID']);
  else
    $data['MANAGER'][0]['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['SEASON_ID']);

  $data['MANAGER'][0]['ITEM'][$c]['EDIT_URL'] = 'manager_season_edit.php'; 
  $data['MANAGER'][0]['ITEM'][$c]['EDIT_PLAYERS_URL'] = 'manager_ppl.php'; 
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  $c++;
}
$db->free();


  // tours
   $sql = "SELECT MS.*, MT.*, MST.MARKET, MSD.SEASON_TITLE, MT.START_DATE < NOW() AS UPDATABLE, MT.END_DATE < NOW() AS CHALLENGE_UPDATABLE,
	  MT.END_DATE < NOW() AND MT.COUNTED_GAMES=MT.TOTAL_GAMES AND MT.TOTAL_GAMES > 0 AS OPENABLE
        FROM manager_seasons MS
      		left JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
	, manager_tours MT, manager_statistics MST
        WHERE MS.SEASON_ID=MT.SEASON_ID
	      AND MS.SEASON_ID=MST.SEASON_ID
              AND MT.START_DATE < NOW()
              AND DATE_ADD(MT.END_DATE, INTERVAL 7 DAY) > NOW()
        ORDER BY MT.START_DATE DESC";
   $db->query ( $sql );
   $t = 0;
   while ( $row = $db->nextRow () ) {
	$data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']] = $row;
	if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
  	  $data ['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'] [$row['NUMBER']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);
	if ($row['MARKET'] == "N" && $row['UPDATABLE'] == 1) {
 	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['SEASON_ID'] = $row['SEASON_ID'];
	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOTAL_GAMES'] = $row['TOTAL_GAMES'];
 	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['COUNTED_GAMES'] = $row['COUNTED_GAMES'];
	}
	if ($row['MARKET'] == "N" && $row['OPENABLE'] == 1) {
 	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['OPEN'][0]['X'] = 1;
 	}

	if ($t & 2 > 0)
	  $data['MANAGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['ODD'][0]['X'] = 1;
	$t++;
   }
   if ($t == 0) {
     $data['MANAGER'][0]['CURRENT_TOURS'][0]['TOUR_NORECORDS'][0]['X'] = 1;
   }


  // tours
   $sql = "SELECT MS.*, MT.*, MSD.TSEASON_TITLE, MT.START_DATE < NOW() AS UPDATABLE
        FROM bracket_seasons MS
      		left JOIN bracket_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
	, bracket_tours MT
        WHERE MS.SEASON_ID=MT.SEASON_ID
              AND MT.START_DATE < NOW()
              AND DATE_ADD(MT.END_DATE, INTERVAL 7 DAY) > NOW()
        ORDER BY MT.START_DATE DESC";
echo $sql;
   $db->query ( $sql );
   $t = 0;
   while ( $row = $db->nextRow () ) {
	$data['ARRANGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']] = $row;
	if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_ARRANGER], 'FA') == 0)
  	  $data ['ARRANGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'] [$row['NUMBER']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);
	if ($row['UPDATABLE'] == 1) {
 	  $data['ARRANGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
	  $data['ARRANGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['UPDATE'][0]['SEASON_ID'] = $row['SEASON_ID'];
	}

	if ($t & 2 > 0)
	  $data['ARRANGER'][0]['CURRENT_TOURS'][$row['SEASON_ID']]['TOURS'][$row['NUMBER']]['ODD'][0]['X'] = 1;
	$t++;
   }
   if ($t == 0) {
     $data['ARRANGER'][0]['CURRENT_TOURS'][0]['TOUR_NORECORDS'][0]['X'] = 1;
   }

   $sql = "SELECT MS.*, MT.*, MSD.SEASON_TITLE, MT.START_DATE < NOW() AS UPDATABLE, MT.END_DATE < NOW() AS CHALLENGE_UPDATABLE
        FROM manager_seasons MS
      		left JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		, manager_tours MT
        WHERE MS.SEASON_ID=MT.SEASON_ID
              AND MT.START_DATE > NOW()
              AND DATE_ADD(MT.END_DATE, INTERVAL -7 DAY) < NOW()
        ORDER BY MT.START_DATE DESC";

   $db->query ( $sql );
   $t = 0;
   while ( $row = $db->nextRow () ) {
	$data['MANAGER'][0]['UPCOMING_TOURS'][0]['TOURS'][$row['NUMBER']] = $row;
	if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
  	  $data ['MANAGER'][0]['UPCOMING_TOURS'][0]['TOURS'] [$row['NUMBER']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);
	if (isset($data['MARKET_OPEN'][0]['MARKET']) && $data['MARKET_OPEN'][0]['MARKET'] == "Y" && $row['UPDATABLE'] == 1) {
 	  $data['MANAGER'][0]['UPCOMING_TOURS'][0]['TOURS'][$row['NUMBER']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
	  $data['MANAGER'][0]['UPCOMING_TOURS'][0]['TOURS'][$row['NUMBER']]['UPDATE'][0]['SEASON_ID'] = $row['SEASON_ID'];
	}

	if ($t & 2 > 0)
	  $data['MANAGER'][0]['UPCOMING_TOURS'][0]['TOURS'][$row['NUMBER']]['ODD'][0]['X'] = 1;
	$t++;
   }
   if ($t == 0) {
     $data['MANAGER'][0]['UPCOMING_TOURS'][0]['TOUR_NORECORDS'][0]['X'] = 1;
   }


   $sql = "SELECT COUNT(NEQ.USER_ID) USERS, QUEUE_ID
		FROM newsletter_email_queue NEQ
              WHERE SENT=0
		GROUP BY QUEUE_ID";
   $db->query ( $sql );
   $c = 0;
   while ( $row = $db->nextRow () ) {
       $data['NEWSLETTER'][0]['ITEMS'][$c] = $row;
	$c++;
   }

   $sql = "SELECT COUNT(NEQ.EMAIL_ID) USERS
		FROM notification_email_queue NEQ
              WHERE SENT=0";
   $db->query ( $sql );
   $c = 0;
   if ( $row = $db->nextRow () ) {
       $data['NOTIFICATION'][0]['ITEMS'][0] = $row;
   }

   $sql = "SELECT COUNT(PM.PM_ID) USERS
		FROM pm_message PM
              WHERE opened=0 and sender_id=-1 
			AND DATE_ADD(SENT_DATE, INTERVAL 14 DAY) > NOW()";
   $db->query ( $sql );
   $c = 0;
   if ( $row = $db->nextRow () ) {
       $data['UNOPENED_PMS'][0]['ITEMS'][0] = $row;
   }


   // results 

   $sql = "SELECT G.GAME_ID, G.PUBLISH, G.SCORE1, G.SCORE2,
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2, 
          SD.SEASON_TITLE, GR.REPORTED_START_DATE, GR.LINK, GR.REPORT_ID, U.USER_NAME
        FROM manager_subseasons MS, manager_tours MT, seasons S
		LEFT JOIN seasons_details SD ON S.SEASON_ID=SD.SEASON_ID 
			AND SD.LANG_ID=".$_SESSION['lang_id']."
	     , games G
             LEFT JOIN teams T1 ON T1.TEAM_ID=G.TEAM_ID1 
             LEFT JOIN teams T2 ON T2.TEAM_ID=G.TEAM_ID2
             LEFT JOIN games_reports GR ON GR.GAME_ID=G.GAME_ID AND GR.FINISHED = 0
             LEFT JOIN users U ON U.USER_ID=GR.USER_ID 
        WHERE G.SEASON_ID=S.SEASON_ID
	      AND S.SEASON_ID=MS.SEASON_ID
              AND MS.MSEASON_ID=MT.SEASON_ID
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              AND ((G.SCORE1 = -1
                    AND G.SCORE2 = -1
                    AND TO_DAYS(NOW()) - TO_DAYS(G.START_DATE)<10
 		    AND G.START_DATE < NOW() 		    
                   )
                   OR  
		   (TO_DAYS(NOW()) = TO_DAYS(G.START_DATE))
		   OR GR.FINISHED = 0
                  )
              AND G.PUBLISH='Y'
        ORDER BY G.START_DATE";

	$db->query($sql);
	$rows = $db->rows();

	$c = 0;
	while ($row = $db->nextRow()) {
	  $data['RESULTS'][$c] = $row;
  
          if ($row['REPORTED_START_DATE'] != "") {
	    $data['RESULTS'][$c]['REPORT'][0] = $row;
          }
	  if ($c & 2 > 0)
	    $data['RESULTS'][$c]['ODD'][0]['X'] = 1;
	  else
	    $data['RESULTS'][$c]['EVEN'][0]['X'] = 1;
  
	  $c++;
	}

   $sql = "SELECT DISTINCT G.GAME_ID, G.PUBLISH, G.TITLE,
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          SD.SEASON_TITLE, R.GAME_ID as CHECKS
        FROM bracket_subseasons MS, bracket_tours MT, seasons S
		LEFT JOIN seasons_details SD ON S.SEASON_ID=SD.SEASON_ID 
			AND SD.LANG_ID=".$_SESSION['lang_id']."
	     , games_races G
		 left join results_races R on R.GAME_ID=G.GAME_ID
        WHERE G.SEASON_ID=S.SEASON_ID
	      AND S.SEASON_ID=MS.SEASON_ID
              AND MS.WSEASON_ID=MT.SEASON_ID
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              AND ((
                    R.GAME_ID is null
                    AND  TO_DAYS(NOW()) - TO_DAYS(G.START_DATE)<10
		    AND  TO_DAYS(NOW()) - TO_DAYS(G.START_DATE)>0
                   )
                   OR  
		   (TO_DAYS(NOW()) = TO_DAYS(G.START_DATE)
                   ))
              AND G.PUBLISH='Y'
        ORDER BY G.START_DATE";

	$db->query($sql);
	$rows = $db->rows();

	$c = 0;
	while ($row = $db->nextRow()) {
	  $data['RESULTS_RACES'][$c] = $row;
  
	  if ($c & 2 > 0)
	    $data['RESULTS_RACES'][$c]['ODD'][0]['X'] = 1;
	  else
	    $data['RESULTS_RACES'][$c]['EVEN'][0]['X'] = 1;
  
	  $c++;
	}
  // tours
   $sql = "SELECT MS.*, MT.*, MT.START_DATE < NOW() AS UPDATABLE,
           MT.DRAWN, MSD.SEASON_TITLE
        FROM manager_tournament MS, manager_tournament_tours MT,
		manager_tournament_details MSD
        WHERE MS.MT_ID=MT.MT_ID
	      AND MT.START_DATE < NOW()	
              AND TO_DAYS(NOW()) - TO_DAYS(MT.START_DATE)<10
	      AND MS.MT_ID=MSD.MT_ID
	      AND MSD.LANG_ID=".$_SESSION['lang_id']."
        ORDER BY MT.START_DATE ASC";

	$db->query ( $sql );
	$t = 0;
        $draw_allowed = false;
	while ( $row = $db->nextRow () )
	{
		$data['TOURNAMENT'][$row['MT_ID']] = $row;
		$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']] = $row;
		if (strcmp ($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0)
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']] ['DEL'] [0] ['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row ['TOUR_ID']);

		if ($row['COMPLETED'] == 0 && $row['UPDATABLE'] == 1 && $row['DRAWN'] == 1)
		{
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['TOUR_ID'] = $row['NUMBER'];
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['MT_ID'] = $row['MT_ID'];
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['UPDATE'][0]['ROUND'] = $row['ROUND'];
		        $data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['COMPLETE'][0] = $row;
		}

		if ($row['COMPLETED'] == 1)
                  $draw_allowed = true;

		if ($row['ROUND'] == 1) {
		  if ($row['DRAWN'] == 0 && $row['UPDATABLE'] == 1 && $draw_allowed)
  		  {
			$draw_allowed = false;
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW'][0]['TOUR_ID'] = $row['NUMBER'];
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW'][0]['MT_ID'] = $row['MT_ID'];
		  } 
		}
		if ($row['COMPLETED'] == 0 && $row['DRAWN'] == 1) {
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['DRAW_COMMENCED'][0]['X'] = 1;
                }

		if ($row['COMPLETED'] == 1) {
			$data['TOURNAMENT'][$row['MT_ID']]['CURRENT_TOURS'] [$row['NUMBER']]['ROUND'][$row['ROUND']]['TOUR_COMPLETED'][0]['X'] = 1;
                }
		$t++;
	}

   $sql = 'SELECT SUM(CREDIT) CREDITS, SUM(FROZEN_CREDITS) FROZEN_CREDIT
	        FROM users
	   WHERE USER_ID > 6';

   $db->query ( $sql );
   $row = $db->nextRow ();
   $data['CREDITS'] = $row['CREDITS'];
   $data['FROZEN_CREDITS'] = $row['FROZEN_CREDIT'];
   $data['TOTAL_CREDITS'] = $row['CREDITS'] + $row['FROZEN_CREDIT'];

   $sql = 'SELECT *
	        FROM users
	   WHERE credit < 0 or frozen_Credits<0';

   $db->query ( $sql );

   if (   $row = $db->nextRow ()) {
     $data['NEGATIVE_CREDITS'][0]['X'] = 1;
   }

   $sql = 'select sum(stakes) AS STAKES, subs.user_id, U.frozen_credits from
	(SELECT user_id, ifnull(sum(stake), 0) STAKES
	        FROM manager_challenges
	   WHERE status=1 or status=2
	group by user_id

	union all

	SELECT user2_id as user_id ,  ifnull(sum(stake), 0) STAKES
	        FROM manager_challenges
	   WHERE status=2
	group by user2_id

	union all

	SELECT user_id, ifnull(sum(stake), 0) STAKES
	        FROM wager_challenges
	   WHERE status=1 or status=2
	group by user_id

	union all

	SELECT user2_id as user_id ,  ifnull(sum(stake), 0) STAKES
	        FROM wager_challenges
	   WHERE status=2
	group by user2_id
	) subs, users U
	where subs.user_id=U.user_id
	group by U.user_id 
	having sum(stakes) <> U.frozen_credits';
   $db->query ( $sql );
   $c = 0;
   while ($row = $db->nextRow ()) {
       $data['WRONG_CREDITS'][$c++] = $row;
   }


   $sql = 'SELECT count(stake) CHALLENGES, ifnull(sum(stake), 0) STAKES
	        FROM manager_challenges
	   WHERE STATUS=5
           UNION
   	   SELECT count(stake) CHALLENGES, ifnull(sum(stake), 0) STAKES
	        FROM manager_battles
	   WHERE STATUS=5
           UNION
   	   SELECT count(stake) CHALLENGES, ifnull(sum(stake), 0) STAKES
	        FROM wager_challenges
	   WHERE STATUS=3';
   $db->query ( $sql );
   while ($row = $db->nextRow ()) {
     if ($row['STAKES'] > 0) {
       $data['UNFREEZE_CREDITS'][0]['STAKES'] += $row['STAKES'];
       $data['UNFREEZE_CREDITS'][0]['CHALLENGES'] += $row['CHALLENGES'];
     }
   }

   $sql = "SELECT round(SUM(CREDITS), 3) CREDITS, DAY FROM

		   (SELECT SUM(CREDITS) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (9,6)
			 and user_id <> 6
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
		   GROUP BY day

                   UNION

       
		   SELECT SUM(CREDITS*0.05) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (3)
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
			 and user_id2 <> 6
		   GROUP BY day
        
		   UNION

		   SELECT SUM(CREDITS*0.1) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (30)
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
			 and user_id2 <> 6
		   GROUP BY day
        
		   UNION

		   SELECT SUM(CREDITS/19) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (27)
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
		   GROUP BY day
        
		   UNION

		   SELECT SUM(CREDITS) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (22)
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
		   GROUP BY day
        
		   UNION
        
		   SELECT SUM(CREDITS*0.01) CREDITS, DATE_FORMAT(event_date, '%Y-%m-%d') as day
		        FROM credits
		   WHERE event_type in (14, 20)
                         and DATE_ADD(NOW(), INTERVAL -4 DAY) < EVENT_DATE
		   GROUP BY day) temp
            GROUP BY DAY
            ORDER BY DAY DESC

           ";

   $db->query ( $sql );
   $c = 0;
   while ($row = $db->nextRow ()) {
       $data['EARNED_CREDITS'][$c] = $row;
       $c++;
   }


   // wrong challenges
   $sql="SELECT MC.*, MUT1.POINTs as POINTS1, MUT2.POINTS as POINTS2, 
		U1.USER_NAME as USER_NAME1, U2.USER_NAME as USER_NAME2,
		MSD.SEASON_TITLE 
		FROM manager_seasons_details MSD, manager_challenges MC
		left join manager_users_tours MUT1 on MC.season_id=MUT1.season_id and MUT1.user_id=MC.user_id and MC.tour_id=MUT1.tour_id
		left join users U1 on MC.user_id=U1.user_id
		left join manager_users_tours MUT2 on MC.season_id=MUT2.season_id and MUT2.user_id=MC.user2_id and MC.tour_id=MUT2.tour_id
		left join users U2 on MC.user2_id=U2.user_id
	WHERE status=4
		and (MC.SCORE1 <> MUT1.POINTS or MC.SCORE2 <> MUT2.POINTS)
		and ((MC.SCORE1 > MC.score2 and MUT1.POINTS<=MUT2.POINTS) or
		     (MC.SCORE1 < MC.score2 and MUT1.POINTS>=MUT2.POINTS) or
		     (MC.SCORE1 = MC.score2 and MUT1.POINTS<>MUT2.POINTS))
		AND MSD.season_id=MC.season_id AND MSD.LANG_ID=".$_SESSION['lang_id'];
   $db->query ( $sql );     
   $c = 0;
   while ($row = $db->nextRow()) {
     $data['WRONG_CHALLENGES'][$c] = $row;
  
     if ($c & 2 > 0)
       $data['WRONG_CHALLENGES'][$c]['ODD'][0]['X'] = 1;
     else
       $data['WRONG_CHALLENGES'][$c]['EVEN'][0]['X'] = 1;
  
      $c++;
   }

// active submitted reports

   $sql="SELECT MPL.*, MSD.SEASON_TITLE, U.USER_NAME, B.FIRST_NAME, B.LAST_NAME, SUM(MM.PLAYER_STATE)  PLAYER_STATE 
              from manager_market MM , manager_player_reports MPL
		 left join users U on U.user_id = MPL.user_id
		 left join busers B on B.user_id = MPL.player_id
		 left join manager_seasons_details MSD on MSD.season_id = MPL.season_id and MSD.lang_id=".$_SESSION['lang_id']."
		where  report_state=0
			and B.user_id = MM.user_id
	group by U.USER_NAME, B.FIRST_NAME, B.LAST_NAME
	ORDER BY DATE_REPORTED ASC, VALID_TILL ASC";
   $db->query ( $sql );     
   $c = 0;
   while ($row = $db->nextRow()) {
     $data['REPORTS'][$c] = $row;
     $data['REPORTS'][$c]['STATE'] = $player_state[$row['STATUS']];
  
     if ($c & 2 > 0)
       $data['REPORTS'][$c]['ODD'][0]['X'] = 1;
     else
       $data['REPORTS'][$c]['EVEN'][0]['X'] = 1;
  
      $c++;
   }

// unfinished submitted reports
   $sql="SELECT MPL.*, MSD.SEASON_TITLE, U.USER_NAME, B.FIRST_NAME, B.LAST_NAME, SUM(MM.PLAYER_STATE)  PLAYER_STATE
		from manager_market MM ,  manager_player_reports MPL
		 left join users U on U.user_id = MPL.user_id
		 left join busers B on B.user_id = MPL.player_id
		 left join manager_seasons MS on MS.season_id = MPL.season_id 
		 left join manager_seasons_details MSD on MSD.season_id = MPL.season_id and MSD.lang_id=".$_SESSION['lang_id']."
		where (VALID_TILL < NOW() OR (MM.PLAYER_STATE =0 AND VALID_TILL > MS.END_DATE)) 
			and report_state=1
			and finished = 0
			and B.user_id = MM.user_id
		group by U.USER_NAME, B.FIRST_NAME, B.LAST_NAME
	ORDER BY VALID_TILL ASC";
   $db->query ( $sql );     
   $c = 0;
   while ($row = $db->nextRow()) {
     $data['UNFINISHED_REPORTS'][$c] = $row;
     $data['UNFINISHED_REPORTS'][$c]['STATE'] = $player_state[$row['STATUS']];
  
     if ($c & 2 > 0)
       $data['UNFINISHED_REPORTS'][$c]['ODD'][0]['X'] = 1;
     else
       $data['UNFINISHED_REPORTS'][$c]['EVEN'][0]['X'] = 1;
  
      $c++;
   }
 
  
   $sql= "SELECT count( PLAYER_ID ) , RML.LEAGUE_ID, RMT.USER_ID, RML.TEAM_SIZE
		FROM rvs_manager_teams RMT, rvs_manager_leagues RML
		WHERE RMT.SELLING_DATE IS NULL
		AND RML.LEAGUE_ID = RMT.LEAGUE_ID
		AND RML.DRAFT_DATE IS NOT NULL
		GROUP BY RMT.LEAGUE_ID, RMT.USER_ID
		HAVING count( PLAYER_ID ) < RML.TEAM_SIZE";
   $db->query ( $sql );     
   if ($row = $db->nextRow()) {
     $data['FIX_RVS_LEAGUES'][0]['X'] = 1;
   }

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/dashboard.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>