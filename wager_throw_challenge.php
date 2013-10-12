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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
//--- build content data -----------------------------------------------------
//----------------------------------------------------------------------------

//include common header
$content = '';

if (!$auth->userOn()) {
   $error['MSG'] = $langs['LANG_ERROR_NOT_LOGED_IN_U'];
}
else {
  if (isset($_GET['game_id'])) {
    $game_id = $_GET['game_id'];
    if (isset($_GET[$game_id])) {
      $wager = new Wager();
  
      $sql = "SELECT *
		FROM wager_games WG
			left join wager_challenges WC ON WG.GAME_ID=WC.GAME_ID
					and WC.USER_ID=".$auth->getUserId()."
		WHERE WC.USER_ID is NULL and WC.USER2_ID is NULL
			AND WG.GAME_ID=".$_GET['game_id'];

      $sql="SELECT WC.CHALLENGE_ID, WC.STAKE, WC.OUTCOME, S.SPORT_ID,
		  DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
	          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
	          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2,
	          G.SCORE1, G.SCORE2, U.USER_NAME, U2.USER_NAME as USER_NAME2, WC.USER_ID, WC.USER2_ID
             FROM wager_games WG
			left join wager_challenges WC ON WG.GAME_ID=WC.GAME_ID
					and WC.USER_ID=".$auth->getUserId()."
                	left join users U on WC.USER_ID=U.USER_ID
	                left join users U2 on WC.USER2_ID=U.USER_ID,
		  seasons S, games G
	              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
	              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
             WHERE WG.GAME_ID=G.GAME_ID
		AND WC.USER_ID is NULL and WC.USER2_ID is NULL
		AND WG.GAME_ID=".$_GET['game_id']."
		AND S.SEASON_ID=G.SEASON_ID";
//echo $sql;
      $db->query($sql);
      if ($row = $db->nextRow()) {
	// add to challenge
	if (isset($_GET['stake']) &&
            $_GET['stake'] > 0 &&
	    $_SESSION["_user"]['CREDIT'] >= $_GET['stake']) {
           unset($sdata);
	   $sdata['GAME_ID'] = $_GET['game_id'];
	   $sdata['SEASON_ID'] = $wager->tseason_id;
	   $sdata['USER_ID'] = $auth->getUserId();
	   $sdata['STATUS'] = 1;
	   $sdata['DATE_CHALLENGED'] = "NOW()";
	   $sdata['STAKE'] = $_GET['stake'];
	   $sdata['OUTCOME'] = $_GET[$game_id];
	   $db->insert('wager_challenges', $sdata);
           $idata['OUTCOME'] = $sdata['OUTCOME'];
  	   $idata['CHALLENGE_THROWN'] = 1;
	   $idata['STAKE'] = $_GET['stake'];      
		// freeze credits
	   $credits = new Credits();
	   $credits->freezeCredits($auth->getUserId(), $_GET['stake']);

           if ($idata['OUTCOME'] == 0)
             $text = $wager_challenge_events_descr[2]; 
           else $text = $wager_challenge_events_descr[1]; 
           $text = str_replace("%u", $auth->getUserName(), $text);
           if ($idata['OUTCOME'] == 1)
             $text = str_replace("%t", $row['TEAM_NAME1'], $text);
           else if ($idata['OUTCOME'] == -1)
             $text = str_replace("%t", $row['TEAM_NAME2'], $text);
           $text = str_replace("%m", $row['TEAM_NAME1']." - ".$row['TEAM_NAME2'], $text);
           $idata['CHALLENGE'] = $text;

	} else {
 	   $idata['CHALLENGE'] = 1;
    	   $error['MSG'] = $langs['LANG_ERROR_WAGER_BAD_STAKE_U'];
	}
      }
    } else {
       $idata['CHALLENGE'] = 1;

       $error['MSG'] = $langs['LANG_ERROR_WAGER_NO_OUTCOME_U'];
    }
    $idata['SEASON_ID'] = $wager->tseason_id;
    $idata['GAME_ID'] = $_GET['game_id'];
    $idata['USER_CREDITS'] = $_SESSION['_user']['CREDIT'];
    $idata['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'];
  }
}

  $smarty->assign("idata", $idata);
  if (isset($error))
    $smarty->assign("error", $error);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bar_wager_challenge_throw.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bar_wager_challenge_throw.smarty'.($stop-$start);

echo $content;
//close connections
include('class/db_close.inc.php');
?>