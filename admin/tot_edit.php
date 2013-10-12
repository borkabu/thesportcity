<?php
/*
===============================================================================
tot_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit totalizator records
  - review totalizator votes
  - select totalizator winners
  - create new totlizator record

TABLES USED: 
  - BASKET.TOTALIZATORS
  - BASKET.TOTALIZATOR_VOTES
  - BASKET.GAMES
  - BASKET.USERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

ob_start();
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');
include('../class/sms.inc.php');
 
// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$lng = new language;
$frm = new form;


// security layer
include('../class/session.inc.php');
define('ADMIN_TOTO', true);
include('../class/toto.inc.php');
include('../class/headers.inc.php');


if (empty($_admin[MENU_ACTIONS_TOTO_EDIT]) || strcmp($_admin[MENU_ACTIONS_TOTO_EDIT], 'NA') == 0)
{
//  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_ACTIONS_TOTO_EDIT], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (isset($form_save) && !$ro) {
  // update fields
  $s_fields = array('title', 'descr', 'descr_wap');
  $i_fields = array('game_id', 'weight', 'tseason_id');
  $d_fields = array('end_date', 'start_date');
  $c_fields = array('publish', 'wap');
  
  // required fields
  $r_fields = array('start_date_y', 'start_date_m', 'start_date_d', 
                    'start_date_h', 'start_date_i', 
                    'end_date_y', 'end_date_m', 'end_date_d', 
                    'end_date_h', 'end_date_i', 'title', 'game_id', 'tseason_id');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $HTTP_POST_VARS)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = 'Neuþpildyti visi bûtini laukeliai!';
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $HTTP_POST_VARS);

    // proceed to database updates

/*        $winners = implode(',', $won);
        if ($db->update('totalizator_votes', array('WON' => "'Y'"), 
                    "TOTALIZATOR_ID=$totalizator_id AND USER_ID IN ($winners)")) {
          // send notification email to winners
            $db->select('users', 'CREDIT, USER_ID', "USER_ID IN ($winners)");
            while ($row=$db->nextRow()) {
              $credit = $row['CREDIT'] + 2;
           //   $db->update('users', array('CREDIT' => "$credit"), "USER_ID=".$row['USER_ID']);
            } 

            $db->free();

          }*/
