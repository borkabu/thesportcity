<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

print_r($_COOKIE);
session_set_cookie_params(1800, '/', '.thesportcity.net'); 
echo setcookie('testas1', "testas", time()+3600*24*365);

echo "cookie: ".$_COOKIE['testas'];
?>    