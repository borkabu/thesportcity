<?php
/*
===============================================================================
thanks.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows thank you message

TABLES USED: 
  - none

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');

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
include('class/inputs.inc.php');
// extras
include('class/ss_conf.inc.php');
include('ss_include.php');

// --- build content data -----------------------------------------------------

// $db->showquery = true;
 $content = $menu->getSubmenu('ss_outside.php');
 $utils->setLocation(SS_GYM);

 if (isset($_POST['train']) && isset($_POST['attr_id'])) {
   $sql = "SELECT *, (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(LAST_SKILL_TRAINED))/(60*60*24) CAN_TRAIN
             FROM ss_skills SD 
                  LEFT JOIN ss_users_da SUD ON 
                       SD.ATTR_ID = SUD.ATTR_ID
                       AND SUD.USER_ID=".$auth->getUserId()."
                  LEFT JOIN ss_users SU ON SUD.USER_ID=SU.USER_ID
            WHERE SD.ATTR_ID=".$_POST['attr_id'];
   $db->query($sql);

   if ($row = $db->nextRow()) {
    if ($row['LEVEL'] > 0 && 
        $row['CAN_TRAIN'] < 1) {
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 31;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['QUANTITY'] = 1;
        $sdata['STATUS'] = 1;
        $sdata['MONEY'] = $row['PRICE']*($row['LEVEL']+1);
        $sdata['LEVEL'] = $row['LEVEL']+1;
        $sdata['SKILL'] = $_POST['attr_id'];
        $db->insert("ss_users_log", $sdata);   
      // log failure

    } else if ($row['LEVEL'] < $row['LEVELS'])
      if ($_SESSION['_user']['SS'][0]['MONEY'] > $row['PRICE']*($row['LEVEL']+1)) {
        unset($sdata);
        $sdata['LEVEL'] = $row['LEVEL']+1;
        $sdata['DATE_TRAINED'] = "NOW()";
        if ($row['LEVEL'] > 0) {
          $db->update("ss_users_da", $sdata, "USER_ID=".$auth->getUserId()." AND ATTR_ID=".$_POST['attr_id']);
        } 
        else {
          $sdata['ATTR_ID'] = $_POST['attr_id'];
          $sdata['USER_ID'] = $auth->getUserId();  
          $db->insert("ss_users_da", $sdata);
        }
        unset($sdata);
        $sdata['LAST_SKILL_TRAINED'] = "NOW()";
        $db->update("ss_users", $sdata, "USER_ID=".$auth->getUserId());

        unset($sdata);
        $sdata['MONEY'] = $_SESSION['_user']['SS'][0]['MONEY'] - $row['PRICE']*($row['LEVEL']+1);
        $_SESSION['_user']['SS'][0]['MONEY'] = $_SESSION['_user']['SS'][0]['MONEY'] - $row['PRICE']*($row['LEVEL']+1);
        $db->update("ss_users", $sdata, "USER_ID=".$auth->getUserId());   
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 3;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['QUANTITY'] = 1;
        $sdata['STATUS'] = 0;
        $sdata['MONEY'] = $row['PRICE']*($row['LEVEL']+1);
        $sdata['LEVEL'] = $row['LEVEL']+1;
        $sdata['SKILL'] = $_POST['attr_id'];
        $db->insert("ss_users_log", $sdata);   

// log operation success
     } else {
// log operation failure
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 3;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['QUANTITY'] = 1;
        $sdata['STATUS'] = 1;
        $sdata['MONEY'] = $row['PRICE']*($row['LEVEL']+1);
        $sdata['LEVEL'] = $row['LEVEL']+1;
        $sdata['SKILL'] = $_POST['attr_id'];
        $db->insert("ss_users_log", $sdata);   
     }     
   }
//   header("location: ".$_SERVER["REQUEST_URI"]);
 } 

 if (isset($_GET['sport_id'])) {
   $sql = "SELECT SD.*, SUD.LEVEL, (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(LAST_SKILL_TRAINED))/(60*60*24) CAN_TRAIN, ST.DESCR, ST.ATTR_NAME
             FROM ss_skills SD 
                  LEFT JOIN ss_skills_details ST ON ST.ATTR_ID=SD.ATTR_ID 
                                    AND ST.LANG_ID=".$_SESSION['lang_id']."
                  LEFT JOIN ss_users_da SUD ON SD.ATTR_ID = SUD.ATTR_ID
                                    AND SUD.USER_ID=".$auth->getUserId()."
                  LEFT JOIN ss_users SU ON SUD.USER_ID=SU.USER_ID
            WHERE SD.SPORT_ID=".$_GET['sport_id'];

   $db->query($sql);
   $c = 0;
   while ($row = $db->nextRow()) {
     $data['SKILLS'][0]['ITEMS'][$c] = $row;
     $data['SKILLS'][0]['ITEMS'][$c]['PRICE'] = $row['PRICE']*($row['LEVEL']+1);
     if ($row['LEVEL'] > 0 && $row['CAN_TRAIN'] < 1) {
       $data['SKILLS'][0]['ITEMS'][$c]['TOO_SOON'][0]['X'] = 1;
       if ($row['LEVEL'] < $row['LEVELS'])
         $data['SKILLS'][0]['ITEMS'][$c]['AVAIL_LEVEL'] = $row['LEVEL']+1;
     }
     else if ($row['LEVEL'] < $row['LEVELS']) {
       $data['SKILLS'][0]['ITEMS'][$c]['AVAIL_LEVEL'] = $row['LEVEL']+1;
       if ($row['PRICE']*($row['LEVEL']+1) > $_SESSION['_user']['SS'][0]['MONEY']) {
         $data['SKILLS'][0]['ITEMS'][$c]['NO_MONEY'][0]['X'] = 1;
       } else {
         $data['SKILLS'][0]['ITEMS'][$c]['TRAIN'][0]['ATTR_ID'] = $row['ATTR_ID'];
       }
     }
     else $data['SKILLS'][0]['ITEMS'][$c]['NO_LEVEL'][0]['X'] = 1;

     $c++;
   }
 }

 $data['OWNED_SKILLS'][0] = $utils->getSkills($auth->getUserId());  

 $data['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'];


$data['SPORT_MENU'] = $menu->getMenuFromArray($sports, 'sport_id');
$tpl->setTemplateFile('tpl/ss_gym.tpl.html'); 
$tpl->addData($data);
$content .= $tpl->parse();
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>