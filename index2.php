<?php
session_start(); //session start

require_once ('/var/www/html/Google/autoload.php');
$_SESSION['upload'] = 0;
//Insert your cient ID and secret 
//You can get it from : https://console.developers.google.com/
$client_id = 'CLIENT ID'; 
$client_secret = 'CLIENT SECRET';
$redirect_uri = 'http://ec2-52-15-200-141.us-east-2.compute.amazonaws.com/index2.php';
$_SESSION['redirect_uri'] = $redirect_uri;
$welcome_uri = 'welcome2.php';
$register_uri = 'register.php';

//database
$db_username = "annemarie"; //Database Username
$db_password = "password"; //Database Password
$host_name = "midterm.cj6k9wsgztik.us-east-2.rds.amazonaws.com"; //Mysql Hostname
$db_name = 'midterm'; //Database Name
$port = '3306';

//recursively delete everthing in a folder, then delete the folder
//for the user's folder in downloads folder
function rmdir_recursive($dir) {
    foreach(scandir($dir) as $file) {
        if ('.' === $file || '..' === $file) continue;
        if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
        else unlink("$dir/$file");
    }
    rmdir($dir);
}
//connect to database
$mysqli = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
    if ($mysqli->connect_error) {
        die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
    }

//incase of logout request, just unset the session var
if (isset($_GET['logout'])) {
  unset($_SESSION['access_token']); //log out of session
  $sql = "UPDATE User 
    SET session_key=NULL WHERE user_id='" . $_SESSION["login_user"] . "'";
  if(!($retval = $mysqli->query($sql))) { //set key to NULL in database to inform other users
    print "Problem accessing query " . $mysqli->error;
  }
  $sql = "SELECT user_name FROM User";
  if($retval = $mysqli->query($sql)) {
    while ($row = $retval->fetch_assoc()) {
      $dir = 'downloads/' . str_replace(' ', '', $row['user_name']);
      if(file_exists($dir)) {
        rmdir_recursive($dir);
      }
    }
  }
  $dir = 'uploads/'; //delete any uploaded files from this session
  foreach(glob($dir.'*.*') as $v){
    unlink($v);
  }
  header("Location: http://ec2-52-15-200-141.us-east-2.compute.amazonaws.com/index.html");
} else { 
	$result = $mysqli->query("SELECT COUNT(user_id) as usercount FROM User WHERE session_key IS NOT NULL");
	$user_count = $result->fetch_object()->usercount; //check if any user has logged in from other server
	if($user_count > 0) { //there is a user logged in
		$_SESSION['upload'] = 1;
    $_SESSION['db'] = 0;
    $sql = "SELECT user_id, user_name FROM User 
            WHERE session_key IS NOT NULL";
        if($retval = $mysqli->query($sql)) {
            $row = $retval->fetch_assoc();
            $_SESSION['login_user'] = $row["user_id"];
            $_SESSION['login_name'] = $row["user_name"];
            $_SESSION['access_token'] = $row["session_key"];
        }
		header('Location: ' . filter_var($welcome_uri, FILTER_SANITIZE_URL));
	} else { //if the session was logged out on a different server, make sure the folers are empty
		$dir = 'uploads/'; //make sure uploads folder is empty
  		foreach(glob($dir.'*.*') as $v){
    		unlink($v);
  		}
    $sql = "SELECT user_name FROM User";
    if($retval = $mysqli->query($sql)) {
      while ($row = $retval->fetch_assoc()) {
        $dir = 'downloads/' . str_replace(' ', '', $row['user_name']);
        if(file_exists($dir)) {
          rmdir_recursive($dir);
        }
      }
    }
	}
}

/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");

/************************************************
  When we create the service here, we pass the
  client to it. The client then queries the service
  for the required scopes, and uses that when
  generating the authentication URL later.
 ************************************************/
$service = new Google_Service_Oauth2($client);

/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
*/

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $access_token = $client->getAccessToken();
  $_SESSION['access_token'] = $access_token;
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  //exit;
}

/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
if (isset($access_token) && $access_token) {
  $client->setAccessToken($access_token);
} else {
  $authUrl = $client->createAuthUrl();
}


//Display user info or display login url as per the info we have.
echo '<div style="margin:20px">';
if (isset($authUrl)){ 
	//show login url
	echo '<div align="center">';
	echo '<h3>Login with Google</h3>';
	echo '<div>Please click login button to connect to Google.</div>';
	echo '<a class="login" href="' . $authUrl . '"><img src="g-login-button.png" /></a>';
	echo '</div>';
	
} else {
	
	$user = $service->userinfo->get(); //get user info 
	
	//check if user exist in database using COUNT
	$result = $mysqli->query("SELECT COUNT(user_id) as usercount FROM User WHERE user_id=$user->id");
	$user_count = $result->fetch_object()->usercount; //will return 0 if user doesn't exist
	
	//show user picture
	echo '<img src="'.$user->picture.'" style="float: right;margin-top: 33px;" />';
	
	if($user_count > 0) //if user already exist change greeting text to "Welcome Back"
  {
    $_SESSION["upload"] = 1;
    $_SESSION['login_email'] = $user->email;
    $_SESSION['login_user'] = $user->id;
		$_SESSION['login_name'] = $user->name;
    $sql = "UPDATE User 
            SET session_key='" . $_SESSION['access_token'] . 
            "' WHERE user_id='" . $_SESSION['login_user'] . "'";
    if(!($retval = $mysqli->query($sql))) {
      print "Problem accessing query " . $conn->error;
    }
    $mysqli->close();
    header('Location: ' . filter_var($welcome_uri, FILTER_SANITIZE_URL));
  }
	else //else greeting text "Thanks for registering"
	{ 
		$_SESSION['login_email'] = $user->email;
		$_SESSION['login_user'] = $user->id;
		$_SESSION['login_name'] = $user->name;
    $mysqli->close();
		header('Location: ' . filter_var($register_uri, FILTER_SANITIZE_URL));
  }
	
}
echo '</div>';

?>

