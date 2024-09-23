<?php
session_start();
header("Content-Type:application/json");
$db_username = "annemarie"; //Database Username
$db_password = "passowrd"; //Database Password
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

	$method = $_SERVER['REQUEST_METHOD'];
	$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

	if(count($request) == 2 && $method == 'GET') {
		$verb = strtolower($request[0]);
		$verb = str_replace(' ', '', $verb);
		$noun = $request[1];
		$conn = new mysqli($host_name, $db_username, $db_password, $db_name, $port);
    	if ($conn->connect_error) {
        	die("Connection failed: " . $conn->connect_error);
    	}
		if($verb == 'getfiles') {
			$noun = strtolower($noun);
			$noun = str_replace(' ', '', $noun);
			if(!ctype_alpha($noun)){
				deliver_response(400, 'invalid request', NULL);
			} else {
				$data = array();
				$sql = "SELECT * FROM Files WHERE user=\"" . $noun . "\"";
				if($retval = $conn->query($sql)) {
					$count = 0;
					while ($row = $retval->fetch_assoc()) {
    					$data[$count] = array();
    					$data[$count]["user"] = $row['user'];
    					$data[$count]["filename"] = $row['filename'];
    					$data[$count]["size"] = $row['size'];
    					$data[$count]["timestamp"] = $row['day'] . "T" . $row['tme'];
    					$count = $count + 1;
    				}
    				deliver_response(200, $verb, $data);
				} else{
					deliver_response(400, 'invalid request', NULL);
				}
			}
		}
		elseif ($verb == 'getsmaller') {
			if(!(is_numeric($noun) && strpos($noun, '-') === False && strpos($noun, '+') === False)){
				deliver_response(400, 'invalid request', NULL);
			} else {
				$data = array();
				$sql = "SELECT * FROM Files WHERE size<" . $noun;
				if($retval = $conn->query($sql)) {
					$count = 0;
    				while ($row = $retval->fetch_assoc()) {
    					$data[$count] = array();
    					$data[$count]["user"] = $row['user'];
    					$data[$count]["filename"] = $row['filename'];
    					$data[$count]["size"] = $row['size'];
    					$data[$count]["timestamp"] = $row['day'] . "T" . $row['tme'];
    					$count = $count + 1;
    				}
    				deliver_response(200, $verb, $data);
				} else {
					deliver_response(400, 'invalid request', NULL);
				}
			}
		}
		elseif ($verb == 'getbefore') {
			$timestamp = explode("T", $noun);
			$skip = False;
			foreach($timestamp as $t){
				if(!(is_numeric($t) && strpos($t, '-') === False && strpos($t, '+') === False)){
					$skip = True;
					deliver_response(400, 'invalid request', NULL);
				}	
			}
			if($skip === False && count($timestamp == 2)){
				$data = array();
				$sql = "SELECT * FROM Files WHERE day>'" . $timestamp[0] . "'";
				if($retval = $conn->query($sql)) {
					$count = 0;
    				while ($row = $retval->fetch_assoc()) {
    					$data[$count] = array();
    					$data[$count]["user"] = $row['user'];
    					$data[$count]["filename"] = $row['filename'];
    					$data[$count]["size"] = $row['size'];
    					$data[$count]["timestamp"] = $row['day'] . "T" . $row['tme'];
    					$count = $count + 1;
    				}
    			} else {
    				deliver_response(400, 'invalid request', NULL);
    			}
				$sql = "SELECT * FROM Files WHERE day='" . $timestamp[0] . "' AND tme>'" . $timestamp[1] . "'";
				if($retval = $conn->query($sql)) {
    				while ($row = $retval->fetch_assoc()) {
    					$data[$count] = array();
    					$data[$count]["user"] = $row['user'];
    					$data[$count]["filename"] = $row['filename'];
    					$data[$count]["size"] = $row['size'];
    					$data[$count]["timestamp"] = $row['day'] . "T" . $row['tme'];
    					$count = $count + 1;
    				}
				} else {
					deliver_response(400, 'invalid request', NULL);
				}
				deliver_response(200, $verb, $data);
			} else {
				deliver_response(400, 'invalid request', NULL);
			}
		} else {
			deliver_response(400, 'invalid request', NULL);
		}
		$conn->close();
	} else {
		deliver_response(400, 'invalid request', NULL);
	}
}

function deliver_response($status, $status_message, $data){
	header("HTTP/1.1 $status $status_message");

	$reponse['status'] = $status;
	$reponse['status_message'] = $status_message;
	$response['data'] = $data;

	$json_response=json_encode($response);
	echo $json_response;
}
?>