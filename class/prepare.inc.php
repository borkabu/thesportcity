<?php
  //This stops SQL Injection in POST vars
//echo $_POST['results'];
  foreach ($_POST as $key => $value) {
    $_POST[$key] = RemoveXSS($value);
    if (!is_array($_POST[$key])) {
      $_POST[$key] = mysql_real_escape_string($_POST[$key]);
    }
  }
//echo $_POST['results'];

  //This stops SQL Injection in GET vars
  foreach ($_GET as $key => $value) {
    $_GET[$key] = RemoveXSS($value);
    $_GET[$key] = mysql_real_escape_string($_GET[$key]);    
  }


?>