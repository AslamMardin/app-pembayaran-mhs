<?php
// payment/notification.php
// Midtrans Webhook Handler - konfigurasikan URL ini di Midtrans Dashboard
require_once '../config/config.php';
$db = getDB();

$json_body = file_get_contents('php://input');
$notification = json_decode($json_body, true);

if (!$notification) {
    http_response_code(400);
    exit('Invalid payload');
}

$order_id = $notification['order_id'] ?? '';
$status_code = $notification['status_code'] ?? '';
$gross_amount = $notification['gross_amount'] ?? 0;
$signature_key = $notification['signature_key'] ?? '';
$transaction_status = $notification['transaction_status'] ?? '';
$payment_type = $notification['payment_type'] ?? '';
$fraud_status = $notification['fraud_status'] ?? '';

// Verify signature
$expected_sig = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
if ($signature_key !== $expected_sig) {
    http_response_code(403);
    exit('Invalid signature');
}

// Determine payment status
$payment_status = 'pending';
if ($transaction_status === 'settlement' || ($transaction_status === 'capture' && $fraud_status === 'accept')) {
    $payment_status = 'success';
} elseif (in_array($transaction_status, ['deny', 'cancel', 'expire', 'failure'])) {
    $payment_status = 'failed';
} elseif ($transaction_status === 'expire') {
    $payment_status = 'expired';
}

$now = date('Y-m-d H:i:s');
$response_json = json_encode($notification);

// Update payment
$stmt = $db->prepare("UPDATE payments SET payment_status=?, payment_method=?, amount_paid=?, payment_date=?, midtrans_response=? WHERE order_id=?");
$amount = (float)$gross_amount;
$stmt->bind_param('ssdss', $payment_status, $payment_type, $amount, $now, $response_json, $order_id);
// Note: bind_param mismatch fixed below
$stmt = $db->prepare("UPDATE payments SET payment_status=?, payment_method=?, amount_paid=?, payment_date=?, midtrans_response=? WHERE order_id=?");
$stmt->bind_param('ssdss', $payment_status, $payment_type, $amount, $now, $order_id);

// Simpler update
$ps = $db->prepare("UPDATE payments SET payment_status=?, payment_method=?, amount_paid=?, payment_date=?, midtrans_response=? WHERE order_id=?");
$ps->bind_param('ssdsss', $payment_status, $payment_type, $gross_amount, $now, $response_json, $order_id);
$ps->execute();

if ($payment_status === 'success') {
    $db->query("UPDATE bills b INNER JOIN payments p ON b.id=p.bill_id SET b.status='paid' WHERE p.order_id='$order_id'");
} elseif ($payment_status === 'failed') {
    $db->query("UPDATE bills b INNER JOIN payments p ON b.id=p.bill_id SET b.status='failed' WHERE p.order_id='$order_id'");
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
