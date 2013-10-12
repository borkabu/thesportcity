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
if (isset($_POST['descr']))
{
//  print_r($team_id);
  $strings = explode("\n", $_POST['descr']);

  $rowcorr =0;
  $rowwrong =0;
  $length= count($strings); 
  for ($i=0; $i<$length; $i++) {
    $fields=explode(",", $strings[$i]);

    if (count($fields) >= 3) {
          unset($sdata);
          $s_fields = '';
          $i_fields = array('sport_id', 'citizenship');
          $d_fields = array('birth_date');
          $c_fields = array('male');
    
          $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
	  if (trim($fields[2]) != "")
            $sdata['birth_date']="DATE_FORMAT('".trim($fields[2])."', '%Y-%m-%d')";
          $fields[0] = str_replace("'", "''", $fields[0]);
          $fields[1] = str_replace("'", "''", $fields[1]);
          $sdata['first_name']="'".trim($fields[0])."'";
          $sdata['last_name']="'".trim($fields[1])."'";
          $sdata['publish']="'Y'";
          $sdata['citizenship']=$_POST['citizenship'];
$db->showquery=true;
    
          $conflict = false;
          if (trim($fields[2]) != "")
            $sql = "SELECT * from busers where soundex(first_name)=soundex(".$sdata['first_name'].") 
						and soundex(last_name)=soundex(".$sdata['last_name'].") 
				AND birth_date=".$sdata['birth_date'] ;
          else 
            $sql = "SELECT * from busers where soundex(first_name)=soundex(".$sdata['first_name'].") 
						and soundex(last_name)=soundex(".$sdata['last_name'].") 
					        and ((CITIZENSHIP = ".$_POST['citizenship']." OR CITIZENSHIP IS NULL) 
							OR NOT (CITIZENSHIP REGEXP '^-?[0-9]+$'))";
          $db->query($sql); 
          if ($row= $db->nextRow()) {
            similar_text($row['LAST_NAME'], $sdata['last_name'], $percent);
            if ($percent > 60) {
              $data['ERROR'] .= "Line ".$i.": User ". $fields[0]." ".$fields[1]. " already exists, attempt to add membership. ".count($fields)."<br>";
      	      $user_id = $row['USER_ID'];
              $conflict = true;
            } 
          } else {
            if (trim($fields[2]) != "")
              $sql = "SELECT * from busers where soundex(last_name)=soundex(".$sdata['last_name'].") 
						AND birth_date=".$sdata['birth_date'] ;
	    else 
              $sql = "SELECT * from busers where soundex(last_name)=soundex(".$sdata['last_name'].")
					        and ((CITIZENSHIP = ".$_POST['citizenship']." OR CITIZENSHIP IS NULL) 
							OR NOT (CITIZENSHIP REGEXP '^-?[0-9]+$'))";
            $db->query($sql); 
            if ($row= $db->nextRow()) {
              similar_text($row['last_name'], $sdata['last_name'], $percent);
              if ($percent > 60) {
                $data['ERROR'] .= "Line ".$i.": Possible conflict detected: ". $fields[0]." ".$fields[1]. " <br>";
                $error .= $strings[$i]."\n"; 
                $rowwrong++;
                $user_id=-1; 
  	        $conflict = true;
              }
            } 
          }

          if (!$conflict) {
            $db->insert('busers', $sdata); 
            $user_id = $db->id();
          }
          if ($user_id > 0) {
            unset($sdata);
	    $sdata['USER_ID'] = $user_id;
	    $sdata['TEAM_ID'] = $_POST['team_id'];
	    $sdata['USER_TYPE'] = 40;
	    $sdata['DATE_STARTED'] = "DATE_FORMAT('".trim($fields[3])."', '%Y-%m-%d')";
	    $sdata['DATE_EXPIRED'] = "DATE_FORMAT('".trim($fields[4])."', '%Y-%m-%d')";
	    $positions=explode("/", $fields[5]);
	    $sdata['POSITION_ID1'] = $positions[0];
            if (count($positions) == 2) 
  	      $sdata['POSITION_ID2'] = $positions[1];
            if (isset($fields[6]) && trim($fields[6]) != "")
  	      $sdata['NUM'] = trim($fields[6]);
            $db->insert('members', $sdata);
            $rowcorr++;
          } else {
            $data['ERROR'] .= "Line ".$i.": Wrong data. ".count($fields)."<br>";
            $error .= $strings[$i]."\n"; 
            $rowwrong++;
          }    
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

}

$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['CITIZENSHIP']=inputCountries('citizenship');
$data['SPORT_ID'] = inputManagerSportTypes('sport_id');
$data['TEAM_ID'] = inputTeams('team_id');    

$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ppl_nt_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');

?>