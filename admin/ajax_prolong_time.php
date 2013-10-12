<?php
/*
===============================================================================
cat.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of categories
  - deletes categories

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
include('../class/manager_log.inc.php');

$ro = false;
// activate
$db->showquery=true;

  unset($sdata);
  $sdata['DATE_EXPIRED'] = 'NULL';
  $db->update('members', $sdata, "ID=".$_GET['id']);


    $sql = 'SELECT M.ID, M.USER_ID, M.USER_TYPE, SUBSTR(M.DATE_STARTED, 1, 10) DATE_STARTED, 
		SUBSTR(M.DATE_EXPIRED, 1, 10) DATE_EXPIRED, B.SPORT_ID,
               T.TEAM_ID, T.TEAM_NAME, T.CITY, T.COUNTRY, M.POSITION_ID1, 
		M.POSITION_ID2, M.NUM
            FROM members M
                 LEFT JOIN teams T ON M.TEAM_ID=T.TEAM_ID
                 LEFT JOIN busers B ON B.USER_ID=M.USER_ID
            WHERE M.ID = '.$_GET['id'];
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $data['FROM'] = $row['DATE_STARTED'];      
      $data['TO'] = $row['DATE_EXPIRED'];      
      $data['NUMBER'] = $row['NUM'];      
      $data['ID'] = $row['ID'];      
      // correct settings for each type of entity
      if ($row['TEAM_ID'] > 0) {
        $data['URL'] = 'team_edit.php?team_id='.$row['TEAM_ID'];
        $data['TITLE'] = truncateString($row['TEAM_NAME'], 30);
      }
      if ($row['POSITION_ID1'] > 0)
        $data['POSITION_ID1'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']];      
      if ($row['POSITION_ID2'] > 0)
        $data['POS2'][0]['POSITION_ID2'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID2']];      
      
    }
    $db->free();

// --- END DELETE -------------------------------------------------------------

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/prolong_time.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>