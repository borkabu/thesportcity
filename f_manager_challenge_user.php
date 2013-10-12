<?php
ini_set('display_errors', 1);
error_reporting (E_ALL & ~E_NOTICE);

//includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

//classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

//connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
//http header
include('class/headers.inc.php');
//page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_user.inc.php');
//--- build content data -----------------------------------------------------
//----------------------------------------------------------------------------

//include common header
$content = '';

if (!$auth->userOn()) {
  $error = $langs['LANG_ERROR_NOT_LOGED_IN_U'];
}
else {
   $manager = new Manager();
   $manager_user = new ManagerUser($manager->mseason_id);
   $process = true;
   if ($process) {
	$current_tour = $manager->getCurrentTour();
	if ($_GET['action'] == 'remove_challenge' && $auth->userOn()) {
		$sql="SELECT * from manager_challenges
			WHERE USER_ID=".$auth->getUserId()." 
				AND USER2_ID=".$_GET['user_id']." 
				AND TYPE=".$_GET['type']." 
				AND STATUS=1 
				AND SEASON_ID=".$manager->mseason_id." 
				AND TOUR_ID=".$current_tour;
		$db->query($sql);
		if ($row = $db->nextRow()) {
			$db->delete('manager_challenges', "USER_ID=".$auth->getUserId()." AND USER2_ID=".$_GET['user_id']." AND STATUS=1 AND SEASON_ID=".$manager->mseason_id." AND TOUR_ID=".$current_tour." AND TYPE=".$_GET['type']);
			if ($_GET['type'] == 2) {
				$challenge['CHALLENGE']['USER_ID'] = $_GET['user_id'];
				$challenge['CHALLENGE']['SEASON_ID'] = $manager->mseason_id;
				$challenge['CHALLENGE']['STAKE'] = $row['STAKE'];      
        			// unfreeze credits
				$credits = new Credits();
				$credits->unfreezeCredits($auth->getUserId(), $row['STAKE']);
				$user_credits = $_SESSION['_user']['CREDIT'];
				$user_id = $_GET['user_id'];
				$frozen_credits = $_SESSION['_user']['FROZEN_CREDITS'];
			}
		}
		else {
			$sql="SELECT * from manager_challenges
				WHERE USER_ID=".$auth->getUserId()." 
					AND USER2_ID=".$_GET['user_id']." 
					AND STATUS=1 AND SEASON_ID=".$manager->mseason_id." 
					AND TOUR_ID=".$current_tour."
					AND TYPE=".$_GET['type'];
			$db->query($sql);
			if ($row = $db->nextRow()) {
				if ($_GET['type'] == 2) {
					$data['CREDIT'][0]['CHALLENGE_ACCEPTED'][0]['X'] = 1;
				}
			}
		}
	}

	if ($current_tour >= 2 && !$manager->disabled_trade) {
		$sql = "SELECT END_DATE
			FROM manager_tours 
			WHERE NUMBER=".($current_tour-1)."
				AND SEASON_ID=".$manager->mseason_id;
		$db->query($sql);
		$row = $db->nextRow();
		$market_open_date = $row['END_DATE'];
		if ($_GET['action'] == 'challenge_throw' && $auth->userOn()) {
			$manager_user = new ManagerUser($manager->mseason_id);
                        if ($manager->canBeChallenged2($current_tour, $_GET['user_id'])) {

			  if ($manager_user->canChallenge($market_open_date, $current_tour) &&
				$manager->canBeChallenged($market_open_date, $current_tour, $_GET['user_id'])) {
				// can be challenged
				// add to challenge
                                $_GET['stake'] = floor($_GET['stake']);
				if (isset($_GET['stake']) &&
					$_GET['stake'] > 0 &&
					(($_GET['stake'] <= 1000 && $_GET['type'] == 1) || ($_GET['stake'] <= 100 && $_GET['type'] == 2 && $_SESSION["_user"]['CREDIT'] >= $_GET['stake']))) {
					unset($sdata);
					$sdata['SEASON_ID'] = $manager->mseason_id;
					$sdata['USER_ID'] = $auth->getUserId();
					$sdata['USER2_ID'] = $_GET['user_id'];
					$sdata['STATUS'] = 1;
					$sdata['TOUR_ID'] = $current_tour;
					$sdata['DATE_CHALLENGED'] = "NOW()";
					$sdata['STAKE'] = $_GET['stake'];
					$sdata['TYPE'] = $_GET['type'];
					$db->insert('manager_challenges', $sdata);
					$challenge['CHALLENGE_THROWN']['USER_ID'] = $_GET['user_id'];      
					$challenge['CHALLENGE_THROWN']['SEASON_ID'] = $manager->mseason_id;
					$challenge['CHALLENGE_THROWN']['STAKE'] = $_GET['stake'];      
					// freeze credits
					$credits = new Credits();
					$credits->freezeCredits($auth->getUserId(), $_GET['stake']);
					$user_credits = $_SESSION['_user']['CREDIT'];
					$user_id = $_GET['user_id'];
					$frozen_credits = $_SESSION['_user']['FROZEN_CREDITS'];
				} else {
				  	$error = $langs['LANG_ERROR_MANAGER_BAD_STAKE_U'];
					$challenge['CHALLENGE']['USER_ID'] = $_GET['user_id'];
					$challenge['CHALLENGE']['SEASON_ID'] = $manager->mseason_id;
					$user_credits = $_SESSION['_user']['CREDIT'];
					$user_id = $_GET['user_id'];
					$frozen_credits = $_SESSION['_user']['FROZEN_CREDITS'];
				}
			}
                             } else {
				  	$error = $langs['LANG_MANAGER_CHALLENGE_STAKE_LIMIT2_U'];
					$user_id = $_GET['user_id'];
					//$data['CHALLENGE'][0]['USER_ID'] = $_GET['user_id'];
					//$data['CHALLENGE'][0]['SEASON_ID'] = $manager->mseason_id;
			     }	

		}
	}
    }
}

  if (isset($error))
    $smarty->assign("error", $error);
  if (isset($challenge))
    $smarty->assign("challenge", $challenge);
  $smarty->assign("user_id", $user_id);
  $smarty->assign("user_credits", $user_credits);
  $smarty->assign("frozen_credits", $frozen_credits);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bar_manager_challenge_user_m.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bar_manager_challenge_user_m.smarty'.($stop-$start);

echo $content;
//close connections
include('class/db_close.inc.php');
?>