//          unset($sdata);
          if (count($won) > 0)
            $sdata['CONFIRMATION_SENT'] = "'Y'";
          else
            $sdata['CONFIRMATION_SENT'] = "'N'";
        }

    if (!empty($totalizator_id)) {
      // UPDATE
      $db->update('totalizators', $sdata, "TOTALIZATOR_ID=$totalizator_id");
    }
    else {
      // INSERT
      $db->insert('totalizators', $sdata);
    }
    
    if (!empty($totalizator_id)) {
      selectWinner($totalizator_id, $game_id);

/*      for ($i = 0; $i < count($points); $i++) {
        unset($sdata);
        $sdata['POINTS'] = $points[$i];
        $db->update('totalizator_votes', $sdata, 
                    "TOTALIZATOR_ID=$totalizator_id AND USER_ID = ".$user_id[$i]);
      }
      $curmonthmax = date("t", mktime(0, 0, 0, $end_date_m, 1, $end_date_y));
$db->showquery = true;
      $sql = "SELECT DISTINCT V.TOTALIZATOR_ID, V.USER_ID, V.SCORE1, 
                       SUBSTRING(T.END_DATE, 1, 10) END_DATE, T.WEIGHT,
                       V.SCORE2, U.USER_NAME, G.SCORE1 GSCORE1, G.SCORE2 GSCORE2,
                       ROUND(SUM(V.POINTS), 2) AS POINTS, SUM(1) TIMES,
                       SUM(case when V.WON='Y' THEN 1 ELSE 0 END) WON, COUNT(1) ALLTIMES 
                FROM totalizator_votes V, totalizators T, tseasons TS, games G, users U
                WHERE G.GAME_ID=T.GAME_ID AND V.USER_ID=U.USER_ID AND V.TOTALIZATOR_ID=T.TOTALIZATOR_ID 
		      AND V.DATE_VOTED < T.END_DATE
                      AND T.TSEASON_ID=TS.TSEASON_ID
                      AND T.END_DATE >= TS.START_DATE 
                      AND TS.TSEASON_ID=".$tseason_id." 
                      AND T.END_DATE >= DATE_FORMAT('".$end_date_y."-".$end_date_m."-01', '%Y-%m-%d') AND
                      T.END_DATE <= DATE_FORMAT('".$end_date_y."-".$end_date_m."-".$curmonthmax." 23:59', '%Y-%m-%d %H:%i') 
                      AND V.POINTS > 0
                GROUP BY U.USER_ID
                
                UNION 
                
                SELECT DISTINCT V.TOTALIZATOR_ID, V.USER_ID, V.SCORE1, 
                       SUBSTRING(T.END_DATE, 1, 10) END_DATE, T.WEIGHT,
                       V.SCORE2, U.USER_NAME, G.SCORE1 GSCORE1, G.SCORE2 GSCORE2,
                       SUM(0) POINTS, SUM(0) TIMES, SUM(0) WON, COUNT(0) ALLTIMES 
                FROM totalizator_votes V, totalizators T, tseasons TS, games G, users U
                WHERE G.GAME_ID=T.GAME_ID AND V.USER_ID=U.USER_ID AND V.TOTALIZATOR_ID=T.TOTALIZATOR_ID
		      AND V.DATE_VOTED < T.END_DATE
                      AND T.TSEASON_ID=TS.TSEASON_ID
                      AND TS.TSEASON_ID=".$tseason_id."
                      AND T.END_DATE >= TS.START_DATE
                      AND T.END_DATE >= DATE_FORMAT('".$end_date_y."-".$end_date_m."-01', '%Y-%m-%d') AND
                      T.END_DATE <= DATE_FORMAT('".$end_date_y."-".$end_date_m."-".$curmonthmax." 23:59', '%Y-%m-%d %H:%i') 
                      AND V.POINTS = 0
                      AND G.SCORE1+G.SCORE2 > 0
                GROUP BY U.USER_ID
          order by points desc";

    $db->query($sql);
   // add data
    $c=0; 
   while ($row = $db->nextRow()) {
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['USER_ID'] = $row['USER_ID'];
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['TSEASON_ID'] = $tseason_id;
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['POINTS'] += $row['POINTS'];
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['ALLTIMES'] += $row['ALLTIMES'];;
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['GOODTIMES'] += $row['TIMES'];
     $data['MONTH'][0]['ITEM'][$row['USER_ID']]['WON'] += $row['WON'];; 
     $c++;
   }*/
/*   if ($c > 0) {
     $db->delete('totalizator_standings', "SEASON_ID=$tseason_id AND MONTH= ".$end_date_m);

     foreach($data['MONTH'][0]['ITEM'] as $value) {
       unset($sdata);
       $sdata['USER_ID'] = $value['USER_ID'];
       $sdata['MONTH'] = $end_date_m;
       $sdata['SEASON_ID'] = $value['TSEASON_ID'];
       $sdata['POINTS'] = $value['POINTS'];
       $sdata['ALLTIMES'] = $value['ALLTIMES'];
       $sdata['GOODTIMES'] = $value['GOODTIMES'];
       $sdata['WON'] = $value['WON'];
       $db->insert('totalizator_standings', $sdata);
     }
   }*/

    if (!$error) {
      // redirect to list page
      $db->close();
      header('Location: '.$HTTP_POST_VARS['referer']);
      exit;
    }
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU'] = getMenu(scriptName($PHP_SELF));
$data['REFERER'] = getReferer($HTTP_POST_VARS);

// new or edit?
if (isset($totalizator_id)) {
  // edit
  $sql = 'SELECT T.TOTALIZATOR_ID, T.GAME_ID, T.TITLE, T.DESCR, T.DESCR_WAP, T.PUBLISH, T.WAP,
            T.CONFIRMATION_SENT, T.WEIGHT, T.TSEASON_ID,
            SUBSTRING(T.START_DATE, 1, 16) START_DATE,
            SUBSTRING(T.END_DATE, 1, 16) END_DATE,
            SUBSTRING(G.START_DATE, 1, 16) G_START_DATE,
            G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
            T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2
         FROM
            totalizators T, games G
            LEFT JOIN teams T1 ON T1.TEAM_ID=G.TEAM_ID1 
            LEFT JOIN teams T2 ON T2.TEAM_ID=G.TEAM_ID2
           WHERE
            T.TOTALIZATOR_ID='.$totalizator_id.'
            AND T.GAME_ID=G.GAME_ID';
  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: tot.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
      $data[$key] = $val;
    }
    if (empty($data['SCORE1'])) {
      $data['SCORE1'] = '-';
      $data['SCORE2'] = '-';
    }
    $toto_weight=$row['WEIGHT'];
  }
  $db->free();
  
  $data['TEAM_NAME1_ENC'] = formEncode($data['TEAM_NAME1']);
  $data['TEAM_NAME2_ENC'] = formEncode($data['TEAM_NAME2']);
  
  $scr1 = $data['SCORE1'];
  $scr2 = $data['SCORE2'];
  if (($scr1 + $scr2) > 0) {
    $diff = abs($scr1 - $scr2);
    $sql_diff = 'ROUND(ABS(ABS(V.SCORE1 - V.SCORE2) - '.$diff.')/(5+'.$diff.'), 2)*4.6';
    $sql_prox = '(ABS(V.SCORE1-'.$scr1.') + ABS(V.SCORE2-'.$scr2.'))/4.6';
    $sql_total = '('.$sql_diff.'+'.$sql_prox.')';
    $sql_points = $toto_weight.'*ROUND(100/('.$sql_total.' + 1), 2)';
    $where = 'AND (((V.SCORE1-V.SCORE2) > 0 AND ('.$scr1.'-'.$scr2.') > 0) 
              OR ((V.SCORE1-V.SCORE2) < 0 AND ('.$scr1.'-'.$scr2.') < 0))';
  }
  else {
    $sql_diff = "'-'";
    $sql_prox = "'-'";
    $sql_total = "'-'";
    $sql_points = "'-'"; 
    $where = '';
  }
  // limit list or not
  // temporarily removed
