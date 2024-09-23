<?php
session_start();
$db_username = "annemarie"; //Database Username
$db_password = "password"; //Database Password
$host_name = "midterm.cj6k9wsgztik.us-east-2.rds.amazonaws.com"; //Mysql Hostname
$db_name = 'midterm'; //Database Name
$port = '3306';

$salt = '';

if((isset($_POST['username']) && !empty($_POST['username'])) && (isset($_POST['password']) && !empty($_POST['password']))) {
	$_SESSION['username'] = $_POST['username'];
	if(ctype_alpha($_SESSION['username'])) {
		$conn = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
		if ($conn->connect_error) {
    		die("Connection failed: " . $conn->connect_error);
		}
		$sql = "SELECT salt FROM User WHERE username='" . $_SESSION['username'] . "'";
		if(($retval = $conn->query($sql))) {
			if($retval->num_rows > 0){
				while($row = $retval->fetch_assoc()){
					$salt = $row['salt'];
				}
			} else {
				echo "You have typed in the wrong username or password";
			}
		} else {
			echo "Problem accessing query " . $conn->error;
		}

		if(!empty($salt)){
			$sql = "SELECT user_name FROM User WHERE password='" . crypt($_POST['password'], $salt) . "'";
			$retval = $conn->query($sql);
			if($retval) {
				if($retval->num_rows > 0){
					$token = rand(1000000, 999999999);
					echo $token;
					$_SESSION["access_token"] = $token;
					$sql = "UPDATE User 
            		SET session_key='" . $token . 
            		"' WHERE username='" . $_POST['username'] . "'";
        			if(!($retval = $conn->query($sql))) {
            			echo "Problem accessing query " . $conn->error;
        			} else {
        				$sql = "SELECT user_name FROM User 
            			WHERE username='" . $_POST['username'] . "'";
        				if(($retval = $conn->query($sql))) {
        					while ($row = $retval->fetch_assoc()) {
        						$_SESSION['login_name'] = $row['user_name'];
    						}
    					} else {
            				echo "Problem accessing query " . $conn->error;
    					}
        				$_SESSION['db'] = 0;
        				$_SESSION['upload'] = 1;
        				header('Location: welcome2.php');
        			}
				} else {
					echo "You have typed in the wrong username or password";
				}
			} else {
				echo "Problem accessing query " . $conn->error;
			}
		} else {
			echo "You have typed in the wrong username or password";
		}
		$conn->close();
	} else {
		echo "You have typed in the wrong username or password";
	}
} else {
	echo "Invalid input";
}

?>
