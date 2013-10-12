<?php
$db_class = 'db_'.$conf_db_type;
//echo $db_class;
$db = new $db_class;
if (!$db->connect()) {
//  header ("Location: busy.php");
  //die('Labai atsiprašome - šiuo metu serveris užimtas. Pabandykite dar kartą.');
}

?>
