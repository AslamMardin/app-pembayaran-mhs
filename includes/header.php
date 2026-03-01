<?php
// includes/header.php
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? APP_NAME ?> - <?= UNIVERSITY_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <style>
    :root {
      --primary: #1a3a6b;
      --secondary: #c8a951;
      --accent: #2563eb;
    }
    body { background-color: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
    .sidebar {
      width: 260px; min-height: 100vh;
      background: linear-gradient(180deg, var(--primary) 0%, #0f2447 100%);
      position: fixed; top: 0; left: 0; z-index: 100;
      box-shadow: 4px 0 15px rgba(0,0,0,0.15);
    }
    .sidebar .logo-area {
      padding: 20px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar .logo-area img { width: 48px; height: 48px; }
    .sidebar .logo-area h6 { color: var(--secondary); font-weight: 700; font-size: 13px; margin: 0; line-height: 1.3; }
    .sidebar .logo-area small { color: rgba(255,255,255,0.6); font-size: 11px; }
    .sidebar .nav-link {
      color: rgba(255,255,255,0.75); padding: 10px 20px;
      border-radius: 8px; margin: 2px 10px;
      transition: all 0.2s; font-size: 14px;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
      background: rgba(255,255,255,0.12); color: #fff;
    }
    .sidebar .nav-link i { width: 22px; }
    .sidebar .nav-section {
      color: rgba(255,255,255,0.35); font-size: 10px;
      text-transform: uppercase; letter-spacing: 1px;
      padding: 12px 20px 4px;
    }
    .main-content { margin-left: 260px; min-height: 100vh; }
    .topbar {
      background: #fff; padding: 12px 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 99;
    }
    .topbar .page-title { font-weight: 700; color: var(--primary); font-size: 18px; margin: 0; }
    .page-content { padding: 24px; }
    .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
    .card-header { background: transparent; border-bottom: 1px solid #e9ecef; font-weight: 600; }
    .stat-card { border-radius: 14px; padding: 20px; color: #fff; position: relative; overflow: hidden; }
    .stat-card .stat-icon { font-size: 40px; opacity: 0.25; position: absolute; right: 16px; top: 16px; }
    .stat-card h3 { font-size: 28px; font-weight: 800; margin: 0; }
    .stat-card p { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }
    .btn-primary { background-color: var(--primary); border-color: var(--primary); }
    .btn-primary:hover { background-color: #0f2447; border-color: #0f2447; }
    .table thead th { background-color: #f8f9fa; color: var(--primary); font-weight: 600; font-size: 13px; }
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main-content { margin-left: 0; }
    }
  </style>
</head>
<body>
<?php if ($flash): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
  <div class="toast show align-items-center text-white bg-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info') ?> border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"><?= $flash['message'] ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>