//  if (!isset($nlim)) {
//    $db->setPage(1, $page_size);
//  }
  
  // generate participant list
  $sql = 'SELECT V.USER_ID, V.SCORE1, V.SCORE2, V.WON, V.WAP,
            SUBSTRING(V.DATE_VOTED, 1, 16) DATE_VOTED,
            U.USER_NAME, U.FIRST_NAME, U.EMAIL, 
            '.$sql_diff.' DIFF,
            '.$sql_prox.' PROX,
            '.$sql_total.' TOTAL,
            '.$sql_points.' POINTS
          FROM totalizator_votes V
               LEFT JOIN users U ON V.USER_ID=U.USER_ID
          WHERE V.TOTALIZATOR_ID='.$totalizator_id.'
              AND V.DATE_VOTED < \''.$data['END_DATE'].'\'  
            '.$where.'
          ORDER BY TOTAL, DATE_VOTED';
//
echo $sql;
  $db->query($sql);
  $c = 0;
  while ($row = $db->nextRow()) {
    $data['ROW'][$c] = $row;
/*    if ($data['CONFIRMATION_SENT'] == 'Y') {
      // email confirmations were already sent
      if ($row['WON'] == 'Y')
        $data['ROW'][$c]['WON'] = '<b>LAIMËTOJAS!</b>';
      else
        $data['ROW'][$c]['WON'] = '';
      $total_points = $data['ROW'][$c]['TOTAL'];
    }
    else */{
      if ($row['WON'] == 'Y')
        $data['ROW'][$c]['WON'] = $frm->getField('adm_tot', 'won[]', $row['USER_ID'], $row['USER_ID']);
      else
        $data['ROW'][$c]['WON'] = $frm->getField('adm_tot', 'won[]', $row['USER_ID']);
    }
    
    if ($row['WAP'] == 'Y') {
      $data['ROW'][$c]['ISWAP'][0]['X']=1;
    }
    if ($c & 2 > 0)
      $data['ROW'][$c]['ODD'][0]['X'] = 1;
    else
      $data['ROW'][$c]['EVEN'][0]['X'] = 1;
    $c++;
  }
  $db->free();
}
else {
  // new record
  $PRESET_VARS['publish'] = 'Y';
  $PRESET_VARS['weight'] =1;
}

// manage message
if ($data['CONFIRMATION_SENT'] == 'Y')
  $data['SENT'][0]['X'] = 1;
else
  $data['NOTSENT'][0]['X'] = 1;

// get common inputs
if (isset($totalizator_id)) {
  $data['GAME_ID_NUM'] = $data['GAME_ID'];
  $data['GAME_ID'] = inputGame('game_id', $data['GAME_ID']);
  $data['TSEASON_ID'] = inputTseasons2('tseason_id', $data['TSEASON_ID']);
}
else {
  $data['GAME_ID'] = inputUpcomingGames('game_id');
  $data['TSEASON_ID'] = inputTseasons2('tseason_id');
}

ob_end_flush();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_tot_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
