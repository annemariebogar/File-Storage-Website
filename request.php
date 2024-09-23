<?php
session_start();
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
	//Send uri to browser
	if(isset($_POST['SubmitButton'])){
		$send_url = True;
		if(isset($_POST['user']) && !(empty($_POST['user']))){
			$verb = 'getfiles';
			$noun = str_replace(' ', '', $_POST['user']);
			$noun = strtolower($noun);
			if(!ctype_alpha($noun)){
				echo 'Invalid input. Please try again.';
				$send_url = False;
			}
		}
		elseif (isset($_POST['size']) && !(empty($_POST['size']))) {
			$verb = 'getsmaller';
			$noun = $_POST['size'];
			if(!(is_numeric($noun) && strpos($noun, '-') === False && strpos($noun, '+') === False)){
				echo 'Invalid input. Please try again.';
				$send_url = False;
			}
		}
		elseif (isset($_POST['date']) && !(empty($_POST['date']))) {
			$verb = 'getbefore';
			$noun = $_POST['date'];
			if(strpos($noun, '/')){
				$noun = str_replace('/', '', $noun);
			}
			if(!(is_numeric($noun) && strpos($noun, '-') === False && strpos($noun, '+') === False)){
				echo 'Invalid input. Please try again.';
				$send_url = False;
			}
			if(isset($_POST['time']) && !(empty($_POST['time']))){
				$time = $_POST['time'];
				if(strpos($time, ':')){
					$time = str_replace(':', '', $time);
				}
				if(!(is_numeric($time) && strpos($time, '-') === False && strpos($time, '+') === False)){
					echo 'Invalid input. Please try again.';
					$send_url = False;
				}
				$noun = $noun . "T" . $time;
			} else {
				$noun = $noun . "T0000";
			}
		}
		if($send_url == True){
			//create REST style uri
			$request_uri = 'http://ec2-52-15-200-141.us-east-2.compute.amazonaws.com/rest.php/' . $verb . '/' . $noun;
			$ch = curl_init(); //create curl object
			curl_setopt($ch, CURLOPT_URL, $request_uri); //set uri
			curl_setopt($ch, CURLOPT_HEADER, 0); //no header
			curl_exec($ch); //send uri to browser
			curl_close($ch); //delete curl object
		}
	}

	//submit buttons -> ask for a user's name, a size, or a date and time
	print( "<HTML>");
	print("<head><TITLE>request</TITLE></HEAD>");
	print("<form action=\"\" method=\"post\">");
	print("<br>If you would like a list of all the files uploaded by a particular user, enter the user's name here (first and last name): <input type=\"text\" name=\"user\" />");
	print("<br>");
	print("If you would like a list of all the files smaller than a certain size, enter the size here: <input type=\"text\" name=\"size\" />");
	print("<br>");
	print("I you would like a list of all the files uploaded after a certain date and time, enter the date(YYYY/MM/DD): <input type=\"text\" name=\"date\" />");
	print("<br>");
	print("and the time (HH:MM (military time) or 00:00 for whole day): <input type=\"text\" name=\"time\" />");
	print("<input type=\"submit\" name=\"SubmitButton\" />");
	print("<br>");
	print("<br>");
	print('[<a href="welcome2.php">Go Back</a>]'); //logout button
	print("</form>");
	print("<BODY>");
	print("</BODY>");
	print("</HTML>");
} 
?>