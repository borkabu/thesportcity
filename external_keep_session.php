<?php
session_set_cookie_params(1800); 
session_start();
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), $_COOKIE[session_name()], time()+1800, '/');
}  

//echo "Reloaded " . date('l jS \of F Y h:i:s A');
//print_r($_SESSION['external_user']);
?>
<script>
  window.setTimeout('location.reload()', 1000000);
</script>
