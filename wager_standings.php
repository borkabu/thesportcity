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
//$db->showquery=true;
//$tpl->setCacheTtl(60);

  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

  $wager = new Wager();
  $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);

  if ($auth->userOn())
    $wager_user = new WagerUser($wager->tseason_id);

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->tseason_id);
 
  if (empty($_GET['page']))
    $page = 1;
  else $page = $_GET['page'];
  if (empty($perpage))
    $perpage = 50; //$page_size;

  $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

  $data['SEASON_ID'] = $wager->tseason_id;

    $opt = array(
      'class' => 'input',
      'options' => array(
        'USER_NAME' => 'LANG_USER_NAME_U',
        'WEALTH' => 'LANG_WEALTH_U'
      )
    );

    $search['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
    $search['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
    $search['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

    $param['where'] = '';
    if (!empty($_GET['where'])) {
      $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%') ";
      $search['FILTERED'] = 1;
    }

    $sql="SELECT COUNT(*) ROWCOUNT
          FROM users U, wager_users MU, wager_standings MS
         WHERE MS.USER_ID = U.USER_ID 
	   AND MS.SEASON_ID=".$wager->tseason_id."
 	   AND MU.SEASON_ID=".$wager->tseason_id."
           AND MU.USER_ID = MS.USER_ID ".$param['where'];

    $db->query($sql);
    $row = $db->nextRow();
    $user_count=$row['ROWCOUNT'];
    $db->free(); 
    if ($user_count > 0) {
        $sql="SELECT U.USER_NAME, U.USER_ID, MU.GAMES,  MU.BALANCE,
                   ROUND(MS.WEALTH, 2) as WEALTH, MS.PLACE, MU.SEASON_ID, MU.REFILLED,
		   ROUND(MU.WINS*100/(if (MU.WINS+MU.LOSSES > 0, MU.WINS+MU.LOSSES, 1)), 0) KOEFF,
		   IF(GAMES = 0, 0, ROUND(TOTAL_STAKES/GAMES, 2)) STAKE_AVG, C.CCTLD, CD.COUNTRY_NAME
	          FROM users U, wager_users MU, wager_standings MS , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                   WHERE MS.USER_ID = U.USER_ID 
	 	    AND MS.SEASON_ID=".$wager->tseason_id."
 	  	    AND MU.SEASON_ID=MS.SEASON_ID
                    AND MU.USER_ID = MS.USER_ID
		    AND U.COUNTRY = C.ID
                    ".$param['where']." 
                  GROUP BY MU.USER_ID
                  ORDER BY MS.PLACE ASC, MS.WEALTH DESC, U.USER_NAME ".$limitclause;
      $db->query($sql);
//echo $sql;
    } else {
       $sql="SELECT COUNT(*) ROWCOUNT
         FROM users U, wager_users MU
        WHERE MU.SEASON_ID=".$wager->tseason_id."
          AND MU.USER_ID = U.USER_ID ".$param['where'];

       $db->query($sql);
       $row = $db->nextRow();
       $user_count=$row['ROWCOUNT'];
       $db->free(); 

       $sql="SELECT U.USER_NAME, U.USER_ID, 0 AS KOEFF, MU.GAMES, MU.REFILLED, ROUND(MU.TOTAL_STAKES/MU.GAMES, 2) STAKE_AVG,
		0 AS PLACE, MU.SEASON_ID, MU.BALANCE, C.CCTLD, CD.COUNTRY_NAME
         FROM users U, wager_users MU , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
        WHERE MU.SEASON_ID=".$wager->tseason_id."
          AND MU.USER_ID = U.USER_ID
	    AND U.COUNTRY = C.ID
          ".$param['where']." 
        ORDER BY U.USER_NAME ".$limitclause;

      $db->query($sql);
    }

    $users = array();
    while ($row = $db->nextRow()) {
      $user = $row;
      if (!empty($row['CCTLD'])) {
        $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if ($auth->getUserId() == $row['USER_ID']) 
        $user['CURRENT'] = 1;
      else $user['NONCURRENT'] = 1;

      if (!empty($row['PLACE_PREV'])) {
        if ($row['PLACE'] < $row['PLACE_PREV']) {
          $user['UP'] = 1;
        } else if ($row['PLACE'] > $row['PLACE_PREV']) {
          $user['DOWN'] = 1;
        }
      } 

      $users[] = $user;
    }
    $rows = $user_count;
    $db->free();
 
  $paging = $pagingbox->getPagingBox($rows);

  $smarty->assign("wager_filter_box", $wager_filter_box);
  $smarty->assign("season_id", $wager->tseason_id);
//  $smarty->assign("tour_id", $tour_id);
  $smarty->assign("paging", $paging);
  $smarty->assign("users", $users);
//  $smarty->assign("tours", $tours);
//  $smarty->assign("all", $all);
  $smarty->assign("search", $search);
//  $smarty->assign("order", $order);
//  $smarty->assign("sort", $sort);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_standings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_standings.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

  define("WAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>