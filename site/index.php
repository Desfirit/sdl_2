<?php

function get_ip_info($mysqli, $ip){
    $data = mysqli_prepare($mysqli, "SELECT * FROM brute.black_list WHERE ip_address = ? LIMIT 1;");

    $data->bind_param("s", $ip);

    $data->execute();
    
    $result = $data->get_result();

    if( $result && mysqli_num_rows( $result ) == 1 ) {
		return mysqli_fetch_assoc( $result );
	}

    return null;
}

$mysqli = mysqli_connect("mysql-brute", "hacker", "228008");
if(!$mysqli){
    echo mysqli_connect_error();
    return;
}

$userIp = $_SERVER['REMOTE_ADDR'];

echo "ip = {$userIp} </br>";

$info = get_ip_info($mysqli, $userIp);

$date = strtotime(date('Y-m-d H:i:s'));

echo "curDate = $date </br>";

$lastLoginDate = strtotime($info['last_login']);

echo "lastLoginDate = $lastLoginDate </br>";

$hour = 60*60;

echo "hour = $hour </br>";

$diff = $date - $lastLoginDate;
echo "curDate - listLogin = {$diff}</br>";

if($date - $lastLoginDate > $hour)
    echo "Помилован";
    $newCount = 1;


?>