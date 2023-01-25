<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_url = plugins_url( '', __FILE__ );
$plugin_url = str_replace( '/includes', '/assets', $plugin_url );
define( 'GENERATE_UNIQE_ID_JS', $plugin_url . '/js/' );
