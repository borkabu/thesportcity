<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
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

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $bracket_user = new BracketUser($bracket->tseason_id);

  $data['BRACKET_FILTER_BOX'] = $bracketbox->getBracketFilterBox($bracket->tseason_id);

$tpl->setCacheLevel(TPL_CACHE_NOTHING);

 $has_league = false;
 $league_id = -1;
 $sql= "SELECT ML.LEAGUE_ID
          FROM bracket_leagues ML
         WHERE ML.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$bracket->tseason_id; 
 $db->query($sql); 
 if ($row = $db->nextRow()) {
//   $has_league = true;
   $league_id = $row['LEAGUE_ID'];
 }

if ($auth->userOn() && $league_id > 0 && isset($_POST['remove_user']) && isset($_POST['user_id'])) {
  $udata['STATUS'] = 4;  
  $udata['END_DATE'] = "NOW()";
  $db->update('bracket_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['remove_user2']) && isset($_POST['user_id'])) {
  $db->delete('bracket_leagues_members', 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['cancel_all_invites'])) {
  $db->delete('bracket_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['tag_user']) && isset($_POST['user_id']) && isset($_POST['tag'])) {
  $udata['TAG'] = "'".$_POST['tag']."'";  
  $db->update('bracket_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['set_rules'])) {
  $s_fields = array('rules');
  $d_fields = '';
  $c_fields = array('recruitment_active', 'accept_newbies', 'real_prizes');
  $i_fields = array('entry_fee', 'country', 'participants', 'type');
 
  $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

  $db->update('bracket_leagues', $udata, 'LEAGUE_ID='.$league_id);  
  unset($udata);
}

if ($auth->userOn() && isset($_POST['create_league']) && isset($_POST['title']) && !empty($_POST['title'])) {
   $sql= "SELECT ML.LEAGUE_ID
          FROM bracket_leagues ML, bracket_leagues_members MLM
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$bracket->tseason_id." 
               AND MLM.STATUS=1"; 
   $db->query($sql); 
   if (!$row = $db->nextRow()) {    
     $sdata['SEASON_ID'] = $bracket->tseason_id;
     $sdata['TITLE'] = "'".$_POST['title']."'";
     $sdata['USER_ID'] = $auth->getUserId();
     $db->insert('bracket_leagues', $sdata);
     unset($sdata);
     $db->select("bracket_leagues", "LEAGUE_ID", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$bracket->tseason_id);   
     if ($row = $db->nextRow()) {
       $sdata['LEAGUE_ID'] = $row['LEAGUE_ID'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 1;
       $db->insert('bracket_leagues_members', $sdata);     
       unset($sdata);
     }
   }
}

if ($auth->userOn() && $league_id > 0 && isset($_POST['invite']) && isset($_POST['user_name']) && isset($_POST['tseason_id'])) {
   $sql = "SELECT U.USER_ID, MU.USER_ID ISIN, MU.IGNORE_LEAGUES 
             FROM users U LEFT JOIN bracket_users MU ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$_POST['tseason_id']."
            WHERE USER_NAME='".$_POST['user_name']."'";
  
   $db->query($sql);
   if ($row = $db->nextRow()) {
     if (empty($row['IGNORE_LEAGUES'])) {
       $data['LOGGED'][0]['INVITE_FORM'][0]['INVITE_ERROR'][0]['NOTEAM'][0]['X'] = 1;
     }
     else if ($row['IGNORE_LEAGUES'] == 'Y') {
       $data['LOGGED'][0]['INVITE_FORM'][0]['INVITE_ERROR'][0]['USERIGNORE'][0]['X'] = 1;
     } 
     else if (!empty($row['ISIN']) && $row['ISIN'] != "") {
       $sdata['LEAGUE_ID'] = $league_id;
       $sdata['USER_ID'] = $row['USER_ID'];
       $sdata['STATUS'] = 3;
       // check that it is not already there
       $db->select("bracket_leagues_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$league_id);       
       if ($row = $db->nextRow()) {
         $data['LOGGED'][0]['INVITE_FORM'][0]['INVITE_ERROR'][0]['USERDOUBLE'][0]['X'] = 1;
       } 
       else {
         $db->select("bracket_leagues_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$league_id);
         if ($row = $db->nextRow()) {
           $db->update('bracket_leagues_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND LEAGUE_ID='.$league_id);
         }
         else $db->insert('bracket_leagues_members', $sdata);     
         unset($sdata);
       }
     }
     else {
      $data['LOGGED'][0]['INVITE_FORM'][0]['INVITE_ERROR'][0]['NOTEAM'][0]['X'] = 1;
     }
   }
   else {
      $data['LOGGED'][0]['INVITE_FORM'][0]['INVITE_ERROR'][0]['NOUSER'][0]['X'] = 1;
   }
}


if ($auth->userOn()) {
// initialize user team
 $sql= "SELECT ML.LEAGUE_ID, ML.TITLE, ML.RULES, U.USER_NAME, U.USER_ID, 
		ML.ENTRY_FEE, ML.COUNTRY, ML.PARTICIPANTS, ML.TYPE, 
		ML.RECRUITMENT_ACTIVE, ML.ACCEPT_NEWBIES, ML.REAL_PRIZES
          FROM bracket_leagues ML, bracket_leagues_members MLM,
               users U
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
	       AND MLM.USER_ID=U.USER_ID
               AND ML.SEASON_ID=".$bracket->tseason_id." 
               AND MLM.STATUS=1"; 
 $db->query($sql); 
 if (!$row = $db->nextRow()) {
   $data['CREATE_LEAGUE_OFFER'][0]['X'] = 1;
 }
 else $has_league = true;

if ($has_league) {
  $data['LOGGED'][0]['TITLE'] = $row['TITLE'];
  $data['LOGGED'][0]['OWNER'] = $row['USER_NAME'];
  $data['LOGGED'][0]['LEAGUE_ID'] = $row['LEAGUE_ID'];
  $data['LOGGED'][0]['RULES'] = $row['RULES'];
 //echo $row['RULES'];
  $data['LOGGED'][0]['DESCR'] = $row['RULES'];
  $data['LOGGED'][0]['ENTRY_FEE'] = $row['ENTRY_FEE'];
  $data['LOGGED'][0]['PARTICIPANTS'] = $row['PARTICIPANTS'];
  $PRESET_VARS['rules'] = $row['RULES'];
  $data['LOGGED'][0]['COUNTRY'] = inputCountries('country', $row['COUNTRY']);

  foreach ($row as $key => $val) {
    $PRESET_VARS[strtolower($key)] = $val;
  }

//print_r($PRESET_VARS);
  $opt['class'] = 'input';
  $opt['options'] = $private_league_type;
  $type = $row['TYPE'];
  $data['LOGGED'][0]['TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'type', $type, $opt, $type);

  $league_id=$row['LEAGUE_ID'];
  $owner = true;
  $db->free();
  // get members
  $sql= "SELECT MLM.*, U.USER_NAME, MS.POINTS, MS.PLACE
          FROM bracket_leagues_members MLM, users U
               LEFT JOIN bracket_standings MS ON MS.USER_ID=U.USER_ID AND MS.MSEASON_ID=".$bracket->tseason_id."
         WHERE MLM.LEAGUE_ID=".$league_id."
               AND MLM.USER_ID=U.USER_ID"; 
  $db->query($sql); 
  $c = 0;
  while ($row = $db->nextRow()) {
   if ($row['STATUS'] ==1) {
     $data['LOGGED'][0]['LEAGUE'][0]['OWNER'][$c] = $row;
   }
   else if ($row['STATUS'] == 2) {
     $data['LOGGED'][0]['LEAGUE'][0]['CURRENT_MEMBERS'][$c] = $row;
   }
   else if ($row['STATUS'] == 3) {
     $data['LOGGED'][0]['LEAGUE'][0]['INVITED_MEMBERS'][$c] = $row;
   }
   else if ($row['STATUS'] == 4) {
     $data['LOGGED'][0]['LEAGUE'][0]['FORMER_MEMBERS'][$c] = $row;
   }
   else if ($row['STATUS'] == 5) {
     $data['LOGGED'][0]['LEAGUE'][0]['DECLINE_MEMBERS'][$c] = $row;
   }

   $c++;
  }
  $db->free();

  if ($owner) {
    // create invitation form
    $data['LOGGED'][0]['INVITE_FORM'][0]['LEAGUE_ID'] = $league_id;
    $data['LOGGED'][0]['INVITE_FORM'][0]['SEASON_ID'] = $bracket->tseason_id;
    $data['LOGGED'][0]['INVITE_FORM'][0]['OWNER'] = $auth->getUserId();
  }
 }
} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
}

$tpl->setTemplateFile('tpl/bracket_league_control.tpl.html');
$tpl->addData($data);

$content .= $tpl->parse();
// ----------------------------------------------------------------------------

  define("ARRANGER", 1);
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');
?>