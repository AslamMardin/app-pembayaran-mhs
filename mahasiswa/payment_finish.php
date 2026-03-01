<?php
require_once '../config/config.php';
$db = getDB();

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? '';
$result = $_GET['result'] ?? 'pending';
$transaction_status = $_POST['transaction_status'] ?? $result;
$payment_type = $_POST['payment_type'] ?? 'online';
$gross_amount = $_POST['gross_amount'] ?? 0;

if ($order_id) {
    // Map Midtrans status to our status
    $status_map = [
        'settlement' => 'success',
        'capture'    => 'success',
        'success'    => 'success',
        'pending'    => 'pending',
        'deny'       => 'failed',
        'expire'     => 'expired',
        'cancel'     => 'failed',
        'failed'     => 'failed',
    ];
    $payment_status = $status_map[$transaction_status] ?? 'pending';

    // Update payment record
    $stmt = $db->prepare("UPDATE payments SET payment_status=?, payment_method=?, amount_paid=?, payment_date=? WHERE order_id=?");
    $amount = $gross_amount ?: 0;
    $now = date('Y-m-d H:i:s');
    $stmt->bind_param('ssdss', $payment_status, $payment_type, $amount, $now, $order_id);
    $stmt->execute();

    // Update bill status if success
    if ($payment_status === 'success') {
        $db->query("UPDATE bills b JOIN payments p ON b.id=p.bill_id SET b.status='paid' WHERE p.order_id='$order_id'");
    } elseif ($payment_status === 'failed') {
        $db->query("UPDATE bills b JOIN payments p ON b.id=p.bill_id SET b.status='failed' WHERE p.order_id='$order_id'");
    }
}

// Get payment details for receipt
$payment = null;
if ($order_id) {
    $stmt = $db->prepare("
        SELECT p.*, b.bill_code, b.description, b.amount, s.name, s.nim
        FROM payments p
        JOIN bills b ON p.bill_id = b.id
        JOIN students s ON b.student_id = s.id
        WHERE p.order_id = ?
    ");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hasil Pembayaran - SIMAK UNASMAN</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f0f4f8; }
    .receipt-card { max-width: 500px; margin: 40px auto; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.12); }
    .receipt-header { padding: 40px 30px; text-align: center; }
    .receipt-body { background: #fff; padding: 30px; }
    .success-icon { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; }
    .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; }
    .detail-row:last-child { border-bottom: none; }
  </style>
</head>
<body>
<?php
$is_success = in_array($result, ['success', 'settlement', 'capture']);
$is_pending = in_array($result, ['pending']);
?>
<div class="receipt-card">
  <div class="receipt-header" style="background:<?= $is_success ? 'linear-gradient(135deg,#059669,#10b981)' : ($is_pending ? 'linear-gradient(135deg,#d97706,#f59e0b)' : 'linear-gradient(135deg,#dc2626,#ef4444)') ?>; color:#fff;">
    <div class="success-icon" style="background:rgba(255,255,255,0.2);">
      <i class="bi bi-<?= $is_success ? 'check-circle-fill' : ($is_pending ? 'hourglass-split' : 'x-circle-fill') ?>"></i>
    </div>
    <h4 class="fw-bold mb-1">
      <?= $is_success ? 'Pembayaran Berhasil!' : ($is_pending ? 'Menunggu Pembayaran' : 'Pembayaran Gagal') ?>
    </h4>
    <p class="opacity-75 mb-0 small">
      <?= $is_success ? 'Terima kasih, pembayaran UKT Anda telah dikonfirmasi' : ($is_pending ? 'Selesaikan pembayaran sesuai instruksi' : 'Terjadi kesalahan, silakan coba kembali') ?>
    </p>
  </div>
  <div class="receipt-body">
    <?php if ($payment): ?>
    <h6 class="fw-bold mb-3 text-muted">DETAIL TRANSAKSI</h6>
    <div class="detail-row">
      <span class="text-muted small">Order ID</span>
      <span class="fw-semibold small"><?= $payment['order_id'] ?></span>
    </div>
    <div class="detail-row">
      <span class="text-muted small">Mahasiswa</span>
      <span class="fw-semibold small"><?= htmlspecialchars($payment['name']) ?> (<?= $payment['nim'] ?>)</span>
    </div>
    <div class="detail-row">
      <span class="text-muted small">Tagihan</span>
      <span class="fw-semibold small"><?= htmlspecialchars($payment['description']) ?></span>
    </div>
    <div class="detail-row">
      <span class="text-muted small">Jumlah</span>
      <span class="fw-bold text-success"><?= formatRupiah($payment['amount']) ?></span>
    </div>
    <div class="detail-row">
      <span class="text-muted small">Metode</span>
      <span class="fw-semibold small"><?= ucfirst($payment['payment_method'] ?? '-') ?></span>
    </div>
    <div class="detail-row">
      <span class="text-muted small">Tanggal</span>
      <span class="fw-semibold small"><?= date('d M Y H:i') ?></span>
    </div>
    <?php endif; ?>

    <div class="d-grid gap-2 mt-4">
      <?php if (isLoggedIn()): ?>
      <a href="<?= APP_URL ?>/mahasiswa/tagihan.php" class="btn btn-primary">Kembali ke Tagihan</a>
      <a href="<?= APP_URL ?>/mahasiswa/riwayat.php" class="btn btn-outline-secondary">Lihat Riwayat</a>
      <?php else: ?>
      <a href="<?= APP_URL ?>/index.php" class="btn btn-primary">Kembali ke Login</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
