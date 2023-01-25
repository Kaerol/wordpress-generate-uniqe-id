<?php

/**
 * Plugin Name: Generare uniqe id - in all system
 */
if (!defined('ABSPATH')) {
	exit;
}
define('GENERATE_UNIQE_ID_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'generate-uniqe-id' . DIRECTORY_SEPARATOR);
define('GENERATE_UNIQE_ID_ASSETS', GENERATE_UNIQE_ID_DIR . 'assets' . DIRECTORY_SEPARATOR);
//define('GENERATE_UNIQE_ID_JS', GENERATE_UNIQE_ID_ASSETS . 'js' . DIRECTORY_SEPARATOR);
define('GENERATE_UNIQE_ID_INCLUDES', GENERATE_UNIQE_ID_DIR . 'includes' . DIRECTORY_SEPARATOR);
define('GENERATE_UNIQE_ID_PHPQRCODE', GENERATE_UNIQE_ID_INCLUDES . 'phpqrcode' . DIRECTORY_SEPARATOR);
define('GENERATE_UNIQE_ID_PDF', GENERATE_UNIQE_ID_INCLUDES . 'pdf' . DIRECTORY_SEPARATOR);

const UNIQE_ID_META_KEY = 'generated_uniqe_id_meta_key';
const UNIQE_ID_META_KEY_LENGHT = 15;
const ORDER_PDF_DIR = GENERATE_UNIQE_ID_DIR . '../../../orders_pdf' . DIRECTORY_SEPARATOR;
const TICKET_TEMPLATE = 'xxvii_rbim_ticket_template.png';
const URL_ORDER_PDF = 'https://zlotlagow.pl/orders_pdf/';

const QRCODE_ECC = 'H';
const QRCODE_PIXEL_SIZE = 6;
const QRCODE_FRAME_SIZE = 1;

if (is_file(GENERATE_UNIQE_ID_INCLUDES . 'define.php')) {
	require_once GENERATE_UNIQE_ID_INCLUDES . 'define.php';
}
if (is_file(GENERATE_UNIQE_ID_INCLUDES . 'generate_pdf.php')) {
	require_once GENERATE_UNIQE_ID_INCLUDES . 'generate_pdf.php';
}

// Add meta box
add_action('add_meta_boxes', 'generate_uniqe_id_order_details_add_meta_boxes');
function generate_uniqe_id_order_details_add_meta_boxes()
{
	add_meta_box(
		'generate-uniqe-id-modal',
		'Generuj bilet',
		'generate_uniqe_id_callback',
		'shop_order',
		'side',
		'core'
	);
}

// Callbacks
function generate_uniqe_id_callback($post)
{
	global $post;
	$order_id = $post->ID;

	echo '<div><p style="text-align: center">
			<button type="button" class="button woo-generate_uniqe_id" data-order_id="' . $order_id . '" title="Generuj bilet">Generuj bilet</button>
		</p></div>';
	echo '<div><p style="text-align: center">
				<input type="text" class="input woo-generate_uniqe_id" disabled="true" ></input>
			</p></div>';
	echo '<div><p style="text-align: center">
			<a class="woo-generate_uniqe_id_link" href="" target="_blank"></a>
		</p></div>';
	echo '<div><p style="text-align: center">
		<span class="woo-generate_uniqe_id_error"></span>
	</p></div>';
	echo '<input type="hidden" name="tracking_box_nonce" value="' . wp_create_nonce() . '">';
}

add_action('admin_enqueue_scripts', 'generate_uniqe_id_enqueue_script');
function generate_uniqe_id_enqueue_script()
{
	global $pagenow;
	if ($pagenow === 'post.php') {
		$screen = get_current_screen();
		if (is_a($screen, 'WP_Screen') && $screen->id == 'shop_order') {
			wp_enqueue_script('generate-uniqe-id-js', GENERATE_UNIQE_ID_JS . 'generate-uniqe-id.js', array('jquery'));
			wp_localize_script(
				'generate-uniqe-id-js',
				'generate_uniqe_id',
				array(
					'ajax_url'                           => admin_url('admin-ajax.php'),
				)
			);
		}
	}
}

add_action('wp_ajax_save_generated_uniqe_id_next_to_order', 'save_generated_uniqe_id_next_to_order');
function save_generated_uniqe_id_next_to_order()
{
	$order_id = $_POST['order_id'];
	$order = wc_get_order($order_id);

	$pdf_result = generateUniqeId_generateQrCodeAndTicketPdf($order);

	echo wp_json_encode(array('uniqeId' => $pdf_result['uniqeId'], 'url' => URL_ORDER_PDF . $pdf_result['file'], 'error' => $pdf_result['error']));
	die();
}
