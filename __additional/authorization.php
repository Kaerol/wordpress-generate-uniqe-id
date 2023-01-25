<?php 
global $wpdb;
global $authData;

$headers = apache_request_headers();
$auth = $headers['Authorization'];

$sql = "SELECT id, user_login, user_email FROM `wp_users` where user_pass = '".$auth."'";
$authData = $wpdb->get_results($sql, OBJECT);

if (!$authData)
{	
	header('Location: /index.php?controller=404');
	die;	
}
	
/*	
if ($token != 'b1f6f7eff74ec414bc66bc43168effaf4ef70e5e366ba01d2fc03a5cf9169d91')
{
	header('Location: /index.php?controller=404');
	die;
}
*/