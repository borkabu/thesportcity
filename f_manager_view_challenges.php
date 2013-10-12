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
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $manager = new Manager();
  $user = new User($_GET['user_id']);

  // show list of completed challenges
  $sql="SELECT U1.USER_NAME USER_NAME1, U2.USER_NAME USER_NAME2, MS.PLACE, MS.POINTS, 
		MUT1.POINTS POINTS1, MUT2.POINTS POINTS2, MC.TOUR_ID, MC.STAKE, MC.TYPE
             FROM manager_standings MS, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=MC.SEASON_ID
							AND MUT1.TOUR_ID=MC.TOUR_ID
							AND MUT1.USER_ID=MC.USER_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=MC.SEASON_ID
							AND MUT2.TOUR_ID=MC.TOUR_ID
							AND MUT2.USER_ID=MC.USER2_ID
                  left join users U1 on U1.user_id=MC.USER_ID    
                  left join users U2 on U2.user_id=MC.USER2_ID
             WHERE MC.SEASON_ID=".$_GET['season_id']." 
               AND MC.USER2_ID=".$_GET['user_id']."
	       AND MS.Mseason_ID=MC.SEASON_ID
		AND MS.USER_ID=MC.USER_ID
               AND MC.STATUS=4
	    UNION 
	    SELECT U1.USER_NAME USER_NAME1, U2.USER_NAME USER_NAME2, MS.PLACE, MS.POINTS, 
		MUT1.POINTS POINTS1, MUT2.POINTS POINTS2, MC.TOUR_ID, MC.STAKE, MC.TYPE
             FROM manager_standings MS, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=".$_GET['season_id']."
							AND MUT1.TOUR_ID=MC.TOUR_ID
							AND MUT1.USER_ID=MC.USER_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=".$_GET['season_id']."
							AND MUT2.TOUR_ID=MC.TOUR_ID          
							AND MUT2.USER_ID=MC.USER2_ID
                  left join users U1 on U1.user_id=MC.USER_ID    
                  left join users U2 on U2.user_id=MC.USER2_ID
             WHERE MC.SEASON_ID=".$_GET['season_id']." 
               AND MC.USER_ID=".$_GET['user_id']."
	       AND MS.Mseason_ID=MC.SEASON_ID
		AND MS.USER_ID=MC.USER_ID
               AND MC.STATUS=4	
            ORDER BY TOUR_ID, USER_NAME1";
      $db->query($sql);    

//echo $sql;
      $c = 0;
      while ($row = $db->nextRow()) {
        $data['COMPLETED'][0]['CHALLENGES'][$c] = $row;
        $data['COMPLETED'][0]['CHALLENGES'][$c]['NUMBER'] = $c+1;
        if ($row['POINTS1'] > $row['POINTS2'])
          $data['COMPLETED'][0]['CHALLENGES'][$c]['USER_NAME1_WON'][0]['X'] = 1;
        else if ($row['POINTS1'] < $row['POINTS2'])
          $data['COMPLETED'][0]['CHALLENGES'][$c]['USER_NAME2_WON'][0]['X'] = 1;

        if ($row['TYPE'] == 1) {
          $data['COMPLETED'][0]['CHALLENGES'][$c]['BUDGET'][0]['X'] = 1;
        }
        else if ($row['TYPE'] == 2) {
          $data['COMPLETED'][0]['CHALLENGES'][$c]['CREDITS'][0]['X'] = 1;
        }

        $c++;
      }

      $data['SEASON_TITLE'] = $manager->title;
      $user_data = $user->getUserData();
      $data['USER_DATA'][0] = $user_data;
  // show challenges log

  // get list of available challenges

$tpl->setTemplateFile('tpl/f_manager_view_challenges.tpl.html');
$tpl->addData($data);

$content .= $tpl->parse();
// ----------------------------------------------------------------------------

// include common header
//include('inc/top.inc.php');
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>