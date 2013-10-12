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
include('../class/trust.inc.php');

if (empty($_SESSION["_admin"][MENU_NEWS_VIDEO]) || strcmp($_SESSION["_admin"][MENU_NEWS_VIDEO], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
} 

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_NEWS_VIDEO], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
//$db->showquery=true;
if(isset($_POST['form_save']) && !$ro){
	// required fields
	$s_fields=array('link', 'source', 'source_name', 'thumbnail');
	$i_fields=array('cat_id');;
	$d_fields=array('date_published');
	$c_fields=array('publish');

	$s_fields_d=array('title', 'descr');
	$i_fields_d=array('lang_id');
	$d_fields_d='';
	$c_fields_d='';

	$r_fields=array('title', 'link');
	if(!requiredFieldsOk($r_fields, $_POST)){
		$error=TRUE;
		$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
	};
	if(!$error){
		// get save data
		$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
		$tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);

		// proceed to database updates
		if (!empty($_GET["video_id"]) && !empty($_POST["lang_id"])){
			// UPDATE
		   $db->update('video', $sdata, "VIDEO_ID=".$_GET["video_id"]);
		   $tdata['video_id'] = $_GET["video_id"];
		   $db->select('video_details', "*", "VIDEO_ID=".$_GET["video_id"]." AND LANG_ID=".$_POST['lang_id']);
		   if ($row = $db->nextRow())
  		     $db->update('video_details', $tdata, "VIDEO_ID=".$_GET["video_id"]." AND LANG_ID=".$_POST['lang_id']);
  		   else $db->insert('video_details', $tdata);
		}
		else {
		      // INSERT
                    $trust = new Trust();
                    $cctl = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
		  //  $sdata['DATE_CREATED']='SYSDATE()';
		    $sdata['USER_ID']=$_SESSION["_user"]['USER_ID'];
		    $sdata['CCTL']=$cctl;
		    $db->insert('video', $sdata);

		    $tdata['video_id'] = $db->id();
		    $db->insert('video_details',$tdata);
                }

		header('Location: '.$_POST['referer']);
		exit;
	};
};
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];
// new or edit?
if(isset($_GET['video_id'])){
	// news is being edited
        $sql = "SELECT V.VIDEO_ID, VD.TITLE, VD.DESCR, V.LINK, V.THUMBNAIL, V.CAT_ID, SUBSTRING(V.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,  V.PUBLISH, V.SOURCE, V.SOURCE_NAME
		FROM video V 
			LEFT JOIN video_details VD ON VD.VIDEO_ID=V.VIDEO_ID AND VD.LANG_ID=".$_SESSION['lang_id']."
		WHERE V.VIDEO_ID=".$_GET['video_id'] ;
	$db->query($sql);
//echo $sql;
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
		$PRESET_VARS['publish']='Y';
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = stripslashes($val);
               $data[$key] = stripslashes($val);
             }
	};
	$db->free();

}else{
	// adding news
	$PRESET_VARS['publish']='Y';
};

// get common inputs
$data['CAT_ID']=inputCats('cat_id');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/video_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();
//echo $errtext;
// close connections
include('../class/db_close.inc.php');
?>