<?php
session_start();
$_SESSION['state'] = "";
unset($_SESSION['state']);

$_SESSION['expires_at'] = "";
unset($_SESSION['expires_at']);

$_SESSION['access_token'] = "";
unset($_SESSION['access_token']);

session_destroy('linkedin');
session_destroy();
?>
<script>
window.opener.location.reload(true);
window.close();
</script>