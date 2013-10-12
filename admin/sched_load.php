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

$data['ERROR'] = '';
$data['OUTCOME'] = '';
$error = '';
if (isset($_POST['descr']) && isset($_POST['season_id']))
{
//variables
//  echo $season_id;
  $sql = 'SELECT S.TEAM_ID, T.TEAM_NAME, T.CITY
          FROM team_seasons S, teams T 
          WHERE S.SEASON_ID='.$_POST['season_id'].'
            AND S.TEAM_ID=T.TEAM_ID
          ORDER BY TEAM_NAME';             
echo $sql;
  
  $db->query($sql);
  $c=0;
  $team_names[0] = '';
  while ($row = $db->nextRow()) {
   $team_names[$c] .= $row['TEAM_NAME'];
   $team_id[$c] = $row['TEAM_ID'];
   $c++;    
   $team_names[$c] = '';
  }
  print_r($team_names);
//  print_r($team_id);
  $strings = explode("\n", $_POST['descr']);

  $rowcorr =0;
  $rowwrong =0;
  $length= count($strings); 
  for ($i=0; $i<$length; $i++)
   {
    $fields=explode(",", $strings[$i]);

    if (count($fields) >= 3)
     {
//echo ".".trim($fields[1]).".".".".trim($fields[2]).".";
      $team_id1 = teaminseason(trim($fields[1]), $team_names, $team_id);
      $team_id2 = teaminseason(trim($fields[2]), $team_names, $team_id);
//      echo $team_id1;
//      echo $team_id2;
      if ($team_id1 && $team_id2)
       {
        $sql="SELECT * FROM games
              WHERE START_DATE=START_DATE=DATE_FORMAT('".trim($fields[0])."', '%Y-%m-%d %H:%i') 
                  AND TEAM_ID1=".$team_id1." 
                  AND TEAM_ID2=".$team_id2;
//        $db->select("games", "*", "START_DATE='".$fields[0]."' AND TEAM_ID1=".$team_id1." AND TEAM_ID2=".$team_id2." AND LOCATION='".$fields[3]."'");     
//        echo $sql;
        $db->query($sql);
        if (!$row = $db->nextRow()) {
          unset($sdata);
          $start_date=trim($fields[0]);
          $s_fields = '';
          $i_fields = array('season_id', 'team_id1', 'team_id2');
          $d_fields = array('start_date');
          $c_fields = array('publish');
    
          $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
          $sdata['start_date']="DATE_FORMAT('".trim($fields[0])."', '%Y-%m-%d %H:%i')";
          $sdata['team_id1']=$team_id1;
          $sdata['team_id2']=$team_id2;

          $db->insert('games', $sdata);
          $rowcorr++;
        }
       else  
        {
         $data['ERROR'] .= "Line ".$i.": Double entry: ".$fields[0]." ".$fields[1]." ".$fields[2]."<br>"; 
         $error .= $strings[$i]."\n"; 
         $rowwrong++;
        }
       }
      else  
       {
        $data['ERROR'] .= "Line ".$i.": Wrong teams: ".$fields[1]." ".$fields[2]."<br>";
        $error .= $strings[$i]."\n"; 
        $rowwrong++;
       }
     }
    else if (trim($fields[0]) != "")
     {
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
$tpl->setTemplateFile('../tpl/adm/sched_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');

function teaminseason($team, $team_names, $team_id) {
  for ($i=0; $i<count($team_names); $i++)
   {
     if (strtolower($team_names[$i]) == strtolower($team))
     { 
      return $team_id[$i];
     }
//    echo strtolower($team_names[$i]).strtolower($team);
   }
 return 0;
}

?>