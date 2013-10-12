<?php
/*
===============================================================================
update.inc.php
-------------------------------------------------------------------------------
===============================================================================
*/

function requiredFieldsOk (&$r_fields, &$savedata) {
  if (is_array($r_fields)) {
    for ($c = 0; $c < sizeof($r_fields); $c++) {
      if (strlen(trim($savedata[$r_fields[$c]])) == 0)
        return FALSE;
    }
  }
  return TRUE;
}

// $usess - update session
function buildSaveData (&$s_fields, &$i_fields, &$d_fields, &$c_fields, 
                        &$savedata, $strip = FALSE, $escape = FALSE) {
  $sdata = array();
  
  // string data
  if (is_array($s_fields)) {
    for ($c = 0; $c < sizeof($s_fields); $c++) {
      if ($strip) {
        $savedata[$s_fields[$c]] = strip_tags($savedata[$s_fields[$c]]);
      }
      if ($escape)
        $sdata[$s_fields[$c]] = "'".str_replace("'", "''", $savedata[$s_fields[$c]])."'";
      else	
        $sdata[$s_fields[$c]] = "'".$savedata[$s_fields[$c]]."'";
      
    }
  }
  
  // integer data
  if (is_array($i_fields)) {
    for ($c = 0; $c < sizeof($i_fields); $c++) {
      $val = '';
      if (isset($savedata[$i_fields[$c]]))
        $val = $savedata[$i_fields[$c]];
           
      if (strlen($val) == 0)
        $val = 'null';
      $sdata[$i_fields[$c]] = $val;
    }
  }
  
  // date data
  if (is_array($d_fields)) {
    for ($c = 0; $c < sizeof($d_fields); $c++) {
      $y = isset($savedata[$d_fields[$c].'_y']) ? $savedata[$d_fields[$c].'_y'] : ""; 
      $m = isset($savedata[$d_fields[$c].'_m']) ? $savedata[$d_fields[$c].'_m'] : ""; 
      $d = isset($savedata[$d_fields[$c].'_d']) ? $savedata[$d_fields[$c].'_d'] : "";
      $h = isset($savedata[$d_fields[$c].'_h']) ? $savedata[$d_fields[$c].'_h'] : "";
      $i = isset($savedata[$d_fields[$c].'_i']) ? $savedata[$d_fields[$c].'_i'] : "";
      if (strlen($m) == 0 || strlen($d) == 0 || strlen($y) == 0 || !checkdate ($m, $d, $y)) {
        $sdata[$d_fields[$c]] = 'null';
        
      }
      elseif (strlen($h) > 0 && strlen($i) >0) {
        $sdata[$d_fields[$c]] = "DATE_FORMAT('$y-$m-$d $h:$i', '%Y-%m-%d %H:%i')";
        
      }
      else {
        $sdata[$d_fields[$c]] = "DATE_FORMAT('$y-$m-$d', '%Y-%m-%d')";
        
      }
    }
  }
  
  // chechbox data
  if (is_array($c_fields)) {
    for ($c = 0; $c < sizeof($c_fields); $c++) {
      if (isset($savedata[$c_fields[$c]])) {  
        $val = $savedata[$c_fields[$c]];
        if (empty($val) || strtoupper($val) == 'N') {
          $sdata[$c_fields[$c]] = "'N'";        
        }
        else {
          $sdata[$c_fields[$c]] = "'Y'";         
        }
      } else $sdata[$c_fields[$c]] = "'N'";        
    }
  }
  return $sdata;
}

function dupeFieldsOk ($table, $dupe_fields, $savedata, $except='') {
  global $db;
  if (!is_array($dupe_fields)) {
    // no fields to check
    return TRUE;
  }
  
  // build WHERE clause
  $pre = '';
  $where = '';
  while (list($key, $val) = each($dupe_fields)) {
    $where .= $pre.'UPPER('.$val.') LIKE UPPER(\''.$savedata[$val].'\')';
    $pre = ' OR ';
  }
  
  // add exception
  if (is_array($except)) {
    $pre = '';
    $ex = '';
    while (list($key, $val) = each($except)) {
      if (!empty($val)) {
        $ex .= $pre.'UPPER('.$key.') NOT LIKE UPPER(\''.$val.'\')';
        $pre = ' AND ';
      }
    }
    $where = "($where) AND ($ex)";
  }
  
  $db->select($table, '*', $where);
  if ($db->nextRow()) {
    // exists
    return FALSE;
  }
  else {
    return TRUE;
  }
  $db->free();
}

function dupeFieldsNotEmptyOk ($table, &$dupe_fields, &$savedata, $except='') {
  global $db;
  if (!is_array($dupe_fields)) {
    // no fields to check
    return TRUE;
  }
  
  // build WHERE clause
  $pre = '';
  $where = '';
  while (list($key, $val) = each($dupe_fields)) {
   if (!empty($val)) {
    $where .= $pre.'UPPER('.$val.') LIKE UPPER(\''.$savedata[$val].'\')';
    $pre = ' OR ';
   }
  }
  
  // add exception
  if (is_array($except)) {
    $pre = '';
    $ex = '';
    while (list($key, $val) = each($except)) {
      if (!empty($val)) {
        $ex .= $pre.'UPPER('.$key.') NOT LIKE UPPER(\''.$val.'\')';
        $pre = ' AND ';
      }
    }
    $where = "($where) AND ($ex)";
  }
  
  $db->select($table, '*', $where);
  if ($db->nextRow()) {
    // exists
    return FALSE;
  }
  else {
    return TRUE;
  }
  $db->free();
}

function dupeFieldsMobileOk ($table, &$dupe_fields, &$savedata, $except='') {
  global $db;
  if (!is_array($dupe_fields)) {
    // no fields to check
    return TRUE;
  }
  
  // build WHERE clause
  $pre = '';
  $where = '';
  while (list($key, $val) = each($dupe_fields)) {
    $where .= $pre.$val. '='. getMobile($savedata[$val]);
    $pre = ' OR ';
  }
  
  // add exception
  if (is_array($except)) {
    $pre = '';
    $ex = '';
    while (list($key, $val) = each($except)) {
      if (!empty($val)) {
        $ex .= $pre.'UPPER('.$key.') NOT LIKE UPPER(\''.$val.'\')';
        $pre = ' AND ';
      }
    }
    $where = "($where) AND ($ex)";
  }
//$db->showquery=true;  
  $db->select($table, '*', $where);

  if ($db->nextRow()) {
    // exists
    return FALSE;
  }
  else {
    return TRUE;
  }
  $db->free();
}

?>