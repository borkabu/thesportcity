<?php

require '../facebook/facebook.php';

$facebook = new Facebook(array(
   'appId'  => '477192532313402',
   'secret' => 'f90ec16fb5fa6db75e927f118344fe9a',
));

// See if there is a user from a cookie
$user = $facebook->getUser();

if ($user) {
echo 1;
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $user_profile = $facebook->api('/me');
 $logoutUrl = $facebook->getLogoutUrl();   
echo $logoutUrl;
  } catch (FacebookApiException $e) {
    echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
    $user = null;
  }
} else {
 $loginUrl = $facebook->getLoginUrl(array(   
        'scope'            => 'user_about_me, email',   
        'redirect_uri'    => "http://www.thesportcity.net",   
        ));   
}

?>
<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <body>
    <?php if ($user) { ?>
      Your user profile is
      <pre>
        <?php print htmlspecialchars(print_r($user_profile, true)) ?>
      </pre>
    <?php } else { ?>
      <fb:login-button></fb:login-button>
    <?php } ?>
    <div id="fb-root"></div>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId: '<?php echo $facebook->getAppID() ?>',
          cookie: true,
          xfbml: true,
          oauth: true
        });
        FB.Event.subscribe('auth.login', function(response) {
          window.location.reload();
        });
        FB.Event.subscribe('auth.logout', function(response) {
          window.location.reload();
        });
      };
      (function() {
        var e = document.createElement('script'); e.async = true;
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>
  </body>
</html>
