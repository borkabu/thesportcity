<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

session_start();

?>
<html>
<body>
<?php
$user_name = "borka_ef";
$user_email = "borka@tdd.lt";
echo session_id()."<br>";

$code = getPage("http://127.0.0.1:8080/thesportcity/authentication_server.php?client=eurofootball.lt&user_ip=127.0.0.1&client_ip=127.0.0.1&user_name=".$user_name);

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

//$iv = mcrypt_create_iv (mcrypt_get_block_size (MCRYPT_TripleDES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM);

// Encrypting
function encrypt($string, $key) {
// $encoded = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key)));
// echo "<br>".$encoded."<br>";
 return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
}

$data = explode("|", $code);
$id = $data[0];
$seed = $data[1];

$stuff = $id."|".$user_name."|".$user_email."|".$_SERVER['REMOTE_ADDR'];
// encrypt with seed
$encrypted = encrypt($stuff, $seed);

//$code = getPage("http://127.0.0.1:8080/thesportcity/verify_external_auth.php?key=".session_id());

echo $encrypted."<br>";
// verify 
?><br>
<script>
  // Resize iframe to full height
  function resizeIframe(height)
  {
    // "+60" is a general rule of thumb to allow for differences in
    // IE & and FF height reporting, can be adjusted as required..
    document.getElementById('tsc').height = parseInt(height)+60;
  }
</script>

<iframe id="tsc" src="http://127.0.0.1:8080/thesportcity/external_session_test.php?source=eurofootball.lt&id=<?php echo $id?>&idstring=<?php echo urlencode($encrypted);?>" width="860" frameborder="no" style="border-width:1px">
  <p>Your browser does not support iframes.</p>
</iframe>

</body>
</html>

