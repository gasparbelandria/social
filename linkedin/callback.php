<?php
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

session_name('linkedin');
session_start();
require_once("../class/connect.php");
$miconexion = new DB_mysql;
$miconexion->conectar();

// Change these
define('API_KEY',      '77ok8gyyhzrre9'                                             );
define('API_SECRET',   'bpiidQFjZkBEoJe2'                                           );
define('REDIRECT_URI', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
define('SCOPE',        'r_fullprofile r_emailaddress rw_nus'                        );
  
// OAuth 2 Control Flow
if (isset($_GET['error'])) {
    // LinkedIn returned an error
    print $_GET['error'] . ': ' . $_GET['error_description'];
    exit;
} elseif (isset($_GET['code'])) {
    // User authorized your application
    if ($_SESSION['state'] == $_GET['state']) {
        echo $_SESSION['state'].' :: '.$_GET['state'];
        // Get token so you can make API calls
        getAccessToken();
    } else {
        // CSRF attack? Or did you mix up your states?
        exit;
    }
} else { 
    if ((empty($_SESSION['expires_at'])) || (time() > $_SESSION['expires_at'])) {
        // Token has expired, clear the state
        $_SESSION = array();
    }
    if (empty($_SESSION['access_token'])) {
        // Start authorization process
        getAuthorizationCode();
    }
}
 
// Congratulations! You have a valid token. Now fetch your profile 
$user = fetch('GET', '/v1/people/~:(firstName,lastName,industry,location,email-address,picture-url)');
//echo "<a href='session_destroy.php'>CLOSE SESSION</a>";
//print "<img src=".$user->pictureUrl."><br /><br />";
//var_dump($user);

// getting profile:
/* no entiendo, si es la misma funciona que lineas arriba, porque estaba alli
echo "<hr><hr><hr>";
$arg = '/v1/people/~';
$api = fetch('GET', $arg);
*/
//var_dump($api);

// storage

$del = $miconexion->consulta("DELETE FROM social_account WHERE screen_name='$user->emailAddress' AND social='linkedin'");
$sql = $miconexion->consulta("SELECT id FROM social_account WHERE screen_name='$user->emailAddress'");
$VL_account = $miconexion->disponibilidad();
if ($VL_account == 0){
    if ($user->emailAddress!=""){
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();
        $name = $user->firstName." ".$user->lastName;
        $miconexion->consulta("INSERT INTO social_account VALUES (null, 'linkedin', '$user->emailAddress', '$name', '$user->pictureUrl', '".$_SESSION['access_token']."', '', '', '', '', '1', '$ip', $now);");
        $account_id = mysql_insert_id();
    }else{
        //header('Location: ./clearsessions.php');
    }
}

echo $_SESSION['access_token'];

//header( 'Location:session_destroy.php' ) ;

// Share message
/*
$arg = '/v1/people/~/shares';
$sha = share('POST', $arg, array(
    'comment' => 'OAuth 2.0 for pangeaconnect coming soon',
    'content' => array(
        'title' => 'OAuth Test by http://gasparbelandria.com',
        'description' => 'OAuth is an open standard for authorization. OAuth provides client applications a (secure delegated access) to server resources on behalf of a resource owner.',
        'submittedUrl' => 'http://gasparbelandria.com/social/linkedin/callback.php',
        'submitted-image-url' => 'http://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Oauth_logo.svg/598px-Oauth_logo.svg.png'
    ),
    'visibility' => array('code' => 'anyone' )
));
*/



/*
// writing message: melirausquin@hotmail.com
echo "<hr><hr><hr>";
$json = '{
  "recipients": {
    "values": [
    {
      "person": {
        "_path": "/people/email=melirausquin@hotmail.com"
       }
    }]
  },
  "subject": "Testing.",
  "body": "Message sending from gasparbelandria.com/linkedin "
}';
echo $json;
$arg = '/v1/people/~/mailbox';
$api = pushing('POST', $arg, $json);
//var_dump($api);
*/

exit;
 
function getAuthorizationCode() {
    $params = array('response_type' => 'code',
                    'client_id' => API_KEY,
                    'scope' => SCOPE,
                    'state' => uniqid('', true), // unique long string
                    'redirect_uri' => REDIRECT_URI,
              );
 
    // Authentication request
    $url = 'https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params);
     
    // Needed to identify request when it returns to us
    $_SESSION['state'] = $params['state'];
 
    // Redirect user to authenticate
    header("Location: $url");
    exit;
}
     
function getAccessToken() {
    $params = array('grant_type' => 'authorization_code',
                    'client_id' => API_KEY,
                    'client_secret' => API_SECRET,
                    'code' => $_GET['code'],
                    'redirect_uri' => REDIRECT_URI,
              );
     
    // Access Token request
    $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);
     
    // Tell streams to make a POST request
    $context = stream_context_create(
                    array('http' => 
                        array('method' => 'POST',
                        )
                    )
                );
 
    // Retrieve access token information
    $response = file_get_contents($url, false, $context);
 
    // Native PHP object, please
    $token = json_decode($response);

    // Store access token and expiration time
    $_SESSION['access_token'] = $token->access_token; // guard this! 
    $_SESSION['expires_in']   = $token->expires_in; // relative time (in seconds)
    $_SESSION['expires_at']   = time() + $_SESSION['expires_in']; // absolute time
     
    return true;
}
 
function fetch($method, $resource, $body = '') {
    $params = array('oauth2_access_token' => $_SESSION['access_token'],
                    'format' => 'json',
              );
    if ($body){
        $body = json_encode($body);
    }
    // Need to use HTTPS
    // https://api.linkedin.com     /v1/people/~:(firstName,lastName,industry,location,email-address,picture-url)
    //                              /v1/people/~?oauth2_access_token=AQXdSP_W41_UPs5ioT_t8HESyODB4FqbkJ8LrV_5mff4gPODzOYR
    //                              /v1/people/~/mailbox
    //                              /v1/people/~/shares
    $url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params);
    // Tell streams to make a (GET, POST, PUT, or DELETE) request
    $context = stream_context_create(
                    array('http' => 
                        array('method' => $method)
                    )
                );
 
 
    // Hocus Pocus
    $response = file_get_contents($url, false, $context);
 
    // Native PHP object, please
    return json_decode($response);
}


function share($method, $resource, $body = '', $format='json') {
    $params = array(
        'oauth2_access_token' => $_SESSION['access_token'],
        'format'              => $format,
    );
    
    //There might be query parameters in the requested resource, we need to merge!
    $urlInfo = parse_url('https://api.linkedin.com'.$resource);
        
    //Build resource URI
    $url = 'https://api.linkedin.com' . $urlInfo['path'] . '?' . http_build_query($params);

    //Some basic encoding to json if an object or array type is send as body
    $body = json_encode($body);

    $response = requestCURL($method,$url,$body,$format);

    if($format=='json'){
        
        // Native PHP object, please
        $response = json_decode($response);
        if(isset($response->errorCode)){
            
            //Reset token if expired.
            if($response->status == 401) echo "resetToken ()";
            
        }
    }
    return $response;


}

function requestCURL($method,$url,$postData='',$type='json') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);

    $contentTypes = array(
        'json' => array('application/json','json'),
        'xml' => array('application/xml','xml'),
    );

    $type = $contentTypes[$type];

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-type: application/$type[0]",
        "x-li-format: $type[1]",
        'Connection: close')
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );

    //Useful for debugging, do not disable ssl in production!
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


    curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
    curl_setopt($ch, CURLOPT_HEADER          ,0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);

    $result = curl_exec($ch);
    
    return $result;


}

?>
