<?php
require_once '../config/config.php';
requireAdmin();
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $code = strtoupper(sanitize($_POST['code']));
        if ($name && $code) {
            $stmt = $db->prepare("INSERT INTO faculties (name, code) VALUES (?,?)");
            $stmt->bind_param('ss', $name, $code);
            if ($stmt->execute()) setFlash('success', 'Fakultas berhasil ditambahkan.');
            else setFlash('error', 'Kode fakultas sudah ada.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $code = strtoupper(sanitize($_POST['code']));
        $stmt = $db->prepare("UPDATE faculties SET name=?, code=? WHERE id=?");
        $stmt->bind_param('ssi', $name, $code, $id);
        $stmt->execute();
        setFlash('success', 'Fakultas berhasil diperbarui.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $check = $db->prepare("SELECT COUNT(*) as c FROM students WHERE faculty_id=?");
        $check->bind_param('i', $id);
        $check->execute();
        if ($check->get_result()->fetch_assoc()['c'] > 0) {
            setFlash('error', 'Tidak dapat dihapus, ada mahasiswa terdaftar di fakultas ini.');
        } else {
            $db->prepare("DELETE FROM faculties WHERE id=?")->bind_param('i', $id) && $db->execute();
            $stmt = $db->prepare("DELETE FROM faculties WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            setFlash('success', 'Fakultas berhasil dihapus.');
        }
    }
    redirect('admin/fakultas.php');
}

$faculties = $db->query("
    SELECT f.*, COUNT(s.id) as student_count
    FROM faculties f LEFT JOIN students s ON f.id = s.faculty_id
    GROUP BY f.id ORDER BY f.name
");

$pageTitle = 'Kelola Fakultas';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-building me-2"></i>Kelola Fakultas</h5>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Daftar Fakultas</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus-lg me-1"></i>Tambah Fakultas
        </button>
      </div>
      <div class="card-body">
        <table class="table datatable">
          <thead><tr><th>#</th><th>Kode</th><th>Nama Fakultas</th><th>Jumlah Mahasiswa</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php $no=1; while ($f = $faculties->fetch_assoc()): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><span class="badge bg-primary"><?= $f['code'] ?></span></td>
              <td><?= htmlspecialchars($f['name']) ?></td>
              <td><?= $f['student_count'] ?> mahasiswa</td>
              <td>
                <button class="btn btn-sm btn-outline-warning"
                  onclick="editFakultas(<?= $f['id'] ?>, '<?= addslashes($f['name']) ?>', '<?= $f['code'] ?>')">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" class="d-inline"
                  onsubmit="return confirm('Hapus fakultas ini?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $f['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
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
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Fakultas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Kode Fakultas</label>
          <input type="text" name="code" class="form-control" placeholder="FT" required maxlength="10">
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Fakultas</label>
          <input type="text" name="name" class="form-control" placeholder="Fakultas Teknik" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-header"><h5 class="modal-title">Edit Fakultas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Kode Fakultas</label>
          <input type="text" name="code" id="editCode" class="form-control" required maxlength="10">
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Fakultas</label>
          <input type="text" name="name" id="editName" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Perbarui</button>
      </div>
    </form>
  </div>
</div>

<script>
function editFakultas(id, name, code) {
  document.getElementById('editId').value = id;
  document.getElementById('editName').value = name;
  document.getElementById('editCode').value = code;
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>
