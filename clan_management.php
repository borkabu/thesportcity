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
include('class/manager.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);
//$db->showquery=true;
if ($auth->userOn()) {
  if (!$auth->isClanMember() && isset($_POST['create_clan']) 
	&& !empty($_POST['clan_name']) && $auth->getCredits() >= 50) {
    $s_fields = array('clan_name');
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $sdata['MEMBERS'] = 1;
    $sdata['USER_ID'] = $auth->getUserId();
    $db->insert('clans', $sdata);
    $clan_id = $db->id();
    unset($sdata);
    $sdata['CLAN_ID'] = $clan_id;
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['STATUS'] = 1;
    $sdata['LEVEL'] = 3;
    $sdata['DATE_JOINED'] = "NOW()";
    $db->insert('clan_members', $sdata);    
    $clan_log = new ClanLog();
    $clan_log->logEvent ($clan_id, 1, 0, $auth->getUserId());
    $clan_user_log = new ClanUserLog();
    $clan_user_log->logEvent ($auth->getUserId(), 1, $clan_id);

    $credits = new Credits();
    $credits->updateCredits($auth->getUserId(), -50);
//    $credits->updateClanCredits($clan_id, 45);
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($auth->getUserId(), 25, 50);

    // remove all invitation
    $db->delete('clan_members', "USER_ID=".$auth->getUserId()." AND STATUS=3");    
    unset($sdata);
    unset($tdata);
    $tdata['TYPE'] = 1;
    $tdata['GROUP_MEMBERS'] = 1;
    $db->insert('forum_groups', $tdata);

    $group_id = $db->id();

    $vars['group_name'] = $_POST['clan_name'];
    // create clan forum group
    $s_fields = array('group_name');
    $d_fields = '';
    $c_fields = '';
    $i_fields = array('lang_id');
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $vars);
    $sdata['group_id'] = $group_id;
    $db->query("SELECT ID AS LANG_ID, I18N FROM languages");
    $dblangs = array();
    while ($row = $db->nextRow()) {
      $dblangs[] = $row['LANG_ID'];
    }
    foreach ($dblangs as $dblang) {
      $sdata['lang_id'] = $dblang;
      $db->insert('forum_groups_details',$sdata);
    }

    unset($sdata);
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['GROUP_ID'] = $group_id;
    $sdata['LEVEL'] = 3;
    $sdata['DATE_JOINED'] = "NOW()";
    $db->insert('forum_groups_members',$sdata);

    // create clan private forum

    $vars['cat_id'] = 13;
    $vars['forum_name'] = $_POST['clan_name'];
    $vars['group_id'] = $group_id;
    $s_fields = '';
    $d_fields = '';
    $c_fields = '';
    $i_fields = array('cat_id', 'group_id');
  
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields,  $vars);

    // create clan private forum
    $sdata['PUBLISH'] = "'Y'";
    $db->insert('forum', $sdata);
    $forum_id = $db->id();
    unset($sdata);
    $sdata['FORUM_ID'] = $forum_id;
    $db->update("clans", $sdata, "CLAN_ID=".$clan_id);      

    $s_fields_d = array('forum_name');
    $d_fields_d = '';
    $c_fields_d = '';
    $i_fields_d = array('lang_id');

    foreach ($dblangs as $dblang) {
      $vars['lang_id'] = $dblang;
      $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $vars);
      $tdata['forum_id'] = $forum_id;
      $db->insert('forum_details', $tdata);
    }
    header('Location: clan_management.php');
    exit;
  }

  $lclanid = $auth->isClanLeader();

  if (isset($_POST['clan_id']) && $lclanid == $_POST['clan_id'] && isset($_POST['create_clan_team']) 
	&& !empty($_POST['team_name'])) {
    $clan = new Clan($lclanid);
    $clan->getClanData();
    $sql="SELECT * FROM clan_teams CT 
		WHERE CT.clan_id=".$_POST['clan_id']."
			AND CT.SEASON_ID=".$_POST['season_id'];
    $db->query($sql);   
    if ($row = $db->nextRow()) {
    } else {
      if ($clan->clan_data['CLAN_FUND'] >= 25) {
        unset($sdata);
        $s_fields = array('team_name');
        $i_fields = array('season_id', 'clan_id', 'event_type');
        $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
        $sdata['DATE_CREATED'] = "NOW()";
        $db->insert('clan_teams', $sdata);
        $team_id = $db->id();
        $clan_log = new ClanLog();
        $clan_log->logEvent ($_POST['clan_id'], 4, 25, '', $team_id);
  
        $credits = new Credits();
        $credits->updateClanCredits($_POST['clan_id'], -25);
      }
    }
    header('Location: clan_management.php');
    exit;
  }

  if (isset($_POST['save_team'])) {
    $clan = new Clan($lclanid);
    $clan->getClanData();
    $clan->saveTeam();
  }

  if ($auth->userOn() && isset($_POST['set_info']) && isset($_POST['clan_id']) && $lclanid == $_POST['clan_id']) {
      $s_fields = array('descr');
      $d_fields = '';
      $c_fields = '';
      $i_fields = '';
 
      $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

      $db->update('clans', $udata, 'CLAN_ID='.$_POST['clan_id']);  
      unset($udata);
  }

  if ($auth->userOn() && isset($_POST['invite']) && isset($_POST['user_name']) 
	&& isset($_POST['clan_id']) && $lclanid == $_POST['clan_id']) {
    $clan = new Clan($lclanid);
    $clan->getClanMembers();
    if (count($clan->clan_members) < 15) {

      $sql = "SELECT U.USER_ID, MU.USER_ID ISIN
              FROM users U LEFT JOIN clan_members MU ON U.USER_ID=MU.USER_ID AND MU.CLAN_ID=".$_POST['clan_id']."
             WHERE USER_NAME='".$_POST['user_name']."'";
   
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $sdata['CLAN_ID'] = $_POST['clan_id'];
        $sdata['USER_ID'] = $row['USER_ID'];
        $sdata['STATUS'] = 3;
        // check that it is not already there
        $db->select("clan_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND CLAN_ID=".$sdata['CLAN_ID']);       
        if ($row = $db->nextRow()) {
          $invite['INVITE_ERROR']['USERDOUBLE'] = 1;
        } 
        else {
          $db->select("clan_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND CLAN_ID=".$sdata['CLAN_ID']);       
          if ($row = $db->nextRow()) {
            $db->update('clan_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND CLAN_ID='.$sdata['CLAN_ID']);
          }
          else {
            $db->select("clan_members", "USER_ID", "STATUS IN (1, 2) AND USER_ID='".$sdata['USER_ID']."' AND CLAN_ID <>".$sdata['CLAN_ID']);       
            if ($row = $db->nextRow()) {
              $invite['INVITE_ERROR']['USERBELONG'] = 1;
            }
            else $db->insert('clan_members', $sdata);     
          }
          unset($sdata);
        }
      }
      else {
         $invite['INVITE_ERROR']['NOUSER'] = 1;
      }
    } else {
      $invite['INVITE_ERROR']['TOOMANY'] = 1;
    }
  }

  if ($auth->userOn() && $lclanid > 0 && isset($_POST['remove_user2']) && isset($_POST['user_id'])) {
    $db->delete('clan_members', 'USER_ID='.$_POST['user_id'].' AND CLAN_ID='.$lclanid." AND STATUS=3");  
  }


  if ($lclanid > 0) {
    // add member  
    $user = new User($auth->getUserId());
    $data['CLAN_MEMBERS'] = '';

    $clan = new Clan($lclanid);
    $data['CLAN'] = $clan->getClanData();
    $clan->getClanMembers();
//print_r($data['CLAN']);

//$db->showquery=true;
    if (isset($_POST['remove_user']) 
	&& isset($clan->clan_members[$_POST['user_id']]) 
	&& $clan->clan_members[$_POST['user_id']]['TEAMS'] == 0) {
      $clan->removeMember($_POST['user_id']);
    }

    if (isset($_POST['award_user'])) {
      $clan->awardMember($_POST['user_id'], $_POST['credits']);
      $data['CLAN'] = $clan->getClanData();
    }


    if (isset($_POST['add_member'])) {
      $new_user = new User();
      if ($new_user->getUserIdFromUsername($_POST['user_name']) > 0)
        $clan->addNewMember($new_user->user_id);
      else {
	  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_NO_USER');
      }
    }

    $data['CLAN_MEMBERS'] = $clan->getClanMembers();
    $data['CLAN_TEAMS'] = $clan->getClanTeams();
    if (count($clan->clan_members) < 15) {
      $invite['CLAN_ID'] = $clan->clan_id;
      $invite['OWNER'] = $auth->getUserId();
      $smarty->assign("invite_form", $invite);
    } else {
      $smarty->assign("invite_form_limit", 1);
    }
    
  } else if ($auth->isClanMember() && $lclanid == 0) {
//       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
//       $errorbox1 = $errorbox->getErrorBox('ERROR_CANNOT_CREATE_CLAN');
    $error['MSG'] = $langs['LANG_ERROR_CANNOT_CREATE_CLAN_MEMBER_ALREADY_U'];

  } else if (!$auth->isClanMember()) {
    // can create clan
    if ($auth->getCredits() >= 50)
      $create_clan_offer = 1;
    else {
      $error['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
    }
    $allow_create_clan = 1;
  } 
  if (isset($error))
    $smarty->assign("error", $error);

  if (isset($create_clan_offer))
    $smarty->assign("create_clan_offer", $create_clan_offer);

  if (isset($allow_create_clan))
    $smarty->assign("allow_create_clan", $allow_create_clan);

  if ($lclanid > 0) {
    $smarty->assign("clan_item", $data['CLAN']);
    $smarty->assign("clan_members", $data['CLAN_MEMBERS']);
    $smarty->assign("clan_teams", $data['CLAN_TEAMS']);
  }


  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/clan_management.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/clan_management.smarty'.($stop-$start);

}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
  define("CLANS", 1);
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>
