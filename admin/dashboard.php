<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();

// Stats
$stats = [];
$stats['mahasiswa'] = $db->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$stats['tagihan_unpaid'] = $db->query("SELECT COUNT(*) as c FROM bills WHERE status='unpaid'")->fetch_assoc()['c'];
$stats['tagihan_paid'] = $db->query("SELECT COUNT(*) as c FROM bills WHERE status='paid'")->fetch_assoc()['c'];
$stats['total_revenue'] = $db->query("SELECT COALESCE(SUM(b.amount),0) as c FROM bills b WHERE b.status='paid'")->fetch_assoc()['c'];

// Recent payments
$recent = $db->query("
    SELECT p.*, b.bill_code, b.description, b.amount, s.name as student_name, s.nim
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN students s ON b.student_id = s.id
    ORDER BY p.created_at DESC LIMIT 10
");

$pageTitle = 'Dashboard Admin';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</h5>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-success">Admin</span>
      <span class="text-muted small"><?= date('d M Y') ?></span>
    </div>
  </div>

  <div class="page-content">
    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#1a3a6b,#2563eb);">
          <i class="bi bi-people-fill stat-icon"></i>
          <h3><?= $stats['mahasiswa'] ?></h3>
          <p>Total Mahasiswa</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#f59e0b);">
          <i class="bi bi-file-earmark-text stat-icon"></i>
          <h3><?= $stats['tagihan_unpaid'] ?></h3>
          <p>Tagihan Belum Bayar</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#059669,#10b981);">
          <i class="bi bi-check-circle-fill stat-icon"></i>
          <h3><?= $stats['tagihan_paid'] ?></h3>
          <p>Tagihan Lunas</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
          <i class="bi bi-currency-dollar stat-icon"></i>
          <h3 style="font-size:20px;"><?= formatRupiah($stats['total_revenue']) ?></h3>
          <p>Total Pendapatan</p>
        </div>
      </div>
    </div>

    <!-- Recent Payments -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Transaksi Terbaru</span>
        <a href="<?= APP_URL ?>/admin/pembayaran.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Mahasiswa</th>
                <th>Keterangan</th>
                <th>Jumlah</th>
                <th>Tanggal</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $recent->fetch_assoc()): ?>
              <tr>
                <td><code><?= $r['order_id'] ?></code></td>
                <td>
                  <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                  <small class="text-muted"><?= $r['nim'] ?></small>
                </td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td class="fw-bold"><?= formatRupiah($r['amount']) ?></td>
                <td><?= $r['payment_date'] ? date('d/m/Y H:i', strtotime($r['payment_date'])) : '-' ?></td>
                <td><?= getStatusBadge($r['payment_status']) ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if ($recent->num_rows === 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
