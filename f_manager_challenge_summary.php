<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';

  $manager = new Manager();
  $current_tour = $manager->getCurrentTour();

  $add_content = ""; 
  if (!$manager->disabled_trade) {
    if ($auth->userOn()) {
      $sql= "SELECT * FROM manager_challenges WHERE status=1 AND challenge_id=".$_GET['challenge_id']; 
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $user_id1 = $row['USER_ID'];
        if (isset($_GET['action']) && $_GET['action']=='accept_invite' 
		&& isset($_GET['challenge_id'])
		&& (($row['TYPE'] == 1) || ($row['TYPE'] == 2 && $_SESSION['_user']['CREDIT']>= $row['STAKE']))) {
          $udata['STATUS'] = 2;  
          $udata['DATE_ACCEPTED'] = "NOW()";
          $db->update('manager_challenges', $udata, 'STATUS=1 AND USER2_ID='.$auth->getUserId().' AND CHALLENGE_ID='.$_GET['challenge_id']);
          unset($udata);
          if ($row['TYPE'] == 2) {
  	    $credits = new Credits();
            $credits->freezeCredits($auth->getUserId(), $row['STAKE']);
            $add_content = "###credits@@@".$_SESSION["_user"]["CREDIT"];
            $add_content .= "###frozen_credits@@@".$_SESSION["_user"]["FROZEN_CREDITS"];
          }
          if ($row['TYPE'] == 2) {
            unset($sdata);
            $sdata['STATUS'] = 5;

	    $sql = "SELECT CREDIT
	              FROM users 
        	     WHERE USER_ID=".$user_id1;
            $db->query($sql);
            if ($row = $db->nextRow()) {
                $db->update('manager_challenges', $sdata, "STATUS=1 AND TYPE=2
			and SEASON_ID=".$manager->mseason_id. " 
			AND (USER_ID=".$user_id1."
		       	    OR USER2_ID=".$user_id1.") 
			AND STAKE > ". $row['CREDIT']);
            }

            $db->update('manager_challenges', $sdata, "STATUS=1 AND TYPE=2
				and SEASON_ID=".$manager->mseason_id. " 
				AND (USER_ID=".$auth->getUserId()."
			       	    OR USER2_ID=".$auth->getUserId().") 
				AND STAKE > ". $_SESSION["_user"]["CREDIT"]);
          }
        }
        if (isset($_GET['action']) && $_GET['action']=='decline_invite' && isset($_GET['challenge_id']) && $row['STATUS'] == 1) {
          $udata['STATUS'] = 3;  
          $udata['DATE_REJECTED'] = "NOW()";
          $db->update('manager_challenges', $udata, 'STATUS=1 AND USER2_ID='.$auth->getUserId().' AND CHALLENGE_ID='.$_GET['challenge_id']);
          unset($udata);
          if ($row['TYPE'] == 2) {
  	    $credits = new Credits();
            $credits->unfreezeCredits($row['USER_ID'], $row['STAKE']);
          }
        }
      }
    }
  } 

  $manager = new Manager();
  $last_tour = $manager->getLastTour();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $manager_user = new ManagerUser($manager->mseason_id);
  
  $content = "challenges@@@".$managerbox->getManagerChallengeBox($manager_user).$add_content;

  echo $content;
// close connections
include('class/db_close.inc.php');
?>