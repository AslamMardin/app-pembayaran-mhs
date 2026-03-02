<?php
// payment/notification.php
require_once '../config/config.php';

$db = getDB();

// Ambil raw JSON dari Midtrans
$json_body = file_get_contents('php://input');
$notification = json_decode($json_body, true);

if (!$notification) {
    http_response_code(400);
    exit('Invalid payload');
}

// Ambil data penting
$order_id           = $notification['order_id'] ?? '';
$status_code        = $notification['status_code'] ?? '';
$gross_amount       = $notification['gross_amount'] ?? 0;
$signature_key      = $notification['signature_key'] ?? '';
$transaction_status = $notification['transaction_status'] ?? '';
$payment_type       = $notification['payment_type'] ?? '';
$fraud_status       = $notification['fraud_status'] ?? '';

// =========================
// VERIFIKASI SIGNATURE
// =========================
$expected_sig = hash(
    'sha512',
    $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY
);

if ($signature_key !== $expected_sig) {
    http_response_code(403);
    exit('Invalid signature');
}

// =========================
// TENTUKAN STATUS PEMBAYARAN
// =========================
$payment_status = 'pending';

if ($transaction_status === 'settlement' || 
   ($transaction_status === 'capture' && $fraud_status === 'accept')) {
    $payment_status = 'success';
} elseif (in_array($transaction_status, ['deny', 'cancel', 'failure'])) {
    $payment_status = 'failed';
} elseif ($transaction_status === 'expire') {
    $payment_status = 'expired';
}

// =========================
// UPDATE DATABASE
// =========================
$amount = (float) $gross_amount; // pastikan angka
$now = date('Y-m-d H:i:s');
$response_json = json_encode($notification);

// Debug nominal (opsional, hapus kalau sudah aman)
// file_put_contents('debug_amount.txt', $gross_amount);

$stmt = $db->prepare("
    UPDATE payments 
    SET payment_status=?, 
        payment_method=?, 
        amount_paid=?, 
        payment_date=?, 
        midtrans_response=? 
    WHERE order_id=?
");

$stmt->bind_param(
    'ssdsss',
    $payment_status,
    $payment_type,
    $amount,
    $now,
    $response_json,
    $order_id
);

if (!$stmt->execute()) {
    file_put_contents('error_log.txt', $stmt->error);
    http_response_code(500);
    exit('Database error');
}

// =========================
// UPDATE STATUS BILL
// =========================
if ($payment_status === 'success') {

    $bill_stmt = $db->prepare("
        UPDATE bills b
        INNER JOIN payments p ON b.id = p.bill_id
        SET b.status = 'paid'
        WHERE p.order_id = ?
    ");
    $bill_stmt->bind_param('s', $order_id);
    $bill_stmt->execute();

} elseif ($payment_status === 'failed') {

    $bill_stmt = $db->prepare("
        UPDATE bills b
        INNER JOIN payments p ON b.id = p.bill_id
        SET b.status = 'failed'
        WHERE p.order_id = ?
    ");
    $bill_stmt->bind_param('s', $order_id);
    $bill_stmt->execute();
}

http_response_code(200);
echo json_encode(['status' => 'ok']);