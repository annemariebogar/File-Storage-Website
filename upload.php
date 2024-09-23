<?php
session_start(); //session start

require '/var/www/html/vendor/autoload.php';
use Aws\S3\S3Client;
//create object to access s3 bucket
$s3Client = S3Client::factory(array(
	'version'     => 'latest',
	'signature'	  => 'v4',
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
if(!($user_count > 0)){
    print("There is a problem. Please go to the sign-in page   [<a href='index.html'>Login</a>]");
} else {
	$upload_dir = 'uploads/';
	$name = $_SESSION["login_name"];
	$user = $_SESSION["login_user"];

	//$upload_dir="uploads/"; // where we put these
	$filename = str_replace(' ', '', basename($_FILES["userfile"]["name"])); //take out spaces so downloadable
	$fname = $upload_dir . $filename;
	$uploadOK = 1;
	//check if file exists
	if(file_exists($fname)) {
		echo "Sorry, file already exists.";
		$uploadOK = 0;
	}
	//check size
	if($_FILES["userfile"]["size"] > 500000) {
		echo "Sorry, your file is too large.";
		$uploadOK = 0;
	}
	//check if file is php/html/js/java/c/cpp/py/rb/sql
	$check = array('.php', '.html', '.js', '.java', '.c', '.cpp', '.py', '.rb', '.sql', '>', '<');
	foreach($check as $c) {
		if(strpos($filename, $c) !== FALSE) {
			$uploadOK = 0;
		}
	}
	if($uploadOK == 0) {
		echo "Sorry, your file was not uploaded.";
	} else {
		if(move_uploaded_file($_FILES['userfile']['tmp_name'], $fname)) {
			echo "The file" . $filename . " has been uploaded.";
		} else {
			echo "Sorry, there was an error uploading your file.";
		}
	}
	//add file to database table Files
	$nameForDb = str_replace(' ', '', $name);
	$nameForDb = strtolower($nameForDb);
	date_default_timezone_set("America/New_York");
	$date = date("Ymd");
	$time = date("hia");
	if(strpos($time, 'pm')){
		$t = intval(substr($time, 0, 2));
		$t = $t + 12;
		$time = strval($t) . substr($time, 2);
		$time = str_replace('pm', '', $time);
	} else {
		$time = str_replace('am', '', $time);
	}
	$conn = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$sql = "INSERT INTO Files (user, filename, size, day, tme) VALUES ('" . $nameForDb . "', '" . $filename . "', '" . $_FILES["userfile"]["size"] . "', '" . $date . "','". $time ."')";
	if(!($retval = $conn->query($sql))) {
    	print "Problem accessing query " . $conn->error;
	}
	$conn->close();

	//upload file to user's folder in s3 bucket
	$dir = 'uploads';
	$bucket = 'bogarmidterm';
	$keyPrefix = str_replace(' ', '', $name);
	$s3Client->uploadDirectory($dir, $bucket, $keyPrefix);
	//go to either welcome page or registered page

	header("location: welcome2.php");
}
?>