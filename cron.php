<?php
session_name('linkedin');
session_start();

/* twitter */
require_once('twitter/twitteroauth/twitteroauth.php');
require_once('twitter/config.php');

/* linkedin */
define('API_KEY',      ''                                          );
define('API_SECRET',   ''                                       );
define('REDIRECT_URI', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
define('SCOPE',        'r_fullprofile r_emailaddress rw_nus'                        );

/* database */
require_once("class/connect.php");
$miconexion = new DB_mysql;
$miconexion->conectar();
$sql = $miconexion->consulta("SELECT * FROM social_account");
$VL_account = $miconexion->disponibilidad();
while ($row = mysql_fetch_row($sql)){
	if ($row[1]=="twitter"){

		$oauth_token = $row[5];
		$oauth_token_secret = $row[6];

		/* Creamos el objeto twitterOauth.*/
		echo "Twitter is done<br /><br />";
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $oauth_token, $oauth_token_secret);
		$connection->post('statuses/update', array('status' => $_POST['message']));
        
	}
	if ($row[1]=="linkedin"){
        echo "Find linkedin record<hr>";
		$access_token = $row[5];
		$arg = '/v1/people/~/shares';
		$sha = share($access_token, 'POST', $arg, array(
            'comment' => 'OAuth 2.0 for pangeaconnect.com coming soon',
            'content' => array(
                'title' => 'OAuth Test',
                'description' => 'OAuth is an open standard for authorization. OAuth provides client applications a (secure delegated access) to server resources on behalf of a resource owner...',
                'submittedUrl' => 'http://gasparbelandria.com/social/linkedin/callback.php',
                'submitted-image-url' => 'http://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Oauth_logo.svg/598px-Oauth_logo.svg.png'
            ),
            'visibility' => array('code' => 'anyone' )
		));


	}
}
mysql_free_result($sql);


/*
 * LINKED[IN] 
 */

function share($access_token, $method, $resource, $body = '', $format='json') {
    $params = array(
        'oauth2_access_token' => $access_token,
        'format'              => $format,
    );
    
    //There might be query parameters in the requested resource, we need to merge!
    $urlInfo = parse_url('https://api.linkedin.com'.$resource);
        
    //Build resource URI
    $url = 'https://api.linkedin.com' . $urlInfo['path'] . '?' . http_build_query($params);

    //Some basic encoding to json if an object or array type is send as body
    $body = json_encode($body);

    /*
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'timeout' => 60
        )
    );
    $context  = stream_context_create($opts);
    $result = file_get_contents($url, false, $context, -1, 40000);  
	*/

    $response = requestCURL($method,$url,$body,$format);

    if($format=='json'){
        
        // Native PHP object, please
        $response = json_decode($response);
        if(isset($response->errorCode)){
            var_dump($response);
            //Reset token if expired.
            //if($response->status == 401) echo "resetToken ()";
            
        }
    }
    return $response;


}

function requestCURL($method,$url,$postData='',$type='json') {
	/*echo $method."<hr>";
	echo $url."<hr>";
	echo $postData."<hr>";*/

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
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


    curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
    curl_setopt($ch, CURLOPT_HEADER          ,0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);

    $result = curl_exec($ch);
    
    return $result;


}
?>
