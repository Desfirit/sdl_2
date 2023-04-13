<html>
<head>
    <title>Пример защиты от bruteforce атаки</title>
    <!--Stylesheet-->
    <style media="screen">
      *,
*:before,
*:after{
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}
body{
    background-color: #080710;
}
.background{
    width: 430px;
    height: 520px;
    position: absolute;
    transform: translate(-50%,-50%);
    left: 50%;
    top: 50%;
}
.background .shape{
    height: 200px;
    width: 200px;
    position: absolute;
    border-radius: 50%;
}
.shape:first-child{
    background: linear-gradient(
        #1845ad,
        #23a2f6
    );
    left: -80px;
    top: -80px;
}
.shape:last-child{
    background: linear-gradient(
        to right,
        #ff512f,
        #f09819
    );
    right: -30px;
    bottom: -80px;
}
form{
    height: 520px;
    width: 400px;
    background-color: rgba(255,255,255,0.13);
    position: absolute;
    transform: translate(-50%,-50%);
    top: 50%;
    left: 50%;
    border-radius: 10px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 40px rgba(8,7,16,0.6);
    padding: 50px 35px;
}
form *{
    font-family: 'Poppins',sans-serif;
    color: #ffffff;
    letter-spacing: 0.5px;
    outline: none;
    border: none;
}
form h3{
    font-size: 32px;
    font-weight: 500;
    line-height: 42px;
    text-align: center;
}

label{
    display: block;
    margin-top: 30px;
    font-size: 16px;
    font-weight: 500;
}

label.failed{
    text-align: center;
    color: red;
    font-size: 18px;
    font-weight: 600;
}

label.success{
    text-align: center;
    color: green;
    font-size: 18px;
    font-weight: 600;
}

input{
    display: block;
    height: 50px;
    width: 100%;
    background-color: rgba(255,255,255,0.07);
    border-radius: 3px;
    padding: 0 10px;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 300;
}
::placeholder{
    color: #e5e5e5;
}
input[type=submit]{
    margin-top: 50px;
    width: 100%;
    background-color: #ffffff;
    color: #080710;
    padding: 15px 0;
    font-size: 18px;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
}

    </style>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <form action="#" method="POST">
        <h3>Authorization</h3>

        <label for="username">Login</label>
        <input type="text" name="username" placeholder="Mger" id="username">

        <label for="password">Password</label>
        <input type="password" name="password" placeholder="Password" id="password">

        <input type="submit" value="Login" name="Login">
    </form>

<?php

function init_connection(){
    return mysqli_connect("mysql-brute", "hacker", "228008");
}

function register($mysqli, $ip){
    $data = mysqli_prepare($mysqli, "INSERT INTO brute.black_list(ip_address, last_login) VALUES (?, ?);");

    $date = date('Y-m-d H:i:s');

    $data->bind_param("ss", $ip, $date);

    $data->execute();
    
    return $data->get_result();
}

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

function ban_user($mysqli, $ip){
    $data = mysqli_prepare($mysqli, "UPDATE brute.black_list SET is_banned = 1 WHERE ip_address = ?;");

    $data->bind_param("s", $ip);

    $data->execute();
    
    return $data->get_result();
}

function update_attempt($mysqli, $ip, $curInfo){
    $data = mysqli_prepare($mysqli, "UPDATE brute.black_list SET last_login = ?, login_attempt = ? WHERE ip_address = ?;");

    $date = date('Y-m-d H:i:s');

    $newCount = $curInfo['login_attempt'] + 1;

    $lastLoginDate = strtotime($curInfo['last_login']);

    $hour = 60*60;

    if(strtotime($date) - $lastLoginDate > 60*60)
        $newCount = 1;

    $data->bind_param("sis", $date, $newCount, $ip);

    $data->execute();
    
    return $data->get_result();
}

function check_brute($mysqli){
    $userIp = $_SERVER['REMOTE_ADDR'];

    $info = get_ip_info($mysqli, $userIp);

    if($info == null)
    {
        register($mysqli, $userIp);
        return get_ip_info($mysqli, $userIp);
    }

    update_attempt($mysqli, $userIp, $info);
    $info = get_ip_info($mysqli, $userIp);

    if ($info['login_attempt'] > 10)
    {
        ban_user($mysqli, $userIp);
        $info['is_banned'] = 1;
        return $info;
    }

    return $info;
}

if( isset( $_POST[ 'Login' ] ) ) {
    if(!isset($_POST['username']) or !isset($_POST['password'])){
        echo "<label class='failed'>Empty login or password</label>";
        return;
    }

    $mysqli = init_connection();
    if(!$mysqli){
        //echo mysqli_connect_error();
        return;
    }
       
    $info = check_brute($mysqli);
    if ($info['is_banned'])
    {
        echo "<label class='failed'>You were banned, noob</label>";
        return;
    } 
    else if($info['login_attempt'] > 3)
    {
        $secondsToWait = ($info['login_attempt'] - 3) * 5;
        sleep($secondsToWait);
    }

    // CWE-20
    if((mb_strlen($_POST['username']) > 20) or (mb_strlen($_POST['password']) > 25)){
        echo "<label class='failed'>Too long login or password</label>";
        return;
    }

    // CWE-79, CWE-598
	// Get username
	$user = htmlspecialchars ($_POST[ 'username' ]);
	// Get password
	$pass = htmlspecialchars ($_POST[ 'password' ]);
    
    // CWE-327
	$password_hash = hash("sha256", $pass); //+ get_salt('shhh'));

    // CWE-89
	// Check the database
    $data = mysqli_prepare($mysqli, "SELECT * FROM brute.users WHERE login = ? AND password = ? LIMIT 1;");

    $data->bind_param("ss", $user, $password_hash);

    $data->execute();
    
    $result = $data->get_result();
	if( $result && mysqli_num_rows( $result ) == 1 ) {
		// Get users details
		$row    = mysqli_fetch_assoc( $result );

        $login = $row['login'];
		// Login successful
        echo "<label class='success'>Welcome, {$login}!</label>";
	}
	else {
		// Login failed
        echo "<label class='failed'>Log in failed</label>";
	}
}
?>
</body>
</html>