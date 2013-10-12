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
include('../lib/genre_types.inc.php');
// connections
include('../class/db_connect.inc.php');
$tpl=new template;
$frm=new form;
// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/trust.inc.php');

if (empty($_SESSION["_admin"][MENU_NEWS_MAIN_EDIT]) || strcmp($_SESSION["_admin"][MENU_NEWS_MAIN_EDIT], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_NEWS_MAIN_EDIT], 'RO') == 0)
  $ro = TRUE;

include('../class/prepare.inc.php');
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
//$db->showquery=true;
if(isset($_POST['form_save'])&&!$ro){
	// required fields
	$s_fields='';
	$i_fields=array('cat_id','genre','priority','reality', 'season_id', 'tournament_id', 'wseason_id');
	$d_fields=array('date_published');
	$c_fields=array('publish');
	$r_fields=array('title');

	$s_fields_d=array('title', 'source', 'source_name', 'descr');
	$i_fields_d=array('lang_id');
	$d_fields_d='';
	$c_fields_d='';

	if(!requiredFieldsOk($r_fields, $_POST)){
		$error=TRUE;
		$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
	};
	if(!$error){
		// get save data
		$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
		$tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
		// proceed to database updates
		if(!empty($_GET["news_id"]) && !empty($_POST["lang_id"])){
			// UPDATE
			$db->update('news', $sdata, "NEWS_ID=".$_GET["news_id"]);
			$tdata['news_id'] = $_GET["news_id"];
			$db->select('news_details', "*", "NEWS_ID=".$_GET["news_id"]." AND LANG_ID=".$_POST['lang_id']);
			if ($row = $db->nextRow())
  			  $db->update('news_details', $tdata, "NEWS_ID=".$_GET["news_id"]." AND LANG_ID=".$_POST['lang_id']);
  			else $db->insert('news_details', $tdata);
		}else{
			// INSERT
                        $trust = new Trust();
                        $cctl = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
			$sdata['DATE_CREATED']='SYSDATE()';
			$sdata['USER_ID']=$_SESSION["_user"]['USER_ID'];
			$sdata['CCTL']=$cctl;
			$db->insert('news', $sdata);

			$tdata['news_id'] = $db->id();
			$db->insert('news_details',$tdata);
		};
		$db->close();
		header('Location: '.$_POST['referer']);
		exit;
	};
};
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];
// new or edit?
if(isset($_GET['news_id'])){
	// news is being edited
	$fields='';
        $sql = "SELECT N.NEWS_ID, ND.TITLE, ND.DESCR, N.CAT_ID, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,  SUBSTRING(N.DATE_CREATED, 1, 16) DATE_CREATED, N.PUBLISH, ND.SOURCE, ND.SOURCE_NAME, N.GENRE, N.PRIORITY, N.REALITY, N.SEASON_ID, N.TOURNAMENT_ID, N.WSEASON_ID
		FROM news N LEFT JOIN news_details ND ON ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']."
		WHERE N.NEWS_ID=".$_GET['news_id'] ;
	$db->query($sql);
//echo $sql;
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
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
	$PRESET_VARS['publish']='N';
        $PRESET_VARS['priority'] =1;
 
};

// get common inputs
$data['CAT_ID']=inputCats('cat_id');
$data['GENRE']=inputGenreTypes('genre');
$data['SEASON_ID'] = inputManagerSeasons('season_id', isset($PRESET_VARS['season_id']) ? $PRESET_VARS['season_id'] : '', 80, true);
$data['WSEASON_ID'] = inputWagerSeasons('wseason_id', isset($PRESET_VARS['wseason_id']) ? $PRESET_VARS['wseason_id'] : '', 80, true);
//$data['TOURNAMENT_ID'] = inputManagerTournamentSeasons('tournament_id', isset($PRESET_VARS['tournament_id']) ? $PRESET_VARS['tournament_id'] : '', 80, true);

//$opt['options'] = $reality_types;

$opt = array(
  'class' => 'input',
  'options' => $reality_types
);


$data['REALITY'] = $frm->getInput(FORM_INPUT_SELECT, 'reality', $PRESET_VARS['reality'], $opt, $PRESET_VARS['reality']);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/news_edit.tpl.html');
$tpl->addData($data);
$content= $tpl->parse();

// close connections
include('../class/db_close.inc.php');

echo $content;
?>