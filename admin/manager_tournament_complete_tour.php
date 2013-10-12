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
include('../class/manager_tournament.inc.php');
include('../class/manager_tournament_log.inc.php');
include('../class/user.inc.php');
include('../class/notification.inc.php');
include('../class/box.inc.php');

include('../smarty/libs/Smarty.class.php');
 $smarty = new Smarty;
 //$smarty->debugging = true;
 $smarty->registerPlugin("function","translate", "get_translation");

include('../class/email.inc.php');


if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

$season_id = $_POST['season_id'];

echo $season_id;
//==== DRAW!!!!!!!!!!!!!!!!!
if (isset ( $_POST['complete'] ) && ! $ro) {
   $db->showquery = true;

   $sql="SELECT MT.MT_ID, ".$_POST['tour_id']."-MT.START_TOUR+1 as TOUR_ID, TOURNAMENT_TYPE, MTT.NUMBER, MT.DURATION
		FROM manager_tournament MT, manager_tournament_tours MTT
		WHERE MT.STATUS < 3
			AND MTT.MT_ID=MT.MT_ID
			AND MTT.COMPLETED = 0
			AND MTT.NUMBER = ".$_POST['tour_id']."-MT.START_TOUR+1
			AND MT.SEASON_ID = ".$_POST['season_id']."
			AND MT.START_TOUR <=".$_POST['tour_id']."
		        AND MT.END_TOUR >=".$_POST['tour_id']."
		ORDER BY MTT.NUMBER ASC";
   $db->query($sql);
   $tournaments = array();
   while ( $row = $db->nextRow () ) {
     $tournaments[] = $row;
   }

   foreach ($tournaments as $tournament) {
      $_POST['tour_id'] = $tournament['TOUR_ID'];
      $_POST['mt_id'] = $tournament['MT_ID'];
      echo "Updating tournament ".$tournament['MT_ID']." tour ".$tournament['TOUR_ID']."<br>";
     
      $manager_tournament = new ManagerTournament($tournament['MT_ID']);
      $sql = "SELECT MTR.PAIR, SUM(MTR.SCORE) as SCORE1, SUM(MTR2.SCORE) as SCORE2, MTU.POINTS as PREV_POINTS1, MTU2.POINTS as PREV_POINTS2, 
 		    U1.USER_ID as USER_ID1, U2.USER_ID as USER_ID2, MS1.WEALTH as WEALTH1, MS2.WEALTH as WEALTH2
			FROM manager_tournament_results MTR
                          left join users U1 on U1.user_id=MTR.USER_ID
  			  left join manager_standings MS1 on MTR.user_id=MS1.USER_ID AND MS1.MSEASON_ID=".$season_id."
		          LEFT JOIN manager_tournament_users MTU ON MTU.MT_ID=".$_POST['mt_id']."
								AND MTU.user_id=MS1.USER_ID 
	                                                  	AND MTU.TOUR=".($_POST['tour_id']-1).",
			     manager_tournament_results MTR2
                          left join users U2 on U2.user_id=MTR2.USER_ID
  			  left join manager_standings MS2 on MTR2.user_id=MS2.USER_ID AND MS2.MSEASON_ID=".$season_id."
		          LEFT JOIN manager_tournament_users MTU2 ON MTU2.MT_ID=".$_POST['mt_id']."
								AND MTU2.user_id=MS2.USER_ID 
	                                                  	AND MTU2.TOUR=".($_POST['tour_id']-1)."
			WHERE  MTR.MT_ID=".$_POST['mt_id']." 
			       AND MTR.TOUR=".$_POST['tour_id']."
			       AND MTR.HOME =0
			       AND MTR2.MT_ID=".$_POST['mt_id']." 
			       AND MTR2.TOUR=".$_POST['tour_id']."
			       AND MTR2.HOME =1
			       AND MTR.PAIR=MTR2.PAIR
			group by U1.USER_NAME, U2.USER_NAME";
	
	$db->query ( $sql );
	$c = 0;
	$users = '';
        $loosers = '';
	while ( $row = $db->nextRow () )
	{
		//echo $row['TEAM_ID1']." - ".$row['TEAM_ID2']."<br>";
            if ($row['SCORE1'] > $row['SCORE2']) {
		$users[$c]['USER_ID'] = $row ['USER_ID1'];
                $loosers[$c]['USER_ID'] = $row ['USER_ID2'];
		$users[$c]['PREV_POINTS'] = $row ['PREV_POINTS1'];
                $loosers[$c]['PREV_POINTS'] = $row ['PREV_POINTS2'];
		$users[$c]['POINTS'] = 1;
                $loosers[$c]['POINTS'] = 0;
            } else if ($row['SCORE1'] < $row['SCORE2']) {
		$users[$c]['USER_ID'] = $row ['USER_ID2'];
                $loosers[$c]['USER_ID'] = $row ['USER_ID1'];
		$users[$c]['PREV_POINTS'] = $row ['PREV_POINTS2'];
                $loosers[$c]['PREV_POINTS'] = $row ['PREV_POINTS1'];
  		$users[$c]['POINTS'] = 1;
                $loosers[$c]['POINTS'] = 0;
            }
            else if ($row['SCORE1'] == $row['SCORE2']) {
              if ($tournament['TOURNAMENT_TYPE'] == 0) {
                if ($row['WEALTH1'] < $row['WEALTH2']) {
    		  $users[$c]['USER_ID'] = $row ['USER_ID1'];
                  $loosers[$c]['USER_ID'] = $row ['USER_ID2'];
                }
   	        else if ($row['WEALTH1'] > $row['WEALTH2']) {
  		  $users[$c]['USER_ID'] = $row ['USER_ID2'];
                  $loosers[$c]['USER_ID'] = $row ['USER_ID1'];
                }   
                else {
                  $winner = rand(0, 1);
                  if ($winner == 0) {
  		    $users[$c]['USER_ID'] = $row ['USER_ID1'];
                    $loosers[$c]['USER_ID'] = $row ['USER_ID2'];
                  } else {
		    $users[$c]['USER_ID'] = $row ['USER_ID2'];
                    $loosers[$c]['USER_ID'] = $row ['USER_ID1'];
                  }
                }
              } else {
  		  $users[$c]['USER_ID'] = $row ['USER_ID1'];
  		  $users[$c]['POINTS'] = 0.5;
		  $users[$c]['PREV_POINTS'] = $row ['PREV_POINTS1'];
		  $c++;
  		  $users[$c]['USER_ID'] = $row ['USER_ID2'];
  		  $users[$c]['POINTS'] = 0.5;
                  $users[$c]['PREV_POINTS'] = $row ['PREV_POINTS2'];
              }
            }   
	    $c++;
	}

	if ($tournament['TOURNAMENT_TYPE'] == 0) {       
          $sql="SELECT U.USER_ID
			FROM manager_users MU, users U, countries C
				, manager_tournament_users MTU
			LEFT JOIN manager_tournament_results MTR ON MTR.MT_ID=MTU.MT_ID
							AND MTR.TOUR=".$_POST['tour_id']."
							AND ROUND=1
							AND MTU.USER_ID=MTR.USER_ID
		WHERE U.USER_ID=MTU.USER_ID 
			AND MTU.MT_ID=".$_POST['mt_id']."
			AND MTU.TOUR=".$_POST['tour_id']."
		        AND U.COUNTRY = C.ID
			AND MU.USER_ID=MTU.USER_ID 
			AND MU.SEASON_ID=".$_POST['season_id']."
		AND MTR.USER_ID IS NULL";
          $db->query ( $sql );
  	  while ( $row = $db->nextRow () ) {
             $users[$c]['USER_ID'] = $row ['USER_ID'];
             $c++;
	  }
        }

        if (count($users) > 1 && $tournament['TOURNAMENT_TYPE'] == 0) {
            for ($i = 0; $i < $c; $i++) { 
	       $sql = "INSERT INTO manager_tournament_users
	           VALUES (" . $_POST['mt_id'] . 
			"," . $users[$i]['USER_ID']. 
			"," . ($_POST['tour_id'] + 1) . ", NULL, 0)";
	       //echo $sql;
	       $db->query ( $sql );
	       $receiver = new User();
	       $receiver_id = $receiver->getUserIdFromId($users[$i]['USER_ID']);
	       if ($receiver_id > 0) {
  	         $notification = new Notification();
	         $notification->sendTournamentProgressEmail($receiver->user_name, $receiver->email, $manager_tournament->title, $_POST['mt_id'], $receiver->last_lang);
               }
   	    }
        } else if ($tournament['TOURNAMENT_TYPE'] == 1) {
            for ($i = 0; $i < $c; $i++) { 
               $db->update("manager_tournament_users", "POINTS=".($users[$i]['POINTS']+$users[$i]['PREV_POINTS']) , "USER_ID=".$users[$i]['USER_ID']." AND MT_ID=".$_POST['mt_id']." AND tour=".$_POST['tour_id']);
               if (isset($loosers[$i]))
                 $db->update("manager_tournament_users", "POINTS=".($loosers[$i]['POINTS']+$loosers[$i]['PREV_POINTS']) , "USER_ID=".$loosers[$i]['USER_ID']." AND MT_ID=".$_POST['mt_id']." AND tour=".$_POST['tour_id']);
	    }
	    if ($_POST['tour_id'] < $tournament['DURATION']) {
              $sql="INSERT INTO manager_tournament_users
 			SELECT MT_ID, USER_ID, ".($_POST['tour_id']+1).", NULL, POINTS FROM manager_tournament_users WHERE mt_id=".$_POST['mt_id']." AND tour=".($_POST['tour_id']);
              $db->query ( $sql );
            }
        }

        if ((count($users) == 1 && $tournament['TOURNAMENT_TYPE'] == 0) ||
	    ($tournament['TOURNAMENT_TYPE'] == 1 && $_POST['tour_id'] == $tournament['DURATION'])) {
           // distribute prize

           if ($manager_tournament->real_prizes == 'N') {
             $prize1 = $manager_tournament->prize_fund / 2;
             $prize2 = $manager_tournament->prize_fund * 0.3;
             $prize3 = $manager_tournament->prize_fund / 10;
                     
             $credits = new Credits();
             $credit_log = new CreditsLog();
             if ($tournament['TOURNAMENT_TYPE'] == 0) { 
               $credits->updateCredits($users[0]['USER_ID'], $prize1); 
               $credit_log->logEvent ($users[0]['USER_ID'], 24, $prize1);
               $credits->updateCredits($loosers[0]['USER_ID'], $prize2); 
               $credit_log->logEvent ($loosers[0]['USER_ID'], 24, $prize2);
             } else if ($tournament['TOURNAMENT_TYPE'] == 1) { 
	       $sql = "SELECT MU.USER_ID, IF(MTR.USER_ID IS NULL, 1, 0) AS SEED, MTU.TOUR, MTU.POINTS, MS.WEALTH
			FROM manager_users MU, manager_tournament_users MTU
			LEFT JOIN manager_tournament_results MTR ON MTR.MT_ID=MTU.MT_ID
							AND MTR.TOUR=".$_POST['tour_id']."
							AND MTU.USER_ID=MTR.USER_ID
			LEFT JOIN manager_standings MS ON MS.USER_ID=MTU.USER_ID and MS.MSEASON_ID=".$_POST['season_id']."
		WHERE  MTU.MT_ID=".$_POST['mt_id']." AND MTU.TOUR=".$_POST['tour_id']."
			AND MU.USER_ID=MTU.USER_ID 
			AND MU.SEASON_ID=".$_POST['season_id']."
		ORDER BY MTU.POINTS DESC, MS.WEALTH ASC, SEED DESC
		LIMIT 2";
                echo $sql;
		$users = array();
                $c = 0;
  	        $db->query ( $sql );
	        while ($row = $db->nextRow()) {
		  $users[$c] = $row['USER_ID'];
                  $c++;
	        }
               $credits->updateCredits($users[0], $prize1); 
               $credit_log->logEvent ($users[0], 24, $prize1);
               $credits->updateCredits($users[1], $prize2); 
               $credit_log->logEvent ($users[1], 24, $prize2);
             }
             $credits->updateCredits($manager_tournament->user_id, $prize3); 
             $credit_log->logEvent ($manager_tournament->user_id, 24, $prize3);   
           } else {
             $credits = new Credits();
             $credit_log = new CreditsLog();
             $prize3 = $manager_tournament->prize_fund * 0.9;
             $credits = new Credits();
             $credit_log = new CreditsLog();
             $credits->updateCredits($manager_tournament->user_id, $prize3); 
             $credit_log->logEvent ($manager_tournament->user_id, 24, $prize3);   
           }

           unset($sdata);           
           $sdata['WINNER'] = 1;
	   $db->update("manager_tournament_users", $sdata, "MT_ID=".$_POST['mt_id']." AND USER_ID=".$users[0]." AND TOUR=".$_POST['tour_id']);
	   $manager_tournament_log = new ManagerTournamentLog();
	   $manager_tournament_log->logEvent('', 5, '', '', $_POST['mt_id']);
	   $receiver = new User();
	   $receiver_id = $receiver->getUserIdFromId($users[0]);
	   if ($receiver_id > 0) {
             $user = $receiver->getUserData();
  	     $notification = new Notification();
	     $notification->sendTournamentWinnerEmail($user['USER_NAME'], $receiver->email, $manager_tournament->title, $_POST['mt_id'], $receiver->last_lang);
           }

           unset($sdata);
      	   $sdata['STATUS'] = 3;
  	   $db->update('manager_tournament', $sdata, "MT_ID=".$_POST['mt_id']);
        } 
        unset($sdata);
   	$sdata['COMPLETED'] = 1;
	$db->update('manager_tournament_tours', $sdata, "MT_ID=".$_POST['mt_id']." AND NUMBER=".$_POST['tour_id']);

        $manager_tournament_log = new ManagerTournamentLog();
        $manager_tournament_log->logEvent('', 3, $_POST['tour_id'], '', $_POST['mt_id']);
        if ($tournament['TOURNAMENT_TYPE'] == 0) {
          echo "Winners progressed to next tour";
        } else if ($tournament['TOURNAMENT_TYPE'] == 1) {
          echo "Points distributed";
        }
    }	
}

?>