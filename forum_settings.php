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
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
  $submenu = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

if ($auth->userOn()) {
  if(isset($_POST['form_save'])){
	// required fields
	$s_fields='';
	$i_fields=array('topic_sorting', 'editor_window');
	$d_fields='';
	$c_fields='';
	$r_fields='';
        $error= false;
	if(!requiredFieldsOk($r_fields, $_POST)){
		$error=TRUE;
		$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
	};
	if(!$error){
		// get save data
		$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
		// proceed to database updates
			// UPDATE
		$db->update('users', $sdata, "USER_ID=".$auth->getUserId());
                
		$_SESSION['_user']['TOPIC_SORTING'] = isset($_POST['topic_sorting']) ? $_POST['topic_sorting'] : 0;
		$_SESSION['_user']['EDITOR_WINDOW'] = isset($_POST['editor_window']) ? $_POST['editor_window'] : 0;

		$errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
		$errorbox1 = $errorbox->getMessageBox('MESSAGE_SETTINGS_SAVED');

	};
  };

  $opt = array(
    'class' => 'input',
    'options' => array(
      0 => 'LANG_UPWARDS_U',
      1 => 'LANG_DOWNWARDS_U'
    )
  );
  
  $topic_sorting = $frm->getInput(FORM_INPUT_SELECT, 'topic_sorting', isset($_SESSION['_user']['TOPIC_SORTING']) ? $_SESSION['_user']['TOPIC_SORTING'] : 0, $opt, isset($_SESSION['_user']['TOPIC_SORTING']) ? $_SESSION['_user']['TOPIC_SORTING'] : 0);   

  $opt = array(
    'class' => 'input',
    'options' => array(
      0 => 'LANG_ABOVE_COMMENTS_U',
      1 => 'LANG_UNDERNEATH_COMMENTS_U'
    )
  );

  $editor_window_position = $frm->getInput(FORM_INPUT_SELECT, 'editor_window', isset($_SESSION['_user']['EDITOR_WINDOW']) ? $_SESSION['_user']['EDITOR_WINDOW'] : 0, $opt, isset($_SESSION['_user']['EDITOR_WINDOW']) ? $_SESSION['_user']['EDITOR_WINDOW'] : 0);   

  $smarty->assign("topic_sorting", $topic_sorting);
  $smarty->assign("editor_window_position", $editor_window_position);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/forum_settings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/forum_settings.smarty'.($stop-$start);

}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_forum.inc.php');

include('class/db_close.inc.php');
?>