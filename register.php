<?php
session_start();
$db_username = "annemarie"; //Database Username
$db_password = "password"; //Database Password
$host_name = "midterm.cj6k9wsgztik.us-east-2.rds.amazonaws.com"; //Mysql Hostname
$db_name = 'midterm'; //Database Name
$port = '3306';
$name = $_SESSION['login_name'];
$user = $_SESSION['login_user'];
$email = $_SESSION['login_email'];
$key = $_SESSION['access_token'];

if(isset($_POST['SubmitButton'])){
	if((isset($_POST['username']) && !empty($_POST['username'])) && (isset($_POST['password']) && !empty($_POST['password']))) {
		if(!(ctype_alpha($_POST['username']))) {
			echo("Invalid username");
		} else {
			$conn = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
			if ($conn->connect_error) {
    			die("Connection failed: " . $conn->connect_error);
    		}
    		$result = $conn->query("SELECT COUNT(user_name) as usercount FROM User WHERE username='" . $_POST['username'] . "'");
    		$user_count = $result->fetch_object()->usercount;
    		if($user_count > 0) {
    			$conn->close();
    			echo("Username not available. Please choose another one.");
    		} else {
    				$salt = rand(1000000, 999999999);
					$password = crypt($_POST['password'], $salt);
					$_SESSION['upload'] = 1;
					$sql = "INSERT INTO User (user_id, user_name, user_email, session_key, username, password, salt) VALUES ('" . $user . "', '" . $name . "', '" . $email . "', '" . $key . "', '" . $_POST['username'] . "', '" . $password . "', '" . $salt . "')";
            		if(!($retval = $conn->query($sql))) {
                		print "Problem accessing query " . $conn->error;
            		}
            		$conn->close();
					header("Location: welcome2.php");
			}
    	}

	} else {
		echo("Invalid username or password");
	}
}

print( "<HTML>");
print("<head><TITLE>request</TITLE></HEAD>");
print("<form action=\"\" method=\"POST\">");
print("<br>");
print("<p>Please choose a user name and password.</p>");
print("Choose a <b>username</b> (must contain only letters): <input type=\"text\" name=\"username\" />");
print("<br>");
print("Choose a <b>password</b>: <input type=\"password\" name=\"password\" />");
print("<br>");
print("<input type=\"submit\" name=\"SubmitButton\" />");
print("</form>");
print("<BODY>");
print("</BODY>");
print("</HTML>");


?>