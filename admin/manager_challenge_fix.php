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
if (isset ( $_GET['challenge_id'] ) && ! $ro) {
//  select current challenges
   $sql="SELECT MC.*, MUT1.POINTs as POINTS1, MUT2.POINTS as POINTS2, 
		U1.USER_NAME as USER_NAME1, U2.USER_NAME as USER_NAME2,
		MSD.SEASON_TITLE 
		FROM manager_seasons_details MSD, manager_challenges MC
		left join manager_users_tours MUT1 on MC.season_id=MUT1.season_id and MUT1.user_id=MC.user_id and MC.tour_id=MUT1.tour_id
		left join users U1 on MC.user_id=U1.user_id
		left join manager_users_tours MUT2 on MC.season_id=MUT2.season_id and MUT2.user_id=MC.user2_id and MC.tour_id=MUT2.tour_id
		left join users U2 on MC.user_id=U2.user_id
	WHERE status=4
		and MC.challenge_id=".$_GET['challenge_id']."
		and (MC.SCORE1 <> MUT1.POINTS or MC.SCORE2 <> MUT2.POINTS)
		and ((MC.SCORE1 > MC.score2 and MUT1.POINTS<=MUT2.POINTS) or
		     (MC.SCORE1 < MC.score2 and MUT1.POINTS>=MUT2.POINTS) or
		     (MC.SCORE1 = MC.score2 and MUT1.POINTS<>MUT2.POINTS))
		AND MSD.season_id=MC.season_id AND MSD.LANG_ID=".$_SESSION['lang_id'];
   $db->query ( $sql );     

     $db->query($sql);
     if ( $row = $db->nextRow () ) {
        $manager = new Manager($row['SEASON_ID']);
        $sdata['SCORE1'] = $row['POINTS1'];   
        $sdata['SCORE2'] = $row['POINTS2']; 
        $sdata['STATUS'] = 4; 
        $db->update('manager_challenges', $sdata, "CHALLENGE_ID=".$row['CHALLENGE_ID']);

	if ($row['POINTS1'] > $row['POINTS2']) {
          // transfer 100 from to  
            unset($sdata);
            if ($row['SCORE1'] != $row['SCORE2'] )
  	      $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST-1";
	    $sdata['CHALLENGES_WON'] = "CHALLENGES_WON+1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER_ID']." AND SEASON_ID=".$row['SEASON_ID']);
            unset($sdata);
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $sdata['CHALLENGES_WON'] = "CHALLENGES_WON-1";
  	    $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST+1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER2_ID']." AND SEASON_ID=".$row['SEASON_ID']);

          if ($row['TYPE'] == 1) {
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $manager->transferMoney($row['USER2_ID'], $row['USER_ID'], $manager->mseason_id, $row['STAKE']*2);
	    else $manager->transferMoney($row['USER2_ID'], $row['USER_ID'], $manager->mseason_id, $row['STAKE']);
          } else if ($row['TYPE'] == 2) {
            $credits = new Credits();
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $credits->transferCredit($row['USER2_ID'], $row['USER_ID'], 2*$row['STAKE'], 0, 14);
	    else $credits->transferCredit($row['USER2_ID'], $row['USER_ID'], $row['STAKE'], 0, 14);
	  }
        } else if ($row['POINTS1'] < $row['POINTS2']) {
          // transfer 100 from to  
            unset($sdata);
            if ($row['SCORE1'] != $row['SCORE2'] )
  	      $sdata['CHALLENGES_WON'] = "CHALLENGES_WON-1";
	    $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST+1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER_ID']." AND SEASON_ID=".$row['SEASON_ID']);
            unset($sdata);
  	    $sdata['CHALLENGES_WON'] = "CHALLENGES_WON+1";
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST-1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER2_ID']." AND SEASON_ID=".$row['SEASON_ID']);

          if ($row['TYPE'] == 1) {
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $manager->transferMoney($row['USER_ID'], $row['USER2_ID'], $manager->mseason_id, $row['STAKE']*2);
	    else $manager->transferMoney($row['USER_ID'], $row['USER2_ID'], $manager->mseason_id, $row['STAKE']);
          } else if ($row['TYPE'] == 2) {
            $credits = new Credits();
            if ($row['SCORE1'] != $row['SCORE2'] )
	      $credits->transferCredit($row['USER_ID'], $row['USER2_ID'], 2*$row['STAKE'], 0, 14);
	    else $credits->transferCredit($row['USER_ID'], $row['USER2_ID'], $row['STAKE'], 0, 14);
	  }
        } else if ($row['POINTS1'] == $row['POINTS2']) {
          // transfer 100 from to  
            unset($sdata);
            if ($row['SCORE1'] < $row['SCORE2'] )
  	      $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST-1";
            else 
  	      $sdata['CHALLENGES_WON'] = "CHALLENGES_WON-1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER_ID']." AND SEASON_ID=".$row['SEASON_ID']);
            unset($sdata);
            if ($row['SCORE1'] > $row['SCORE2'] )
	      $sdata['CHALLENGES_LOST'] = "CHALLENGES_LOST-1";
            else $sdata['CHALLENGES_WON'] = "CHALLENGES_WON-1";
            $db->update('manager_users', $sdata, "USER_ID=".$row['USER2_ID']." AND SEASON_ID=".$row['SEASON_ID']);

          if ($row['TYPE'] == 1) {
            if ($row['SCORE1'] < $row['SCORE2'] )
	      $manager->transferMoney($row['USER_ID'], $row['USER2_ID'], $manager->mseason_id, $row['STAKE']*2);
	    else $manager->transferMoney($row['USER_ID'], $row['USER2_ID'], $manager->mseason_id, $row['STAKE']);
          } else if ($row['TYPE'] == 2) {
            $credits = new Credits();
            if ($row['SCORE1'] < $row['SCORE2'] )
	      $credits->transferCredit($row['USER_ID'], $row['USER2_ID'], $row['STAKE'], 0, 14);
	    else $credits->transferCredit($row['USER2_ID'], $row['USER_ID'], $row['STAKE'], 0, 14);
	  }
        }
	echo "Fix completed!";
     } else {
      echo "Could not fix it";
     }

}

?>