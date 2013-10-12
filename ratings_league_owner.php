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
//include('class/rating.inc.php');

// --- build content data -----------------------------------------------------
//$db->showquery=true;

  $content = '';
  $content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);

  if (empty($_GET['page']))
    $page = 1;
  else $page = $_GET['page'];
  if (empty($perpage))
    $perpage = 100; //$page_size;

  $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $query_input = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));

    $data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

    $param['where'] = "";
    if (!empty($_GET['query'])) {
      $param['where'] = " AND UPPER(USER_NAME) like UPPER('%".$_GET['query']."%') ";
      $filtered = 1;
    }

    $sql="SELECT COUNT(DISTINCT U.USER_ID) ROWCOUNT
          FROM users U
         WHERE U.LEAGUE_OWNER_RATING > 0 ".$param['where'];

    $db->query($sql);
    $row = $db->nextRow();
    $user_count=$row['ROWCOUNT'];
//echo "<!--".$sql."-->";
    $db->free(); 
//echo $user_count;
    $sql="SELECT U.USER_NAME, U.USER_ID, U.LEAGUE_OWNER_RATING as KOEFF, C.CCTLD, 
		CD.COUNTRY_NAME, count(ML.LEAGUE_ID) LEAGUES, ROUND(U.LEAGUE_OWNER_RATING / count(ML.LEAGUE_ID), 2) as AVG_KOEFF 
	          FROM manager_leagues ML, users U, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                   WHERE ML.USER_ID = U.USER_ID
			AND U.COUNTRY = C.ID
			AND U.LEAGUE_OWNER_RATING <> 0
                    ".$param['where']." 
		  GROUP BY ML.USER_ID
                  ORDER BY U.LEAGUE_OWNER_RATING DESC ".$limitclause;
//echo $sql;
    $db->query($sql);
    $users = array();
    $c = 0;
    while ($row = $db->nextRow()) {
      $user = $row;
      $user['PLACE'] = ++$c + ($page - 1)* $perpage;
      if (!empty($row['CCTLD'])) {
        $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }
      $users[] = $user;
    }
 
  $paging = $pagingbox->getPagingBox($user_count);

  //$tpl->addData($data);
  $smarty->assign("users", $users);
  $smarty->assign("paging", $paging);
  $smarty->assign("query_input", $query_input);  
  if (isset($filtered))
    $smarty->assign("filtered", true);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/ratings_league_owner.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/ratings_league_owner.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>