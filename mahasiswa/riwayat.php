<?php
require_once '../config/config.php';
requireMahasiswa();
$db = getDB();
$sid = $_SESSION['student_id'];

$payments = $db->prepare("
    SELECT p.*, b.bill_code, b.description, b.semester, b.academic_year
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    WHERE b.student_id = ?
    ORDER BY p.created_at DESC
");
$payments->bind_param('i', $sid);
$payments->execute();
$payments = $payments->get_result();

$pageTitle = 'Riwayat Pembayaran';
include '../includes/header.php';
include '../includes/mhs_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran</h5>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-header">Semua Riwayat Transaksi</div>
      <div class="card-body">
        <?php if ($payments->num_rows === 0): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          Belum ada riwayat pembayaran
        </div>
        <?php else: ?>
        <table class="table datatable table-hover">
          <thead>
            <tr><th>Order ID</th><th>Tagihan</th><th>Metode</th><th>Tgl Bayar</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php while ($p = $payments->fetch_assoc()): ?>
            <tr>
              <td><code><?= $p['order_id'] ?></code></td>
              <td>
                <?= htmlspecialchars($p['description']) ?><br>
                <small class="text-muted">Semester <?= $p['semester'] ?> · <?= $p['academic_year'] ?></small>
              </td>
              <td><?= $p['payment_method'] ? ucfirst($p['payment_method']) : '-' ?></td>
              <td><?= $p['payment_date'] ? date('d/m/Y H:i', strtotime($p['payment_date'])) : '-' ?></td>
              <td><?= getStatusBadge($p['payment_status']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
