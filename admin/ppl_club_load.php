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

    if ((count($fields) >= 3 && $_POST['sport_id'] != 4)
         || (count($fields) == 4 && $_POST['sport_id'] == 4)) {
      unset($sdata);
      $s_fields = '';
      $i_fields = array('sport_id');
      $d_fields = '';
      $c_fields = array('male');
    
      $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
      if ($_POST['sport_id'] != 4) {
        $sdata['first_name']="'".trim($fields[3])."'";
        $sdata['last_name']="'".trim($fields[4])."'";
        $country="'".trim($fields[1])."'";
        $number = $fields[0];
        $position = $fields[2];
        $date_from = $fields[5];
        $date_to = $fields[6];
      } else {
        $sdata['first_name']="'".trim($fields[1])."'";
        $sdata['last_name']="'".trim($fields[0])."'";
        $date_from = $fields[2];
        $date_to = $fields[3];
	$country = '';
      }
      $sdata['publish']="'Y'";
$db->showquery=true;
      // check if user exist & is member
      $conflict = false;
      if ($country != "''") {
        $country_set = true;         
        $sql = "SELECT * from busers, countries where soundex(first_name)=soundex(".$sdata['first_name'].") 
						and soundex(last_name)=soundex(".$sdata['last_name'].")
						and CITIZENSHIP = id
						and sport_id=".$_POST['sport_id']."
						and latin_name=".$country;
        $db->query($sql); 
        if ($row= $db->nextRow()) {
          similar_text($row['LAST_NAME'], $sdata['last_name'], $percent);
          if ($percent > 60) {
            $data['ERROR'] .= "Line ".$i.": User ". $sdata['first_name']." ".$sdata['last_name']. " already exists, attempt to add membership. ".count($fields)."<br>";
      	    $user_id = $row['USER_ID'];
	    $country = $row['ID'];
            $conflict = true;
          } 
        } else {
          $sql = "SELECT * from busers, countries where soundex(last_name)=soundex(".$sdata['last_name'].")
						and CITIZENSHIP = id
						and sport_id=".$_POST['sport_id']."
						and latin_name=".$country;
          $db->query($sql); 
          if ($row= $db->nextRow()) {
            similar_text($row['last_name'], $sdata['last_name'], $percent);
            if ($percent > 60) {
              $data['ERROR'] .= "Line ".$i.": Possible conflict detected: ". $fields[3]." ".$fields[4]. " <br>";
              $error .= $strings[$i]."\n"; 
              $rowwrong++;
              $user_id=-1; 
  	    $conflict = true;
            }
          } else {
            $sql = "SELECT * from busers where soundex(first_name)=soundex(".$sdata['first_name'].") 
  						and soundex(last_name)=soundex(".$sdata['last_name'].")
						and sport_id=".$_POST['sport_id']."
  						and (CONVERT(citizenship, SIGNED INTEGER) = 0 OR 
  						     CONVERT(citizenship, SIGNED INTEGER) is NULL)";
            $db->query($sql);   
            if ($row= $db->nextRow()) {
            similar_text($row['LAST_NAME'], $sdata['last_name'], $percent);
              if ($percent > 60) {
                $data['ERROR'] .= "Line ".$i.": User ". $fields[3]." ".$fields[4]. " already exists, attempt to add membership. ".count($fields)."<br>";
       	        $user_id = $row['USER_ID'];
    	        $country = $row['ID'];
                $conflict = true;
  	        $country_set = false;
              }
            }
          }
        }
      } else {
        $country_set = false;         
        $sql = "SELECT * from busers where soundex(first_name)=soundex(".$sdata['first_name'].") 
						and soundex(last_name)=soundex(".$sdata['last_name'].")
						and length(last_name) = length(".$sdata['last_name'].")
						and sport_id=".$_POST['sport_id'];
        $db->query($sql); 
        if ($row= $db->nextRow()) {
          similar_text($row['LAST_NAME'], $sdata['last_name'], $percent);
          if ($percent > 60) {
            $data['ERROR'] .= "Line ".$i.": User ". $sdata['first_name']." ".$sdata['last_name']. " already exists, attempt to add membership. ".count($fields)."<br>";
    	    $user_id = $row['USER_ID'];
	    $country = $row['ID'];
            $conflict = true;
          } 
        }
      }
      if (!$conflict) {
        $sql = "SELECT * from countries where latin_name=".$country;
        $db->query($sql); 
        if ($row= $db->nextRow()) {
  	  $sdata['CITIZENSHIP'] = $row['ID'];
        }       
        $db->insert('busers', $sdata); 
        $user_id = $db->id();
      } else if (!$country_set) {
        unset($sdata);
        $sql = "SELECT * from countries where latin_name=".$country;
        $db->query($sql); 
        if ($row= $db->nextRow()) {
  	  $sdata['CITIZENSHIP'] = $row['ID'];
        }       
        $db->update('busers', $sdata, "USER_ID=".$user_id); 
      }

      if ($user_id > 0) {
        unset($sdata);
	$sdata['USER_ID'] = $user_id;
	$sdata['TEAM_ID'] = $_POST['team_id'];
	$sdata['USER_TYPE'] = 40;
	$sdata['DATE_STARTED'] = "DATE_FORMAT('".trim($date_from)."', '%Y-%m-%d')";
	$sdata['DATE_EXPIRED'] = "DATE_FORMAT('".trim($date_to)."', '%Y-%m-%d')";
        if (isset($position))
  	  $sdata['POSITION_ID1'] = $position;
        if (isset($number) && trim($number) != "")
          $sdata['NUM'] = trim($number);
        $db->insert('members', $sdata);
        $rowcorr++;
      } else if ($user_id==0){
        $data['ERROR'] .= "Line ".$i.": Wrong data. ".count($fields)."<br>";
        $error .= $strings[$i]."\n"; 
        $rowwrong++;
      }    
    } else if (trim($fields[0]) != "") {
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
$tpl->setTemplateFile('../tpl/adm/ppl_club_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');

?>