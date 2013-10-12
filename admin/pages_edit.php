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
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');
// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');
// connections
include('../class/db_connect.inc.php');
$tpl=new template;
$frm=new form;
// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

//echo $_admin[MENU_NEWS_EDIT];
if (empty($_SESSION["_admin"][MENU_MENU]) || strcmp($_SESSION["_admin"][MENU_MENU], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_MENU], 'RO') == 0)
  $ro = TRUE;

include('../class/prepare.inc.php');
//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
	// required fields
	$s_fields=array('page_name');
	$i_fields='';
	$d_fields='';
	$c_fields=array('publish');
	$r_fields=array('page_name');

	$s_fields_d = array('title','pic_location','pic_title','description');
	$d_fields_d = '';
	$c_fields_d = '';
  	$i_fields_d = array('lang_id');
  	$r_fields_d = array('title');
	
	// check for required fields
	if (!requiredFieldsOk ( $r_fields, $_POST ) || 
            !requiredFieldsOk ( $r_fields_d, $_POST )) {

		$error=TRUE;
		$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
	};
	if(!$error){
		// get save data
		$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
 	    	$tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
		// proceed to database updates
		if(!empty($_GET["page_id"]) && !empty($_POST["lang_id"])){
			// UPDATE                  
			$sdata['DATE_CREATED']='SYSDATE()';
			$db->update('pages', $sdata, "PAGE_ID=".$_GET["page_id"]);
		        $tdata['page_id'] = $_GET["page_id"];
			$tdata['CUSER_ID']=$_SESSION["_user"]['USER_ID'];
			$db->replace('pages_details', $tdata);
		}else{
			// INSERT
			$sdata['DATE_CREATED']='SYSDATE()';
			$db->insert ('pages', $sdata );
			$tdata['page_id'] = $db->id();
	        	$db->insert('pages_details', $tdata);
		};
		// redirect to news page
		header('Location: pages.php');
		exit;
	};
};
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];
// new or edit?
if(isset($_GET['page_id'])){
	// news is being edited
	$sql = "SELECT P.PAGE_ID, PD.TITLE, PD.DESCRIPTION, PD.PIC_LOCATION, PD.PIC_TITLE, P.PAGE_NAME
			FROM pages P LEFT JOIN pages_details PD ON PD.PAGE_ID=P.PAGE_ID AND PD.LANG_ID=".$_SESSION['lang_id']."
		WHERE P.PAGE_ID=".$_GET['page_id'] ;
	$db->query($sql);
echo $sql;
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
		//header('Location: pages.php');
		//exit;
		$PRESET_VARS['publish']='Y';
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $data[$key] = $val;
             }
	};
	$db->free();
}else{
	// adding news
	$PRESET_VARS['publish']='Y';
};

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/pages_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();
include('../class/db_close.inc.php');
?>