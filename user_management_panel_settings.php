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
if ($auth->userOn()) {
  $submenu = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 16);

  if(isset($_POST['form_save'])){
	// required fields
	$s_fields='';
	$i_fields='';
	$d_fields='';
	$c_fields=array('pm_email', 'stock_profit_email');
	$r_fields='';
        $error= false;
	if(!requiredFieldsOk($r_fields, $_POST)){
		$error=TRUE;
		$data['ERROR']['MSG']=$langs['LANG_ERROR_MAND_U'];
	};
	if(!$error){
		// get save data
		$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
		// proceed to database updates
			// UPDATE
		$db->update('users', $sdata, "USER_ID=".$auth->getUserId());
                
		$_SESSION['_user']['PM_EMAIL'] = isset($_POST['pm_email']) ? $_POST['pm_email'] : 'N';

		$errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
		$errorbox1 = $errorbox->getMessageBox('MESSAGE_SETTINGS_SAVED');

	};
  };

  $smarty->assign("pm_email", isset($_SESSION['_user']['PM_EMAIL']) ? $_SESSION['_user']['PM_EMAIL'] : 'Y');

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/user_management_panel_settings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/user_management_panel_settings.smarty'.($stop-$start);

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
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>