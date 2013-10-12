<?php
/*
===============================================================================
run_line.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit running line

TABLES USED: 

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

ob_start();

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

if (empty($_SESSION["_admin"][MENU_GAMES]) || strcmp($_SESSION["_admin"][MENU_GAMES], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$PRESET_VARS['publish'] = 'Y';
$data['SEASON_ID'] = inputSeasons('season_id');

$db->showquery=true;

$data['ERROR'] = '';
$data['OUTCOME'] = '';
$error = '';
if (isset($_POST['descr']) && isset($_POST['season_id'])) {
//variables
//  echo $season_id;
//  print_r($team_id);
  $strings = explode("\n", $_POST['descr']);

  $rowcorr =0;
  $rowwrong =0;
  $length= count($strings); 
  for ($i=0; $i<$length; $i++)
   {
    $fields=explode(",", $strings[$i]);

    if (count($fields) >= 2) {
//echo ".".trim($fields[1]).".".".".trim($fields[2]).".";
      unset($sdata);
      $start_date=trim($fields[0]);
      $s_fields = array('title');;
      $i_fields = array('season_id');
      $d_fields = array('start_date');
      $c_fields = array('publish');
    
      $_POST['title'] = trim($fields[1]);
      $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
      $sdata['start_date']="DATE_FORMAT('".trim($fields[0])."', '%Y-%m-%d %H:%i')";

      $db->insert('games_races', $sdata);
      $rowcorr++;
    }
    else if (trim($fields[0]) != "") {
      $data['ERROR'] .= "Line ".$i.": Wrong number of fields. ".count($fields)."<br>";
      $error .= $strings[$i]."\n"; 
      $rowwrong++;
     }  
   }
   $data['OUTCOME'] .= "Saved lines: ".$rowcorr."<br>";
   $data['OUTCOME'] .= "Rejected lines: ".$rowwrong."<br>";

  $PRESET_VARS['descr'] = $error;
  $data['descr'] = $error;
//  header('Location: sched_load.php');

}

$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/sched_races_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>