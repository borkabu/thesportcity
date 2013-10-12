<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

session_start();

?>
<html>
<body>
<?php
echo session_id()."<br>";

echo $_SERVER['SERVER_NAME'];
$stuff="borka|".$_SERVER['SERVER_NAME']."|".session_id();
$key="XiTo74dOO09N48YeUmuvbL0E";

function nl() {
    echo "<br/> \n";
}
/*
$iv = mcrypt_create_iv (mcrypt_get_block_size (MCRYPT_TripleDES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM);

// Encrypting
function encrypt($string, $key) {
    $enc = "";
    global $iv;
    $enc=mcrypt_cbc (MCRYPT_TripleDES, $key, $string, MCRYPT_ENCRYPT, $iv);

  return base64_encode($enc);
}

// Decrypting
function decrypt($string, $key) {
    $dec = "";
    $string = trim(base64_decode($string));
    global $iv;
    $dec = mcrypt_cbc (MCRYPT_TripleDES, $key, $string, MCRYPT_DECRYPT, $iv);
  return $dec;
} */

$encrypted = $stuff;// encrypt($stuff, $key);
$decrypted = $encrypted; //decrypt($encrypted, $key);

echo "Encrypted is ".$encrypted . nl();
echo "Decrypted is ".$decrypted . nl(); 

$pieces = explode("|", $decrypted);
echo "<br>";
echo "local user_name: ".$pieces[0]."<br>";
echo "server_name: ".$pieces[1]."<br>";
echo "local session id: ".$pieces[2]."<br>";

function getPage($url) {
  $handle = fopen($url, "rb");
  echo $handle;
  $fd = '';
  do {
    $data = fread($handle, 100000);
    if (strlen($data) == 0) {
       break;
    }
    $fd .= $data;
  } while (true);
echo $data;
  if ($fd) {
    return $fd;
  }
}

$code = getPage("http://www.thesportcity.net/verify_external_auth.php?key=".session_id());

echo $code;
// verify 
?><br>
<iframe src="http://www.thesportcity.net/external_session_test.php?idstring=<?php echo $idstring;?>" width="640" frameborder="yes" height="640">
  <p>Your browser does not support iframes.</p>
</iframe>

</body>
</html>

