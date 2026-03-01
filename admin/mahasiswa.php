<?php
require_once '../config/config.php';
requireAdmin();
$db = getDB();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nim = sanitize($_POST['nim']);
        $name = sanitize($_POST['name']);
        $faculty_id = (int)$_POST['faculty_id'];
        $semester = (int)$_POST['semester'];
        $angkatan = (int)$_POST['angkatan'];
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        $db->begin_transaction();
        try {
            $u = $db->prepare("INSERT INTO users (nim, password, role) VALUES (?,?,'mahasiswa')");
            $u->bind_param('ss', $nim, $password);
            $u->execute();
            $uid = $db->insert_id;

            $s = $db->prepare("INSERT INTO students (user_id,nim,name,faculty_id,semester,angkatan,phone,email) VALUES (?,?,?,?,?,?,?,?)");
            $s->bind_param('issiisss', $uid, $nim, $name, $faculty_id, $semester, $angkatan, $phone, $email);
            $s->execute();
            $db->commit();
            setFlash('success', 'Mahasiswa berhasil ditambahkan.');
        } catch (Exception $e) {
            $db->rollback();
            setFlash('error', 'NIM sudah terdaftar.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $faculty_id = (int)$_POST['faculty_id'];
        $semester = (int)$_POST['semester'];
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $stmt = $db->prepare("UPDATE students SET name=?,faculty_id=?,semester=?,phone=?,email=? WHERE id=?");
        $stmt->bind_param('siissi', $name, $faculty_id, $semester, $phone, $email, $id);
        $stmt->execute();
        setFlash('success', 'Data mahasiswa berhasil diperbarui.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("SELECT user_id FROM students WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $s = $stmt->get_result()->fetch_assoc();
        if ($s) {
            $db->prepare("DELETE FROM users WHERE id=?")->bind_param('i', $s['user_id']);
            $st2 = $db->prepare("DELETE FROM users WHERE id=?");
            $st2->bind_param('i', $s['user_id']);
            $st2->execute();
        }
        setFlash('success', 'Mahasiswa berhasil dihapus.');
    }
    redirect('admin/mahasiswa.php');
}

$students = $db->query("
    SELECT s.*, f.name as faculty_name, f.code as faculty_code
    FROM students s JOIN faculties f ON s.faculty_id = f.id
    ORDER BY s.created_at DESC
");
$faculties = $db->query("SELECT * FROM faculties ORDER BY name");

$pageTitle = 'Data Mahasiswa';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-people-fill me-2"></i>Data Mahasiswa</h5>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Daftar Mahasiswa</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-person-plus me-1"></i>Tambah Mahasiswa
        </button>
      </div>
      <div class="card-body">
        <table class="table datatable table-hover">
          <thead><tr><th>#</th><th>NIM</th><th>Nama</th><th>Fakultas</th><th>Semester</th><th>Angkatan</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php $no=1; while ($s = $students->fetch_assoc()): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><code><?= $s['nim'] ?></code></td>
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><span class="badge bg-primary"><?= $s['faculty_code'] ?></span> <?= htmlspecialchars($s['faculty_name']) ?></td>
              <td>Semester <?= $s['semester'] ?></td>
              <td><?= $s['angkatan'] ?></td>
              <td>
                <button class="btn btn-sm btn-outline-info" onclick="editMhs(<?= htmlspecialchars(json_encode($s)) ?>)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus mahasiswa ini?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Mahasiswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">NIM</label>
            <input type="text" name="nim" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fakultas</label>
            <select name="faculty_id" class="form-select" required>
              <?php $faculties->data_seek(0); while ($f = $faculties->fetch_assoc()): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select">
              <?php for ($i=1;$i<=14;$i++): ?><option><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Angkatan</label>
            <input type="number" name="angkatan" class="form-control" value="<?= date('Y') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
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
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-header"><h5 class="modal-title">Edit Mahasiswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="name" id="editName" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fakultas</label>
            <select name="faculty_id" id="editFaculty" class="form-select">
              <?php $faculties->data_seek(0); while ($f = $faculties->fetch_assoc()): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Semester</label>
            <select name="semester" id="editSemester" class="form-select">
              <?php for ($i=1;$i<=14;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">No. HP</label>
            <input type="text" name="phone" id="editPhone" class="form-control">
          </div>
          <div class="col-md-5">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="editEmail" class="form-control">
          </div>
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
function editMhs(s) {
  document.getElementById('editId').value = s.id;
  document.getElementById('editName').value = s.name;
  document.getElementById('editFaculty').value = s.faculty_id;
  document.getElementById('editSemester').value = s.semester;
  document.getElementById('editPhone').value = s.phone || '';
  document.getElementById('editEmail').value = s.email || '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>
