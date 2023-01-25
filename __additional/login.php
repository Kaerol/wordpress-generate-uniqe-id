<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php' );

class Auth
{
    var $code;
}

function db_login_data($email, $password) {		
	$user = get_user_by( 'login', $email );
	if ( $user && wp_check_password( $password, $user->data->user_pass, $user->ID) ){
		$auth = new Auth();
		$auth->code = $user->user_pass;
		
		return wp_json_encode($auth);
	}else{
		header('HTTP/1.0 403 Forbidden');

		echo 'You are forbidden!';
	}
}

$headers = apache_request_headers();
$auth = $headers['Authorization'];

$loginData = explode(":", base64_decode($auth));

echo db_login_data($loginData[0], $loginData[1]);
