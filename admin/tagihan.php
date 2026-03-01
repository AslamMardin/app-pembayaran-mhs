<?php
require_once '../config/config.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $student_id = (int)$_POST['student_id'];
        $description = sanitize($_POST['description']);
        $amount = (float)str_replace(['.', ','], ['', '.'], $_POST['amount']);
        $semester = (int)$_POST['semester'];
        $academic_year = sanitize($_POST['academic_year']);
        $due_date = $_POST['due_date'] ?: null;
        $bill_code = generateBillCode();

        $stmt = $db->prepare("INSERT INTO bills (student_id,bill_code,description,amount,semester,academic_year,due_date) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('issdiis', $student_id, $bill_code, $description, $amount, $semester, $academic_year, $due_date);
        if ($stmt->execute()) setFlash('success', 'Tagihan berhasil dibuat: ' . $bill_code);
        else setFlash('error', 'Gagal membuat tagihan.');
    } elseif ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = sanitize($_POST['status']);
        $stmt = $db->prepare("UPDATE bills SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        setFlash('success', 'Status tagihan diperbarui.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM bills WHERE id=? AND status='unpaid'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) setFlash('success', 'Tagihan dihapus.');
        else setFlash('error', 'Tagihan tidak dapat dihapus (sudah ada transaksi).');
    }
    redirect('admin/tagihan.php');
}

$bills = $db->query("
    SELECT b.*, s.name as student_name, s.nim, f.name as faculty_name
    FROM bills b
    JOIN students s ON b.student_id = s.id
    JOIN faculties f ON s.faculty_id = f.id
    ORDER BY b.created_at DESC
");
$students = $db->query("SELECT id, nim, name FROM students ORDER BY name");

$pageTitle = 'Kelola Tagihan';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-file-earmark-text me-2"></i>Kelola Tagihan</h5>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Daftar Tagihan</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus-lg me-1"></i>Buat Tagihan
        </button>
      </div>
      <div class="card-body">
        <table class="table datatable table-hover">
          <thead>
            <tr><th>Kode</th><th>Mahasiswa</th><th>Keterangan</th><th>Jumlah</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php while ($b = $bills->fetch_assoc()): ?>
            <tr>
              <td><code><?= $b['bill_code'] ?></code></td>
              <td>
                <strong><?= htmlspecialchars($b['student_name']) ?></strong><br>
                <small class="text-muted"><?= $b['nim'] ?></small>
              </td>
              <td><?= htmlspecialchars($b['description']) ?></td>
              <td class="fw-bold text-success"><?= formatRupiah($b['amount']) ?></td>
              <td><?= $b['due_date'] ? date('d/m/Y', strtotime($b['due_date'])) : '-' ?></td>
              <td><?= getStatusBadge($b['status']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-warning"
                  onclick="updateStatus(<?= $b['id'] ?>, '<?= $b['status'] ?>')">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
                <?php if ($b['status'] === 'unpaid'): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus tagihan ini?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Buat Tagihan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Mahasiswa</label>
            <select name="student_id" class="form-select" required>
              <option value="">-- Pilih Mahasiswa --</option>
              <?php while ($s = $students->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>"><?= $s['nim'] ?> - <?= htmlspecialchars($s['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label">Keterangan Tagihan</label>
            <input type="text" name="description" class="form-control" placeholder="UKT Semester 7 TA 2024/2025" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Jumlah (Rp)</label>
            <input type="text" name="amount" class="form-control" placeholder="2500000" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select">
              <?php for ($i=1;$i<=14;$i++): ?><option><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Jatuh Tempo</label>
            <input type="date" name="due_date" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tahun Akademik</label>
            <input type="text" name="academic_year" class="form-control" placeholder="2024/2025" value="<?= date('Y') . '/' . (date('Y')+1) ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Buat Tagihan</button>
      </div>
    </form>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id" id="statusId">
      <div class="modal-header"><h5 class="modal-title">Update Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <select name="status" id="statusSelect" class="form-select">
          <option value="unpaid">Belum Bayar</option>
          <option value="pending">Pending</option>
          <option value="paid">Lunas</option>
          <option value="failed">Gagal</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateStatus(id, status) {
  document.getElementById('statusId').value = id;
  document.getElementById('statusSelect').value = status;
  new bootstrap.Modal(document.getElementById('statusModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>
