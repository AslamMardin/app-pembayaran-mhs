<?php
require_once '../config/config.php';
requireAdmin();
$db = getDB();

$payments = $db->query("
    SELECT p.*, b.bill_code, b.description, b.amount, s.name as student_name, s.nim, f.name as faculty_name
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN students s ON b.student_id = s.id
    JOIN faculties f ON s.faculty_id = f.id
    ORDER BY p.created_at DESC
");

$pageTitle = 'Riwayat Pembayaran';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-credit-card me-2"></i>Riwayat Pembayaran</h5>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-header">Semua Transaksi Pembayaran</div>
      <div class="card-body">
        <table class="table datatable table-hover">
          <thead>
            <tr><th>Order ID</th><th>Mahasiswa</th><th>Tagihan</th><th>Jumlah</th><th>Metode</th><th>Tgl Bayar</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php while ($p = $payments->fetch_assoc()): ?>
            <tr>
              <td><code><?= $p['order_id'] ?></code></td>
              <td>
                <strong><?= htmlspecialchars($p['student_name']) ?></strong><br>
                <small class="text-muted"><?= $p['nim'] ?> · <?= $p['faculty_name'] ?></small>
              </td>
              <td><?= htmlspecialchars($p['description']) ?></td>
              <td class="fw-bold"><?= formatRupiah($p['amount_paid'] ?? $p['amount']) ?></td>
              <td><?= $p['payment_method'] ? ucfirst($p['payment_method']) : '-' ?></td>
              <td><?= $p['payment_date'] ? date('d/m/Y H:i', strtotime($p['payment_date'])) : '-' ?></td>
              <td><?= getStatusBadge($p['payment_status']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
