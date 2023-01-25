<?php

/**
 * Plugin Name: Generare uniqe id - in all system
 */
if (!defined('ABSPATH')) {
    exit;
}

if (is_file(GENERATE_UNIQE_ID_PHPQRCODE . 'qrlib.php')) {
    require_once GENERATE_UNIQE_ID_PHPQRCODE . 'qrlib.php';
}
if (is_file(GENERATE_UNIQE_ID_PDF . 'fpdf.php')) {
    require_once GENERATE_UNIQE_ID_PDF . 'fpdf.php';
}

function generateUniqeId_generateQrCodeAndTicketPdf($order)
{
    $are_virtual = generateUniqeId_order_contains_ticket_1572($order);

    if ($are_virtual) {
        $uniqeId =    $order->get_meta(UNIQE_ID_META_KEY);
        $order_id = $order->get_id();

        if (empty($uniqeId)) {
            $uniqeId = generateUniqeId_checkUniqeId($order_id);
            $order->update_meta_data(UNIQE_ID_META_KEY, $uniqeId);
            $order->save();

            $pdf_result = generateUniqeId_generateOderTicketPdf($order_id, $uniqeId);

            return ['uniqeId' => $uniqeId, 'file' => $pdf_result['file'], 'error' => $pdf_result['error'], 'filePath' => $pdf_result['filePath']];
        } else {
            $pdf_result = generateUniqeId_generateOderTicketPdf($order_id, $uniqeId);

            return ['uniqeId' => $uniqeId, 'file' => $pdf_result['file'], 'error' => "Unikalny identyfikator juz istnieje." . $pdf_result['error'], 'filePath' => $pdf_result['filePath']];
        }
    } else {
        return ['uniqeId' => '', 'file' => '', 'error' => "W zamówieniu nie znajduje się bilet na XXVII RBiM", 'filePath' => ''];
    }
}

function generateUniqeId_generateOderTicketPdf($order_id, $uniqeId)
{
    $error = '';
    try {
        $fileRandomName = '_' . $order_id . '_' . generateUniqeId_random_string(30);
        $qr_code_file_name = $fileRandomName . '.png';
        $pdf_file_name =  $fileRandomName . '.pdf';

        $qr_code_file_path = ORDER_PDF_DIR . $qr_code_file_name;
        $pdf_file_path = ORDER_PDF_DIR . $pdf_file_name;
        $ticket_template_path = GENERATE_UNIQE_ID_ASSETS . TICKET_TEMPLATE;

        QRcode::png($uniqeId, $qr_code_file_path, QRCODE_ECC, QRCODE_FRAME_SIZE, QRCODE_FRAME_SIZE);
        global $pdf;

        $pdf = new FPDF();
        $pdf->SetFont('Times', '', 12);
        $pdf->AddPage();
        $pdf->Image($ticket_template_path, 0, 0, 210, 297, 'PNG');
        $pdf->SetXY(135, 145);
        $pdf->Write(5, $uniqeId);
        $pdf->Image($qr_code_file_path, 130, 80, 62, 62, 'PNG');
        $pdf->Output('F', $pdf_file_path, true);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    return ['file' => $pdf_file_name, 'error' => $error, 'filePath' => ORDER_PDF_DIR . $pdf_file_name];
}

function generateUniqeId_checkUniqeId($order_id)
{
    global $wpdb;

    do {
        $randomString = generateUniqeId_random_string(UNIQE_ID_META_KEY_LENGHT);

        $sql = 'SELECT p.id, pm1.meta_value ' .
            'FROM ' . $wpdb->prefix . 'posts p ' .
            'left outer join ' . $wpdb->prefix . 'postmeta pm1 on pm1.post_id = p.id and pm1.meta_key = \'' . UNIQE_ID_META_KEY . '\'' .
            'WHERE pm1.meta_value=' . $randomString;

        $result = $wpdb->get_results($sql);
    } while (count($result) != 0);

    return 'z:' . $order_id . '|k:' . $randomString;
}

function generateUniqeId_random_string($length_of_string)
{
    return substr(
        bin2hex(random_bytes($length_of_string)),
        0,
        $length_of_string
    );
}

function generateUniqeId_order_contains_ticket_1572($order)
{
    $are_virtual = false;
    foreach ($order->get_items() as $item_id => $item_product) {
        $product = $item_product->get_product();

        if ($product->is_virtual('yes') && $product->get_id() == "1572") {
            $are_virtual = true;
        }
    }

    return $are_virtual;
}
