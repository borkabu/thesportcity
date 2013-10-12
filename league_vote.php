<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers_no_cache.inc.php');
// page requirements
include('class/inputs.inc.php');

// --- build content data -----------------------------------------------------

// check if user can vote
//$db->showquery=true;
if ($auth->userOn()) {

  if (isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {

    $prefix = "";
    if ($_GET['league_type'] == 1)
      $prefix = 'manager';
    else if ($_GET['league_type'] == 2) 
      $prefix = 'wager';
    else if ($_GET['league_type'] == 3) 
      $prefix = 'bracket';
    else if ($_GET['league_type'] == 4)
      $prefix = 'rvs_manager';


    $sql= "SELECT ML.USER_ID
            FROM ".$prefix."_leagues ML
           WHERE ML.LEAGUE_ID=".$_GET['item_id']; 

    $db->query($sql);     
    if ($row = $db->nextRow()) {
      $owner = $row['USER_ID'];
    }

    $sql= "SELECT DISTINCT MLV.VOTE, MLM.LEAGUE_ID, MLM.USER_ID
            FROM ".$prefix."_leagues_members MLM
			left join ".$prefix."_leagues_votes MLV
					ON MLV.LEAGUE_ID=MLM.LEAGUE_ID
						AND MLV.USER_ID=".$auth->getUserId()."
           WHERE MLM.LEAGUE_ID=".$_GET['item_id']."
		AND MLM.STATUS <> 3
		AND MLM.USER_ID=".$auth->getUserId(); 

    $db->query($sql);     
    if ($row = $db->nextRow()) {
      $vote = 0;
      if (isset($_GET['action']) && $_GET['action'] == 'thumbup') {
        $data['VOTING'][0]['THUMB_UP'][0]['X'] = 1;
        $vote = 1;
      } else if (isset($_GET['action']) && $_GET['action'] == 'thumbdown') {
        $data['VOTING'][0]['THUMB_DOWN'][0]['X'] = 1;
        $vote = -1;
      }
      if ($_GET['mode'] == 'leagues') {
        $sql = "REPLACE INTO ".$prefix."_leagues_votes
		VALUES (".$_GET['item_id'].",
			".$auth->getUserId().",
			".$vote.", NOW())";
      } 
      $db->query($sql);

      $sql="SELECT SUM(VOTE) RATING from ".$prefix."_leagues_votes WHERE LEAGUE_ID=".$_GET['item_id'];
      $db->query($sql);
      $row = $db->nextRow();
      $sdata['RATING'] = $row['RATING'];
      $db->update($prefix."_leagues", $sdata, "LEAGUE_ID=".$_GET['item_id']);

      $sql="SELECT USER_ID FROM ".$prefix."_leagues WHERE LEAGUE_ID=".$_GET['item_id'];
      $db->query($sql);
      if ($row = $db->nextRow()) {
	unset($sdata);
        $league = new League($prefix, $_GET['item_id']);
        $sdata['LEAGUE_OWNER_RATING'] = $league->getOwnerRating($row['USER_ID']);
        $db->update("users", $sdata, "USER_ID=".$owner);
      }

      $data['VOTING'][0]['LEAGUE_ID'] = $_GET['item_id'];
      $data['VOTING'][0]['LEAGUE_TYPE'] = $_GET['league_type'];
    }
  }
}

// send email if voting is moderated
// update rating if not

$tpl->setCacheLevel(TPL_CACHE_NOTHING);

$tpl->setTemplateFile('tpl/league_vote.tpl.html');

$tpl->addData($data);

$content=$tpl->parse();
echo $content;

include('class/db_close.inc.php');
?>