<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class SupporterBox extends Box{

  function SupporterBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getSupporterBox ($box=true) {
    global $smarty;
    global $auth;
    global $langs;
    
    // content
    $supporter = "";
    if ($auth->hasSupporter()) {
      $utc = $auth->getUserTimezoneName();
      $supporter['SUPPORTER']['MSG'] = str_replace("%d", $_SESSION["_user"]['END_DATE']." ".$utc, $langs['LANG_GC_BOUGHT_U']); 
    } else if ($_SESSION["_user"]['CREDIT'] < 1) {
      $supporter['SUPPORTER_NO_CREDITS'] = 1;
    }

    if (!$auth->hasSupporter()) {
      if ($_SESSION["_user"]['CREDIT'] >= 0.3) {
        $supporter['PURCHASE_SUPPORTER']['DAY'] = 1;
      } else {
        $supporter['PURCHASE_SUPPORTER']['DAY_UNAVAILABLE'] = 1;
      }

      if ($_SESSION["_user"]['CREDIT'] >= 2) {
        $supporter['PURCHASE_SUPPORTER']['WEEK'] = 1;
      } else {
        $supporter['PURCHASE_SUPPORTER']['WEKK_UNAVAILABLE'] = 1;
      }
  
      if ($_SESSION["_user"]['CREDIT'] >= 9) {
        $supporter['PURCHASE_SUPPORTER']['MONTH'] = 1;
      } else {
        $supporter['PURCHASE_SUPPORTER']['MONTH_UNAVAILABLE'] = 1;
      }
  
      if ($_SESSION["_user"]['CREDIT'] >= 99) {
        $supporter['PURCHASE_SUPPORTER']['YEAR'] = 1;
      } else {
        $supporter['PURCHASE_SUPPORTER']['YEAR_UNAVAILABLE'] = 1;
      }
    }

    $template_file="";
    if ($box)
      $template_file = 'smarty_tpl/bar_supporter.smarty';
    else $template_file = 'smarty_tpl/good_citizen.smarty';
    $smarty->assign("supporter", $supporter);
    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template_file.'.smarty'.($stop-$start);
    return $output;
  } 

  function getSupporterData() {
    global $db;

    
  }

}   
?>