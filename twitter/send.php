<?
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/*
 * Send a tweet
 */
if ((isset($_POST['tweet'])) && ($_POST['tweet']!="")){
	if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
	    "ERROR, session closed, but I have your token ;)";
	}else{

		/* Asignamos las credenciales de la variable de session pa no trabajar mucho, la idea es que un cron las obtenga de la base de datos */
		$oauth_token = $_SESSION['access_token']['oauth_token'];
		$oauth_token_secret = $_SESSION['access_token']['oauth_token_secret'];

		/* Creamos el objeto twitterOauth. */
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $oauth_token, $oauth_token_secret);
		$connection->post('statuses/update', array('status' => $_POST['tweet']));

		
		echo $_SESSION['access_token']['oauth_token'];
	}
}else{
	echo "EMPTY";
}
?>