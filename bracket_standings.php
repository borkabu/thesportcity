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
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');

// --- build content data -----------------------------------------------------
//$db->showquery=true;
//$tpl->setCacheTtl(60);

  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);

  if ($auth->userOn())
    $bracket_user = new BracketUser($bracket->tseason_id);

  $bracket_filter_box = $bracketbox->getBracketFilterBox($bracket->tseason_id);
 
 
    if (empty($_GET['page']))
      $page = 1;
    else $page = $_GET['page'];
    if (empty($perpage))
      $perpage = 50; //$page_size;

    $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

    $tour_id = isset($_GET['tour_id']) ? $_GET['tour_id'] : '';

    $opt = array(
      'class' => 'input',
      'options' => array(
        'USER_NAME' => 'LANG_USER_NAME_U'
      )
    );

    // sorting
    if (empty($_GET['order']))
      $param['order'] = 'PLACE asc ';
    else $param['order'] = $_GET['order'];

    $order = $param['order'];
    $so_fields = array('POINTS', 'USER_NAME', 'PLACE', 'MATCHES');
    for ($c=0; $c<sizeof($so_fields); $c++) {
      if ($param['order'] == $so_fields[$c].' desc') {
        $sort[$so_fields[$c].'_DESC_A']['URL'] = 'xxx';
        $sort[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
      }
      elseif ($param['order'] == $so_fields[$c].' asc') {
        $sort[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
        $sort[$so_fields[$c].'_ASC_A']['URL'] = 'xxx';
      }
      else {
        $sort[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
        $sort[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
      }
    }
    $search['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
    $search['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
    $search['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

    $param['where'] = '';
    if (!empty($_GET['where'])) {
      $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%') ";
      $search['FILTERED'][0]['X'] = 1;
    }

    if (!empty($_GET['tour_id'])) {
       $sql = "SELECT COUNT(*) ROWCOUNT
		 FROM bracket_users_tours MUT, users U, bracket_users MU
		WHERE U.USER_ID = MUT.USER_ID
                      AND MU.USER_ID = MUT.USER_ID
                      AND MU.SEASON_ID=".$bracket->tseason_id." 
		      AND MUT.TOUR_ID = ".$_GET['tour_id']." 
                      AND MUT.SEASON_ID=".$bracket->tseason_id." 
                      ".$param['where'];
    } else {
      $sql="SELECT COUNT(*) ROWCOUNT
          FROM users U, bracket_users MU, bracket_standings MS
         WHERE MS.USER_ID = U.USER_ID 
	   AND MS.MSEASON_ID=".$bracket->tseason_id." 
 	   AND MU.SEASON_ID=".$bracket->tseason_id." 
           AND MU.USER_ID = MS.USER_ID ".$param['where'];
    }
    $db->query($sql);
    $row = $db->nextRow();
    $user_count=$row['ROWCOUNT'];
//echo "<!--".$sql."-->";
    $db->free(); 
//echo $user_count;
    if ($user_count > 0) {
      if (!empty($_GET['tour_id'])) {
        $sql = "SELECT U.USER_NAME, U.USER_ID, MUT.POINTS, MUT.PLACE_TOUR AS PLACE, MUT.MATCHES,
			MUT.USER_ID AS PLAYER_ID, 
			C.CCTLD, CD.COUNTRY_NAME
		 FROM bracket_users_tours MUT, users U, bracket_users MU, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
		WHERE U.USER_ID = MUT.USER_ID
                      AND MU.USER_ID = MUT.USER_ID
                      AND MU.SEASON_ID=".$bracket->tseason_id." 
		      AND MUT.TOUR_ID = ".$_GET['tour_id']." 
                      AND MUT.SEASON_ID=".$bracket->tseason_id." 
		      AND U.COUNTRY = C.ID
                      ".$param['where']." 
                ORDER BY ".$param['order'].$limitclause;
         $db->query($sql);   
//echo $sql;
      } else {
        $sql="SELECT U.USER_NAME, U.USER_ID, 
                   MS.POINTS, MS.PLACE, MS.PLACE_PREV, MS.MATCHES, MU.SEASON_ID,
		   C.CCTLD, CD.COUNTRY_NAME
	          FROM users U, bracket_users MU, bracket_standings MS, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
                   WHERE MS.USER_ID = U.USER_ID 
	 	    AND MS.MSEASON_ID=".$bracket->tseason_id." 
 	  	    AND MU.SEASON_ID=".$bracket->tseason_id." 
                    AND MU.USER_ID = MS.USER_ID
		    AND U.COUNTRY = C.ID
                    ".$param['where']." 
                  GROUP BY MU.USER_ID
                  ORDER BY ".$param['order'].$limitclause;
      }
      $db->query($sql);

    } else {
        $sql="SELECT COUNT(*) ROWCOUNT
          FROM users U, bracket_users MU
         WHERE MU.SEASON_ID=".$bracket->tseason_id." 
           AND MU.USER_ID = U.USER_ID ".$param['where'];

        $db->query($sql);
        $row = $db->nextRow();
        $user_count=$row['ROWCOUNT'];
        $db->free(); 

        $sql="SELECT U.USER_NAME, U.USER_ID, MU.ALLOW_VIEW,
		0 AS POINTS, 0 AS PLACE, 0 as PLACE_PREV, 0 as MATCHES, MU.SEASON_ID, C.CCTLD, CD.COUNTRY_NAME
          FROM users U, bracket_users MU, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE MU.SEASON_ID=".$bracket->tseason_id." 
           AND MU.USER_ID = U.USER_ID
	    AND U.COUNTRY = C.ID
           ".$param['where']." 
         ORDER BY ".$param['order'].$limitclause;

       $db->query($sql);
     }
//echo $sql;
    $users = array();
    while ($row = $db->nextRow()) {
      $user = $row;
      if (!empty($row['CCTLD'])) {
        $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if (!empty($row['PLACE_PREV'])) {
        if ($row['PLACE'] < $row['PLACE_PREV']) {
          $user['UP'] = 1;
        } else if ($row['PLACE'] > $row['PLACE_PREV']) {
          $user['DOWN'] = 1;
        }
      }

      if ($auth->getUserId() == $row['USER_ID']) {          
        if ($row['ALLOW_VIEW'] == '-1') {
          $user['NOTALLOW'] = 1;
	  $user['CURRENT'] = 1;
        }
        else {
          $user['ALLOW'] = 1;
	  $user['CURRENT'] = 1;
        }
      }
      else {
        if (($auth->hasSupporter() && (!$bracket->season_over)) || $row['ALLOW_VIEW'] == '1') {
          $user['ALLOW'] = 1;
	  $user['NONCURRENT'] = 1;
        }
        else {
          $user['NOTALLOW'] = 1;
	  $user['NONCURRENT'] = 1;
        }
      }
      $users[] = $user;
    }

    $rows = $user_count;
    $paging = $pagingbox->getPagingBox($rows);

    $tours = array();
    if ($user_count > 0) {
       $sql = "SELECT DISTINCT MUT.NUMBER AS TOUR_ID
		 FROM bracket_tours MUT
		WHERE MUT.SEASON_ID=".$bracket->tseason_id." 
                ORDER BY MUT.TOUR_ID";
       $db->query($sql);   
       $c = 0;
       while ($row = $db->nextRow()) {
           $state = 'NORMAL'; 
           if (!empty($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
             $state = 'SELECTED'; 
           $tour = $row;
	   $tour[$state] = 1;
           $tour['NUMBER'] = $row['TOUR_ID'];
           $tour['TSEASON_ID'] = $bracket->tseason_id;
           $tours[] = $tour;
       }
       if (isset($_GET['tour_id'])) {
          $all['NORMAL']['TSEASON_ID'] = $bracket->tseason_id;
       } else {
          $all['SELECTED'] = 1;
       }
    }

  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  $smarty->assign("season_id", $bracket->tseason_id);
  $smarty->assign("tour_id", $tour_id);
  $smarty->assign("paging", $paging);
  $smarty->assign("users", $users);
  $smarty->assign("tours", $tours);
  $smarty->assign("all", $all);
  $smarty->assign("search", $search);
  $smarty->assign("order", $order);
  $smarty->assign("sort", $sort);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_standings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_standings.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

  define("ARRANGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');
?>