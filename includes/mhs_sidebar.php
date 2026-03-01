<?php // includes/mhs_sidebar.php ?>
<div class="sidebar d-flex flex-column">
  <div class="logo-area d-flex align-items-center gap-3">
    <div style="width:48px;height:48px;background:var(--secondary);border-radius:10px;display:flex;align-items:center;justify-content:center;">
      <i class="bi bi-mortarboard-fill text-white fs-4"></i>
    </div>
    <div>
      <h6>SIMAK UNASMAN</h6>
      <small>Portal Mahasiswa</small>
    </div>
  </div>

  <nav class="mt-3 flex-grow-1">
    <div class="nav-section">Menu</div>
    <a href="<?= APP_URL ?>/mahasiswa/dashboard.php"
       class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="bi bi-house-fill me-2"></i> Dashboard
    </a>
    <a href="<?= APP_URL ?>/mahasiswa/tagihan.php"
       class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tagihan.php' ? 'active' : '' ?>">
      <i class="bi bi-receipt me-2"></i> Tagihan Saya
    </a>
    <a href="<?= APP_URL ?>/mahasiswa/riwayat.php"
       class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'riwayat.php' ? 'active' : '' ?>">
      <i class="bi bi-clock-history me-2"></i> Riwayat Pembayaran
    </a>
    <a href="<?= APP_URL ?>/mahasiswa/profil.php"
       class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profil.php' ? 'active' : '' ?>">
      <i class="bi bi-person-circle me-2"></i> Profil
    </a>
  </nav>

  <div class="p-3 border-top border-secondary border-opacity-25">
    <div class="d-flex align-items-center gap-2 mb-2">
      <div style="width:32px;height:32px;background:var(--secondary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-person-fill text-white small"></i>
      </div>
      <div>
        <div style="color:#fff;font-size:13px;font-weight:600;"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
        <div style="color:rgba(255,255,255,0.5);font-size:11px;"><?= $_SESSION['nim'] ?? '' ?></div>
      </div>
    </div>
    <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm w-100" style="background:rgba(255,255,255,0.1);color:#fff;">
      <i class="bi bi-box-arrow-right me-1"></i> Logout
    </a>
  </div>
</div>
