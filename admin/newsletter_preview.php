<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
cat_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit individual category
  - create new category

TABLES USED: 
  - BASKET.CATS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/manager.inc.php');
include('../class/wager.inc.php');
include('../class/box.inc.php');
include('../class/newsbox.inc.php');
include('../class/announcementbox.inc.php');
include('../class/newsletter.inc.php');
include('../class/email.inc.php');


include('../smarty/libs/Smarty.class.php');
 $smarty = new Smarty;
 //$smarty->debugging = true;
 $smarty->registerPlugin("function","translate", "get_translation");

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
     global $db;
     global $conf_site_url;
     global $conf_home_dir;
     global $tpl;
    
     $sql= "SELECT N.*, ND.*, NS.*, 'borka' as USER_NAME, 'en' as SHORT_CODE
		FROM newsletter N, newsletter_details ND, newsletter_subscribers NS
		WHERE N.ID=ND.ID AND N.ID=".$_GET['id']." 
			AND NS.USER_ID=6
			AND NS.ID=N.ID
			AND ND.LANG_ID=".$_SESSION['lang_id'];
//echo $sql;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $title = $row['TITLE'];
       $lang = $row['SHORT_CODE'];
       $lang_id = $row['LANG_ID'];
       include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');


       while (list($key, $val) = each($langs)) {
         $fdata[$key] = $val;
       }

       $newsletter = new Newsletter();
       $email = new Email();
       if ($row['TYPE'] == 0) {
         $sdata = $newsletter->generateGeneralNewsletterContent($row, $lang_id);
         echo $email->getEmailFromTemplate ('email_newsletter_general_html', $sdata) ;
       } else if ($row['TYPE'] == 1) {
         $manager = new Manager($row['SEASON_ID']);
         $user = $manager->getUser(6);
         $sdata = $newsletter->generateManagerNewsletterContent($row, $user, $row['SEASON_ID'], $lang_id);
         $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
         $tpl->setTemplateFile('../tpl/email_newsletter_manager_html.tpl.html');
       } else if ($row['TYPE'] == 2) {
         $wager = new Wager($row['SEASON_ID']);
         $user = $wager->getUser(6);
         $sdata = $newsletter->generateWagerNewsletterContent($row, $user, $row['SEASON_ID'], $lang_id);
         $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
         $tpl->setTemplateFile('../tpl/email_newsletter_wager_html.tpl.html');
       } else if ($row['TYPE'] == 3) {
         $wager = new Wager($row['SEASON_ID']);
         $user = $wager->getUser(6);
         $sdata = $newsletter->generateArrangerNewsletterContent($row, $user, $row['SEASON_ID'], $lang_id);
         $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
         $tpl->setTemplateFile('../tpl/email_newsletter_arranger_html.tpl.html');
       }

     }

// close connections
include('../class/db_close.inc.php');
?>
