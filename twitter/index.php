<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
require_once("../class/connect.php");
$miconexion = new DB_mysql;
$miconexion->conectar();


/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/verify_credentials');

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham'));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992));
//$connection->post('friendships/destroy', array('id' => 9436992));

/* Include HTML to display on the page */

/* Verificamos el limite de velocidad */
$rate_limit_status = $connection->get('account/rate_limit_status');
$remaining_hits = $rate_limit_status->remaining_hits;
$hourly_limit = $rate_limit_status->hourly_limit;
$reset_time = $rate_limit_status->reset_time;

$ip = $_SERVER['REMOTE_ADDR'];
$now = time();

$sql = $miconexion->consulta("SELECT id FROM social_account WHERE social='twitter' AND screen_name='$content->screen_name'");
$VL_account = $miconexion->disponibilidad();
if ($VL_account == 0){
  	if ($content->name!=""){
		$miconexion->consulta("INSERT INTO social_account VALUES (null, 'twitter', '$content->screen_name', '$content->name', '$content->profile_image_url', '".$_SESSION['access_token']['oauth_token']."', '".$_SESSION['access_token']['oauth_token_secret']."', '', '', '', '1', '$ip', $now);");
		$account_id = mysql_insert_id();
	}else{
		//header('Location: ./clearsessions.php');
	}
}


mysql_free_result($sql);
$miconexion->desconectar(); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>Twitter OAuth by Gaspar</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <style type="text/css">
      img {border-width: 0}
      * {font-family:'Lucida Grande', sans-serif;}
    </style>
	<script>
	window.opener.location.reload(true);
	window.close();
	</script>    
  </head>
  <body>
  	<?
  	if ((isset($_SESSION['access_token']['oauth_token'])!="") && ($_SESSION['access_token']['oauth_token']!="")){
  	?>
		<div>
		<a href="clearsessions.php">LOGOUT</a>
		<?php
			$datos = "$content->name";  // 
			$account = "<img src='$content->profile_image_url' align='absmiddle' width='40' height='40' hspace='10' vspace='10' />@$content->screen_name<br />$content->description";
			echo $account;
		?>
		<hr>
		<div style="text-align:center">
		<strong>I did store your Access-Token and Access-Token-Secret and I will can write any tweet for you:</strong>
		<br />
		<?php
			echo "<br>";
			echo "<strong>oauth_token:</strong> ".$_SESSION['access_token']['oauth_token'];
			echo "<br>";
			echo "<strong>oauth_token_secret:</strong> ".$_SESSION['access_token']['oauth_token_secret'];
		?>
		</div>
		</div>
	<?
	}
	?>
  </body>
</html>
