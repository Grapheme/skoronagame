<?php
# Logging in with Google accounts requires setting special identity,
# so this example shows how to do it.
require 'openid.php';

try {
    # Change 'example.org' to your domain name.
    $domain = 'example.org';
    $openid = new LightOpenID($domain);
    
    if (!$openid->mode) {
        if (isset($_GET['login'])) {
            $openid->identity = 'https://www.google.com/accounts/o8/id';
            header('Location: ' . $openid->authUrl());
        }
?>
<form action="?login" method="post">
    <button>Login with Google</button>
</form>
<?php
    } elseif($openid->mode == 'cancel') {
        echo 'Users has canceled authentication!';
    } else {
        echo 'Users ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
    }
} catch(ErrorException $e) {
    echo $e->getMessage();
}
