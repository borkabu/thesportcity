<?php
/*
===============================================================================
news_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit news message
  - edit news keywords
  - create new news message

TABLES USED: 
  - BASKET.NEWS
  - BASKET.KEYWORDS
  - BASKET.SOURCES

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/

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

// user session
include('class/ss_const.inc.php');
include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

if (!$auth->userOn()) {
   $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_CONTENT_LOGIN_U'];
}

if(isset($_POST['add_club_event']) && $auth->userOn() && isset($_POST['group_id']) &&
   ((isset($_POST['event_id']) && is_numeric($_POST['event_id'])) || 
    empty($_POST['event_id']))){

      $event_id = $clubbox->addClubEventItem($_POST['group_id'], isset($_POST['event_id']) ? $_POST['event_id'] : '');

      if ($event_id == '') {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_ADD_CLUB_EVENT');
      }
      else {
        header("Location: clubs.php?club_id=".$_POST['group_id']);
/*        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
        $added = true;*/
      }
}

if ($auth->userOn() && isset($_GET['club_id'])) {
  $data['GROUP_ID'] = $_GET['club_id'];
  $data['EVENT_ID'] = !empty($_GET['event_id']) ? $_GET['event_id'] : '';
  $group = new Group($_GET['club_id']);
  
  if ($group->isGroupModerator($auth->getUserId())) {
     $data['lang_id']=$_SESSION['lang_id'];
     if(isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
		// news is being edited
        $sql = "SELECT FGED.TITLE, FGE.EVENT_ID, ML.GROUP_ID, MS.GROUP_NAME, 
			FGED.DESCR, FGED.RESULTS, FGE.PARTICIPANTS
            FROM forum_groups ML, forum_groups_details MS, forum_groups_events FGE, forum_groups_events_details FGED
           WHERE FGE.EVENT_ID=".$_GET['event_id']."
	        AND ML.GROUP_ID=FGE.GROUP_ID
	        AND MS.GROUP_ID=ML.GROUP_ID
		AND FGE.EVENT_ID=FGED.EVENT_ID			
		AND FGED.LANG_ID=".$_SESSION['lang_id']."
		AND MS.LANG_ID=".$_SESSION['lang_id']; 

	$db->query($sql);
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
	//        header('Location: index.php');
		exit;
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $data[$key] = $val;
             }
	};
     } else {
        $data['DESCR'] = "";
        $data['RESULTS'] = "";
        $data['PARTICIPANTS'] = 0;
     }
  }


// get common inputs
// content
//print_r($data);

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/club_event_add.tpl.html');
  $tpl->addData($data);
  $content .= $tpl->parse();

}
 
// ----------------------------------------------------------------------------
  define("CLUBS", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');

?>