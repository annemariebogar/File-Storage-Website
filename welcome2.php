<?php
session_start(); //session start
require '/var/www/html/vendor/autoload.php';
use Aws\S3\S3Client;
$_SESSION['page'] = 0;
$name = $user = $key = $redirect_uri = '';
//create object to access s3 bucket
$s3Client = S3Client::factory(array(
    'version'     => 'latest',
    'signature'   => 'v4',
    'region'      => 'us-east-2',
    'credentials' => array(
        'key'    => 'KEY',
        'secret' => 'SECRET',
    )
));
$db_username = "annemarie"; //Database Username
$db_password = "password"; //Database Password
$host_name = "midterm.cj6k9wsgztik.us-east-2.rds.amazonaws.com"; //Mysql Hostname
$db_name = 'midterm'; //Database Name
$port = '3306';

$conn = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query("SELECT COUNT(user_id) as usercount FROM User WHERE session_key IS NOT NULL");
$user_count = $result->fetch_object()->usercount; //check if any user has logged in from other server
$conn->close();
if(!($user_count > 0) && isset($_SESSION['access_token'])){
    print("There is a problem. Please go to the sign-in page   [<a href='index.html'>Login</a>]");
} else {
    $key = $_SESSION['access_token'];
    $name = $_SESSION['login_name'];
    $user = $_SESSION['login_user'];
    $email = $_SESSION['login_email'];
    $redirect_uri = $_SESSION['redirect_uri'];
    
    print('<HTML>');
    print("<head><TITLE>Welcome Back</TITLE></HEAD>");
    print("<BODY>");
    
    if($_SESSION['upload'] == 1) {
        //download user's folder from s3 bucket
        $dir = '/var/www/html/downloads';
        $bucket = 'bogarmidterm';
        $s3Client->downloadBucket($dir, $bucket);

        $_SESSION['upload'] = 0;
    }
    
    print('Welcome, ' . $name . '!           ');
    print('[<a href="'.$redirect_uri.'?logout=1">Log Out</a>]'); //logout button
    print("<br />");
    print("<br />");
    print('[<a href="http://ec2-52-15-200-141.us-east-2.compute.amazonaws.com/request.php">View File Info</a>]');
    print("<br />");
    print("<br />");
    //buttons to upload files
    print("<form action='upload.php' method='POST' enctype='multipart/form-data'>");
    print("<input type='file' name='userfile' id='userfile' /><br />");
    print("<input type='submit' value='Upload me!' />");
    print("</form>");

    // print out a directory of the current upload area

    print "<HR><BR>Directory<BR>";

    $filelist = scandir('/var/www/html/uploads'); //show uploaded files

    foreach ($filelist as $curfile)
    {
        if (($curfile != ".") && ($curfile != ".."))
	   {
            print("<A HREF=downloads/" . $curfile . ">". $curfile . "</A><br>");
	   }	
    }
    $filelist = scandir('/var/www/html/downloads'); //show files from s3 bucket
    foreach ($filelist as $curfile)
    {
        if (($curfile != ".") && ($curfile != ".."))
        {
            print("<A HREF=downloads/" . $curfile . ">". $curfile . "</A><br>");
        }   
    }

    print("</BODY>");
    print("</HTML>");
}

?>