<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    if (isAdmin()) redirect('admin/dashboard.php');
    else redirect('mahasiswa/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = sanitize($_POST['nim'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nim) || empty($password)) {
        $error = 'NIM dan password wajib diisi.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE nim = ?");
        $stmt->bind_param('s', $nim);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nim'] = $user['nim'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'mahasiswa') {
                $s = $db->prepare("SELECT * FROM students WHERE nim = ?");
                $s->bind_param('s', $nim);
                $s->execute();
                $student = $s->get_result()->fetch_assoc();
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['name'] = $student['name'];
                redirect('mahasiswa/dashboard.php');
            } else {
                $_SESSION['name'] = 'Administrator';
                redirect('admin/dashboard.php');
            }
        } else {
            $error = 'NIM atau password salah.';
        }
        $db->close();
    }
}
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SIMAK UNASMAN</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #1a3a6b 0%, #0f2447 50%, #c8a951 100%);
      min-height: 100vh; display: flex; align-items: center;
    }
    .login-card {
      border: none; border-radius: 20px; overflow: hidden;
      box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    }
    .login-left {
  position: relative;
  padding: 50px 40px;
  color: #fff;
  background: url('assets/img/unnamed-14.jpg') center/cover no-repeat;
  overflow: hidden;
  background-position: -4rem 0px;
}

/* Overlay gelap */
.login-left::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(10, 25, 50, 0.65); /* tingkat kegelapan */
  backdrop-filter: blur(2px); /* efek blur */
  -webkit-backdrop-filter: blur(5px);
}

/* Supaya isi tetap di atas overlay */
.login-left > * {
  position: relative;
  z-index: 2;
}
    .login-right { background: #fff; padding: 50px 40px; }
    .logo-circle {
      width: 80px; height: 80px; background: #c8a951;
      border-radius: 20px; display: flex; align-items: center;
      justify-content: center; margin-bottom: 20px;
    }
    .form-control:focus { border-color: #1a3a6b; box-shadow: 0 0 0 0.2rem rgba(26,58,107,0.15); }
    .btn-login { background: #1a3a6b; border: none; border-radius: 10px; padding: 12px; font-weight: 600; }
    .btn-login:hover { background: #0f2447; }
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; z-index: 10; }
    .input-icon input { padding-left: 38px; border-radius: 10px; }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="login-card row g-0">
        <!-- Left Panel -->
        <div class="col-md-5 login-left d-flex flex-column justify-content-center">
          <div class="logo-circle">
            <i class="bi bi-mortarboard-fill fs-1 text-white"></i>
          </div>
          <h4 class="fw-bold mb-1">SIMAK UNASMAN</h4>
          <p class="opacity-75 small mb-4">Sistem Informasi Pembayaran Mahasiswa<br>Universitas Al-Asyariah Mandar</p>
          <hr class="border-white opacity-25">
          <div class="mt-3">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-shield-check-fill text-warning"></i>
              <small>Sistem pembayaran aman & terpercaya</small>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-lightning-fill text-warning"></i>
              <small>Proses cepat & mudah</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-bell-fill text-warning"></i>
              <small>Notifikasi pembayaran real-time</small>
            </div>
          </div>
        </div>
        <!-- Right Panel -->
        <div class="col-md-7 login-right">
          <h4 class="fw-bold mb-1" style="color:#1a3a6b;">Selamat Datang!</h4>
          <p class="text-muted small mb-4">Masuk menggunakan NIM dan password Anda</p>

          <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
            <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
          </div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold small">NIM / ID Admin</label>
              <div class="input-icon">
                <i class="bi bi-person"></i>
                <input type="text" name="nim" class="form-control"
                       placeholder="Masukkan NIM Anda"
                       value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" required>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold small">Password</label>
              <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" id="passwordField" class="form-control"
                       placeholder="Masukkan password" required>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showPwd">
                <label class="form-check-label small text-muted" for="showPwd">Tampilkan password</label>
              </div>
            </div>
            <button type="submit" class="btn btn-login btn-primary w-100 text-white">
              <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
          </form>

          <div class="mt-4 p-3 bg-light rounded-3">
            <p class="small text-muted mb-1 fw-bold">Aplikasi Oleh Masita tahun 2026</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('showPwd').addEventListener('change', function() {
    const f = document.getElementById('passwordField');
    f.type = this.checked ? 'text' : 'password';
  });
</script>
</body>
</html>
