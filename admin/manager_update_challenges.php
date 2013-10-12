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
include('../class/manager_log.inc.php');
include('../class/manager.inc.php');
include('../class/manager_users_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

$db->showquery=true;
if (isset ( $_POST['update'] ) && ! $ro) {
  if (isset($_POST['season_id']) && isset($_POST['tour_id'])) {

     unset($sdata);
     $sdata['CHALLENGES_WON'] = "0";
     $sdata['BATTLES_WON'] = "0";
     $db->update('manager_users_tours', $sdata, "TOUR_ID=".$_POST['tour_id']." AND SEASON_ID=".$_POST['season_id']);

//  select current challenges
     $manager = new Manager($_POST['season_id']);
     $sql="SELECT MC.*, MUT1.points SCORE1, MUT2.points SCORE2 
		from manager_challenges MC
		left join manager_users_tours MUT1 on MUT1.season_id=".$_POST['season_id']."
							and MUT1.tour_id=".$_POST['tour_id']."
							and MUT1.user_id=MC.user_id
		left join manager_users_tours MUT2 on MUT2.season_id=".$_POST['season_id']."
							and MUT2.tour_id=".$_POST['tour_id']."
							and MUT2.user_id=MC.user2_id 
	  WHERE MC.STATUS=2 
		and MC.tour_id=".$_POST['tour_id']."
		and MC.season_id=".$_POST['season_id'];

     $db->query($sql);
     $challenges=array();
     while ( $row = $db->nextRow () ) {
	$challenges[$row['CHALLENGE_ID']] = $row;
     }

     // print_r($players);
     foreach ( $challenges as $challenge ) {
	unset($sdata);
        $sdata['SCORE1'] = !empty($challenge['SCORE1']) ? $challenge['SCORE1'] : '0';   
        $sdata['SCORE2'] = !empty($challenge['SCORE2']) ? $challenge['SCORE2'] : '0'; 
        $sdata['STATUS'] = 4; 
        $db->update('manager_challenges', $sdata, "CHALLENGE_ID=".$challenge['CHALLENGE_ID']);
	if ($challenge['SCORE1'] > $challenge['SCORE2']) {
          // transfer 100 from to  
          unset($sdata);
	  $sdata['CHALLENGES_WON'] = "CHALLENGES_WON+1";
          $db->update('manager_users', $sdata, "USER_ID=".$challenge['USER_ID']." AND SEASON_ID=".$challenge['SEASON_ID']);
          $db->update('manager_users_tours', $sdata, "USER_ID=".$challenge['USER_ID']." AND TOUR_ID=".$_POST['tour_id']." AND SEASON_ID=".$challenge['SEASON_ID']);
          unset($sdata);
	  $sdata['BONUS'] = "BONUS+1";
          $db->update('manager_rating_points', $sdata, "USER_ID=".$challenge['USER_ID']." AND TOUR_ID=".$_POST['tour_id']." AND SEASON_ID=".$challenge['SEASON_ID']);
          unset($sdata);
	  $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST+1";
          $db->update('manager_users', $sdata, "USER_ID=".$challenge['USER2_ID']." AND SEASON_ID=".$challenge['SEASON_ID']);
          if ($challenge['TYPE'] == 1)
	    $manager->transferMoney($challenge['USER_ID'], $challenge['USER2_ID'], $_POST['season_id'], $challenge['STAKE']);
          else if ($challenge['TYPE'] == 2) {
            $credits = new Credits();
  	    $credits->unfreezeCredits($challenge['USER2_ID'], $challenge['STAKE']);
  	    $credits->unfreezeCredits($challenge['USER_ID'], $challenge['STAKE']);
	    $credits->transferCredit($challenge['USER2_ID'], $challenge['USER_ID'], $challenge['STAKE'], 1, 14);
          }
        } else if ($challenge['SCORE1'] < $challenge['SCORE2']) {
          // transfer 100 from to  
          unset($sdata);
	  $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST+1";
          $db->update('manager_users', $sdata, "USER_ID=".$challenge['USER_ID']." AND SEASON_ID=".$challenge['SEASON_ID']);
          unset($sdata);
	  $sdata['CHALLENGES_WON'] = "CHALLENGES_WON+1";
          $db->update('manager_users', $sdata, "USER_ID=".$challenge['USER2_ID']." AND SEASON_ID=".$challenge['SEASON_ID']);
          $db->update('manager_users_tours', $sdata, "USER_ID=".$challenge['USER2_ID']." AND TOUR_ID=".$_POST['tour_id']." AND SEASON_ID=".$challenge['SEASON_ID']);
          unset($sdata);
	  $sdata['BONUS'] = "BONUS+1";
          $db->update('manager_rating_points', $sdata, "USER_ID=".$challenge['USER2_ID']." AND TOUR_ID=".$_POST['tour_id']." AND SEASON_ID=".$challenge['SEASON_ID']);
          unset($sdata);
          if ($challenge['TYPE'] == 1)
	    $manager->transferMoney($challenge['USER2_ID'], $challenge['USER_ID'], $_POST['season_id'], $challenge['STAKE']);
          else if ($challenge['TYPE'] == 2) {
            $credits = new Credits();
  	    $credits->unfreezeCredits($challenge['USER2_ID'], $challenge['STAKE']);
  	    $credits->unfreezeCredits($challenge['USER_ID'], $challenge['STAKE']);
	    $credits->transferCredit($challenge['USER_ID'], $challenge['USER2_ID'], $challenge['STAKE'], 1, 14);
          }
        }
	else if ($challenge['SCORE1'] == $challenge['SCORE2']) {
          if ($challenge['TYPE'] == 2) {
  	    $credits->unfreezeCredits($challenge['USER2_ID'], $challenge['STAKE']);
  	    $credits->unfreezeCredits($challenge['USER_ID'], $challenge['STAKE']);
          } 
        }
     }


//  select current battles
     $sql="SELECT MC.BATTLE_ID, MC.SEASON_ID, MC.STAKE, MC.PRIZE_FUND,
		  SUM( IFNULL( IF(MBM1.TEAM_ID=1, MUT1.POINTS, 0), 0)) as SCORE1, 
		  SUM( IFNULL( IF(MBM1.TEAM_ID=2, MUT1.POINTS, 0), 0)) as SCORE2
		from manager_battles MC
                     left join manager_battles_members MBM1 ON
                          MBM1.BATTLE_ID=MC.BATTLE_ID
                     left join manager_users_tours MUT1 on MUT1.season_id=MC.SEASON_ID
 							AND MUT1.TOUR_ID=MC.TOUR_ID
							AND MUT1.USER_ID=MBM1.USER_ID
	  WHERE MC.STATUS=2 
		and MC.tour_id=".$_POST['tour_id']."
		and MC.season_id=".$_POST['season_id']."
          GROUP BY MC.BATTLE_ID";

     $db->query($sql);
     $battles=array();
     while ( $row = $db->nextRow () ) {
	$battles[] = $row;
     }

     // print_r($players);
     foreach ( $battles as $battle ) {
	unset($sdata);
        $sdata['SCORE1'] = !empty($battle['SCORE1']) ? $battle['SCORE1'] : '0';   
        $sdata['SCORE2'] = !empty($battle['SCORE2']) ? $battle['SCORE2'] : '0'; 
        $sdata['STATUS'] = 4; 
        $prize_fund = $battle['PRIZE_FUND'] * 0.9;
        $prize = $battle['STAKE'] + ($battle['STAKE'] * 0.9);
        $db->update('manager_battles', $sdata, "BATTLE_ID=".$battle['BATTLE_ID']);
        // get battle teams

        $sql="SELECT MBM.USER_ID, MBM.TEAM_ID
		from manager_battles_members MBM
	  WHERE MBM.BATTLE_ID=".$battle['BATTLE_ID'];
        $db->query($sql);
        $team = array();
        $c = 0;
        while ( $row = $db->nextRow () ) {
  	  $team[$row['TEAM_ID']][] = $row;
	  $c++;
        }

        $credits = new Credits();
        $credit_log = new CreditsLog();
        $manager_user_log = new ManagerUserLog();

	if ($battle['SCORE1'] > $battle['SCORE2']) {
          // transfer 100 from to  
          unset($sdata);
	  $sdata['BATTLES_WON'] = "BATTLES_WON+1";
          $db->update('manager_users', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=1 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND SEASON_ID=".$battle['SEASON_ID']);
          $db->update('manager_users_tours', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=1 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND TOUR_ID=".$_POST['tour_id']." 
									AND SEASON_ID=".$battle['SEASON_ID']);

          unset($sdata);
	  $sdata['BONUS'] = "BONUS+1";
          $db->update('manager_rating_points', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=1 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND TOUR_ID=".$_POST['tour_id']." 
									AND SEASON_ID=".$battle['SEASON_ID']);

          unset($sdata);
	  $sdata['BATTLES_LOST'] = "BATTLES_LOST+1";
          $db->update('manager_users', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=2 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND SEASON_ID=".$battle['SEASON_ID']);

          if ($battle['STAKE'] > 0) {
            foreach($team[1] as $team_member) {
              $credits->updateCredits($team_member['USER_ID'], $prize); 
              $credit_log->logEvent ($team_member['USER_ID'], 20, $prize);
              $manager_user_log->logEvent ($team_member['USER_ID'], 14, $prize, $battle['SEASON_ID']);
            }  

            foreach($team[2] as $team_member) {
              $credit_log->logEvent ($team_member['USER_ID'], 21, $battle['STAKE']);
              $manager_user_log->logEvent ($team_member['USER_ID'], 15, $battle['STAKE'], $battle['SEASON_ID']);
    	    }
          }
        } else if ($battle['SCORE1'] < $battle['SCORE2']) {
          // transfer 100 from to  
	  unset($sdata);
	  $sdata['BATTLES_WON'] = "BATTLES_WON+1";
          $db->update('manager_users', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=2 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND SEASON_ID=".$battle['SEASON_ID']);
          $db->update('manager_users_tours', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=2 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND TOUR_ID=".$_POST['tour_id']." 
									AND SEASON_ID=".$battle['SEASON_ID']);
          unset($sdata);
	  $sdata['BONUS'] = "BONUS+1";
          $db->update('manager_rating_points', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=2 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND TOUR_ID=".$_POST['tour_id']." 
									AND SEASON_ID=".$battle['SEASON_ID']);

          unset($sdata);

	  $sdata['BATTLES_LOST'] = "BATTLES_LOST+1";
          $db->update('manager_users', $sdata, "USER_ID IN (SELECT MBM.USER_ID
							from manager_battles_members MBM
						  WHERE MBM.TEAM_ID=1 AND MBM.BATTLE_ID=".$battle['BATTLE_ID'].") AND SEASON_ID=".$battle['SEASON_ID']);

          if ($battle['STAKE'] > 0) {
            foreach($team[2] as $team_member) {
              $credits->updateCredits($team_member['USER_ID'], $prize); 
              $credit_log->logEvent ($team_member['USER_ID'], 20, $prize);
              $manager_user_log->logEvent ($team_member['USER_ID'], 14, $prize, $battle['SEASON_ID']);
            }  

            foreach($team[1] as $team_member) {
              $credit_log->logEvent ($team_member['USER_ID'], 21, $battle['STAKE']);
              $manager_user_log->logEvent ($team_member['USER_ID'], 15, $battle['STAKE'], $battle['SEASON_ID']);
    	    }
          }
        }
	else if ($battle['SCORE1'] == $battle['SCORE2']) {
          if ($battle['STAKE'] > 0) {
            foreach($team[1] as $team_member) {
              $credits->updateCredits($team_member['USER_ID'], $battle['STAKE']); 
            }  

            foreach($team[2] as $team_member) {
              $credits->updateCredits($team_member['USER_ID'], $battle['STAKE']); 
    	    }
          }
        }
     }
  }

  echo "Update completed!";
}

?>