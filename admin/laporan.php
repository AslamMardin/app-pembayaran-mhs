<?php
require_once '../config/config.php';
requireAdmin();
$db = getDB();

$filter_year = $_GET['year'] ?? date('Y');
$filter_status = $_GET['status'] ?? '';

$where = "WHERE YEAR(p.created_at) = '$filter_year'";
if ($filter_status) $where .= " AND p.payment_status = '$filter_status'";

$summary = $db->query("
    SELECT
        COUNT(*) as total_txn,
        SUM(CASE WHEN p.payment_status='success' THEN p.amount_paid ELSE 0 END) as total_success,
        COUNT(CASE WHEN p.payment_status='success' THEN 1 END) as count_success,
        COUNT(CASE WHEN p.payment_status='pending' THEN 1 END) as count_pending,
        COUNT(CASE WHEN p.payment_status='failed' THEN 1 END) as count_failed
    FROM payments p $where
")->fetch_assoc();

// Monthly breakdown
$monthly = $db->query("
    SELECT MONTH(p.created_at) as bulan,
           SUM(CASE WHEN p.payment_status='success' THEN p.amount_paid ELSE 0 END) as total,
           COUNT(CASE WHEN p.payment_status='success' THEN 1 END) as count
    FROM payments p $where
    GROUP BY MONTH(p.created_at) ORDER BY bulan
");
$monthlyData = [];
while ($m = $monthly->fetch_assoc()) $monthlyData[$m['bulan']] = $m;

$months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

$pageTitle = 'Laporan Pembayaran';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-bar-chart me-2"></i>Laporan Pembayaran</h5>
  </div>
  <div class="page-content">
    <!-- Filter -->
    <div class="card mb-4">
      <div class="card-body py-2">
        <form class="row g-2 align-items-end">
          <div class="col-auto">
            <label class="form-label small">Tahun</label>
            <select name="year" class="form-select form-select-sm">
              <?php for ($y=date('Y');$y>=2020;$y--): ?>
              <option <?= $filter_year==$y?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">Semua</option>
              <option value="success" <?= $filter_status==='success'?'selected':'' ?>>Berhasil</option>
              <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
              <option value="failed" <?= $filter_status==='failed'?'selected':'' ?>>Gagal</option>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <!-- <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#059669,#10b981);">
          <i class="bi bi-cash-stack stat-icon"></i>
          <h3 style="font-size:18px;"><?= formatRupiah($summary['total_success']) ?></h3>
          <p>Total Pendapatan</p>
        </div>
      </div> -->
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#1a3a6b,#2563eb);">
          <i class="bi bi-receipt stat-icon"></i>
          <h3><?= $summary['total_txn'] ?></h3>
          <p>Total Transaksi</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#f59e0b);">
          <i class="bi bi-hourglass-split stat-icon"></i>
          <h3><?= $summary['count_pending'] ?></h3>
          <p>Pending</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc2626,#ef4444);">
          <i class="bi bi-x-circle stat-icon"></i>
          <h3><?= $summary['count_failed'] ?></h3>
          <p>Gagal</p>
        </div>
      </div>
    </div>

    <!-- Monthly Chart -->
    <div class="card">
      <div class="card-header">Grafik Pembayaran Per Bulan (<?= $filter_year ?>)</div>
      <div class="card-body">
        <canvas id="monthlyChart" height="80"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const monthlyData = <?= json_encode($monthlyData) ?>;
const labels = <?= json_encode($months) ?>;
const amounts = labels.map((_, i) => (monthlyData[i+1]?.total || 0) / 1000000);
const counts = labels.map((_, i) => monthlyData[i+1]?.count || 0);

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Pendapatan (Juta Rp)',
      data: amounts,
      backgroundColor: 'rgba(26,58,107,0.7)',
      borderColor: '#1a3a6b',
      borderWidth: 1
    }, {
      label: 'Jumlah Transaksi',
      data: counts,
      type: 'line',
      borderColor: '#c8a951',
      backgroundColor: 'transparent',
      tension: 0.4,
      yAxisID: 'y1'
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { title: { display: true, text: 'Jutaan Rp' } },
      y1: { position: 'right', title: { display: true, text: 'Jumlah Transaksi' } }
    }
  }
});
</script>
<?php include '../includes/footer.php'; ?>
