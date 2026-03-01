<?php
require_once '../config/config.php';
requireMahasiswa();
$db = getDB();
$sid = $_SESSION['student_id'];

$student = $db->prepare("SELECT s.*, f.name as faculty_name FROM students s JOIN faculties f ON s.faculty_id=f.id WHERE s.id=?");
$student->bind_param('i', $sid);
$student->execute();
$student = $student->get_result()->fetch_assoc();

$bills_unpaid = $db->prepare("SELECT COUNT(*) as c FROM bills WHERE student_id=? AND status IN ('unpaid','pending')");
$bills_unpaid->bind_param('i', $sid);
$bills_unpaid->execute();
$unpaid_count = $bills_unpaid->get_result()->fetch_assoc()['c'];

$total_paid = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM bills WHERE student_id=? AND status='paid'");
$total_paid->bind_param('i', $sid);
$total_paid->execute();
$total_paid = $total_paid->get_result()->fetch_assoc()['t'];

$recent_bills = $db->prepare("SELECT * FROM bills WHERE student_id=? ORDER BY created_at DESC LIMIT 5");
$recent_bills->bind_param('i', $sid);
$recent_bills->execute();
$recent_bills = $recent_bills->get_result();

$pageTitle = 'Dashboard Mahasiswa';
include '../includes/header.php';
include '../includes/mhs_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-house me-2"></i>Dashboard</h5>
    <span class="text-muted small"><?= date('l, d F Y') ?></span>
  </div>
  <div class="page-content">
    <!-- Welcome -->
    <div class="card mb-4" style="background:linear-gradient(135deg,#1a3a6b,#0f2447);color:#fff;border:none;">
      <div class="card-body p-4 d-flex justify-content-between align-items-center">
        <div>
          <h4 class="fw-bold mb-1">Halo, <?= htmlspecialchars($student['name']) ?>! 👋</h4>
          <p class="opacity-75 mb-0"><?= htmlspecialchars($student['faculty_name']) ?> · Semester <?= $student['semester'] ?></p>
        </div>
        <div class="text-end">
          <div class="opacity-75 small">NIM</div>
          <h5 class="mb-0 fw-bold"><?= $student['nim'] ?></h5>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div style="width:52px;height:52px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-receipt fs-4 text-warning"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold"><?= $unpaid_count ?></h4>
              <p class="text-muted small mb-0">Tagihan Belum Lunas</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div style="width:52px;height:52px;background:#d1fae5;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-check-circle fs-4 text-success"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold" style="font-size:18px;"><?= formatRupiah($total_paid) ?></h4>
              <p class="text-muted small mb-0">Total Sudah Dibayar</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div style="width:52px;height:52px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-calendar-check fs-4 text-primary"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold">Semester <?= $student['semester'] ?></h4>
              <p class="text-muted small mb-0">Semester Aktif</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Bills -->
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span>Tagihan Terbaru</span>
        <a href="<?= APP_URL ?>/mahasiswa/tagihan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="card-body">
        <?php if ($recent_bills->num_rows === 0): ?>
        <div class="text-center py-4 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          Belum ada tagihan
        </div>
        <?php else: ?>
        <?php while ($b = $recent_bills->fetch_assoc()): ?>
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 bg-light">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($b['description']) ?></div>
            <div class="text-muted small"><?= $b['bill_code'] ?> · Jatuh tempo: <?= $b['due_date'] ? date('d/m/Y', strtotime($b['due_date'])) : '-' ?></div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-success"><?= formatRupiah($b['amount']) ?></div>
            <?= getStatusBadge($b['status']) ?>
          </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
