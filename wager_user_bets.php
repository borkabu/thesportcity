<?php
/*
===============================================================================
toto.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows totalizator result archive

TABLES USED: 
  - BASKET.totalizators
  - BASKET.totalizator_votes
  - BASKET.users
  - BASKET.games

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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------

  $content = '';
    
  if (isset($_GET['user_id']) && isset($_GET['season_id'])) {
     $user = new User();
     $user->getUserIdFromId($_GET['user_id']);
     $wager = new Wager($_GET['season_id']);

     if (empty($_GET['page']))
       $page = 1;
     else $page = $_GET['page'];
     if (empty($perpage))
       $perpage = 50; //$page_size;

     $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

     $sql = "SELECT count(*) CNT
        FROM
          wager_games WG, wager_votes WV, wager_seasons TS, seasons S, games G
        WHERE
          WG.GAME_ID=G.GAME_ID
          AND G.SEASON_ID = S.SEASON_ID
          AND WG.WSEASON_ID=TS.SEASON_ID
          AND WG.WSEASON_ID=".$_GET['season_id']."
	  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() 
          AND WV.VOTE_ID IS NOT NULL
          AND WG.WAGER_ID=WV.WAGER_ID 
	  AND WV.USER_ID=".$_GET['user_id'];
     $db->query($sql);   
     while ($row = $db->nextRow()) {
       $rows = $row['CNT'];
     }


     $sql = "SELECT WG.WAGER_ID, WG.GAME_ID, WG.PUBLISH, TSD.TSEASON_TITLE, 
	  WG.STAKES1, WG.STAKES0, WG.`STAKES-1` as STAKES_1, WV.DIFFERENCE,
		WV.HOST_SCORE, WV.VISITOR_SCORE,
          WG.START_DATE, WV.RETURN, WG.KOEFF, WV.PROCESSED,
	  DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') GAME_DAY,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
          SD.SEASON_TITLE, WV.STAKE, WV.CHOICE, WV.VOTE_ID, S.SPORT_ID, WV.POINTS
        FROM
          wager_games WG, wager_votes WV, wager_seasons TS
		left JOIN wager_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
		, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G
              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
		LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
		LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
        WHERE
          WG.GAME_ID=G.GAME_ID
          AND G.SEASON_ID = S.SEASON_ID
          AND WG.WSEASON_ID=TS.SEASON_ID
          AND WG.WSEASON_ID=".$_GET['season_id']."
	  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() 
          AND WV.VOTE_ID IS NOT NULL
          AND WG.WAGER_ID=WV.WAGER_ID 
	  AND WV.USER_ID=".$_GET['user_id']."
        ORDER BY G.START_DATE DESC, T1.TEAM_NAME ".$limitclause;

         $db->query($sql);   

//echo $sql;
         $tourstats = array();
         while ($row = $db->nextRow()) {
           $bet = $row;
           if ($row['PROCESSED'] == 'Y') { // calc winnings       
             $choice = 0;
             $win_coeff = 1.1;
             if ($row['SCORE1'] > $row['SCORE2']) {
               $choice = 1;
               $bet['WINNER1']['WIN'] = 1;
               $bet['KOEFF'] = 1 + $row['KOEFF'];
               if ($row['SCORE2'] - $row['SCORE1'] == $row['DIFFERENCE']) {
                 $bet['WINNER1']['WIN2'] = 1;
                 if ($row['STAKE'] > 0)
                   $bet['KOEFF'] = $bet['RETURN'] / $row['STAKE'];
               }
             }
             else if ($row['SCORE1'] < $row['SCORE2']) {
                  $choice = '_1';
                  $bet['WINNER_1']['WIN'] = 1;
                  $bet['KOEFF'] = 1 + $row['KOEFF'];
                  if ($row['SCORE2'] - $row['SCORE1'] == $row['DIFFERENCE']) {
                    $bet['WINNER_1']['WIN2'] = 1;
                    if ($row['STAKE'] > 0)
                      $bet['KOEFF'] = $bet['RETURN'] / $row['STAKE'];
                  }
             }
             else {
               $game['WINNER0'] = 1;
               if ($row['DIFFERENCE'] == 0) {
                 $bet['WINNER0']['WIN2'] = 1;
               }
             }
             if ($row['STAKE'] > 0)
               $bet['KOEFF'] = round($bet['RETURN'] / $row['STAKE'], 2);
             if ($row['CHOICE'] != $choice)
               $bet['WINNINGS'] = 0;
             else 
               $bet['WINNINGS'] = $win_coeff * $row['STAKE'];
           }


           $bets[] = $bet;
         }

       $smarty->assign("user_name", $user->user_name);
       $smarty->assign("season_title", $wager->title);
       if (count($bets) > 0)
         $smarty->assign("bets", $bets);
  } 

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_user_bets.smarty');    
  $content .= $pagingbox->getPagingBox($rows);
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_user_bet.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

// close connections
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>