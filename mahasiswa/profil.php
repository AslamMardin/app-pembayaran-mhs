<?php
require_once '../config/config.php';
requireMahasiswa();
$db = getDB();
$sid = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $stmt = $db->prepare("UPDATE students SET phone=?, email=? WHERE id=?");
        $stmt->bind_param('ssi', $phone, $email, $sid);
        $stmt->execute();
        setFlash('success', 'Profil berhasil diperbarui.');
    } elseif ($action === 'change_password') {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $uid = $_SESSION['user_id'];
        $user = $db->prepare("SELECT password FROM users WHERE id=?");
        $user->bind_param('i', $uid);
        $user->execute();
        $user = $user->get_result()->fetch_assoc();
        if (password_verify($old, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            setFlash('success', 'Password berhasil diubah.');
        } else {
            setFlash('error', 'Password lama tidak sesuai.');
        }
    }
    redirect('mahasiswa/profil.php');
}

$student = $db->prepare("SELECT s.*, f.name as faculty_name FROM students s JOIN faculties f ON s.faculty_id=f.id WHERE s.id=?");
$student->bind_param('i', $sid);
$student->execute();
$student = $student->get_result()->fetch_assoc();

$pageTitle = 'Profil Saya';
include '../includes/header.php';
include '../includes/mhs_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-person-circle me-2"></i>Profil Saya</h5>
  </div>
  <div class="page-content">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card text-center">
          <div class="card-body py-4">
            <div style="width:80px;height:80px;background:#1a3a6b;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
              <i class="bi bi-person-fill text-white fs-2"></i>
            </div>
            <h5 class="fw-bold"><?= htmlspecialchars($student['name']) ?></h5>
            <p class="text-muted small"><?= $student['nim'] ?></p>
            <span class="badge bg-primary"><?= htmlspecialchars($student['faculty_name']) ?></span>
            <div class="row g-2 mt-3">
              <div class="col-6">
                <div class="bg-light rounded p-2">
                  <div class="small text-muted">Angkatan</div>
                  <div class="fw-bold"><?= $student['angkatan'] ?></div>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-light rounded p-2">
                  <div class="small text-muted">Semester</div>
                  <div class="fw-bold"><?= $student['semester'] ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card mb-3">
          <div class="card-header">Edit Profil</div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_profile">
              <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>">
              </div>
              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
          </div>
        </div>
        <div class="card">
          <div class="card-header">Ganti Password</div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="old_password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
              </div>
              <button type="submit" class="btn btn-warning">Ganti Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
