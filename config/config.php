<?php
// config/config.php
session_start();

define('APP_NAME', 'SIMAK UNASMAN');
define('APP_URL', 'http://localhost/simak');
define('UNIVERSITY_NAME', 'Universitas Al-Asyariah Mandar');

// Midtrans Configuration (Sandbox)
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-SrGY5IayYanPygo-n5sWiGOR');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-d22UfIZaEYgBQzK8');
define('MIDTRANS_IS_PRODUCTION', false);
define('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js');

require_once __DIR__ . '/database.php';

// Helper functions
function redirect($url) {
    header("Location: " . APP_URL . "/" . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMahasiswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('mahasiswa/dashboard.php');
    }
}

function requireMahasiswa() {
    requireLogin();
    if (!isMahasiswa()) {
        redirect('admin/dashboard.php');
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'unpaid'  => '<span class="badge bg-warning text-dark">Belum Bayar</span>',
        'pending' => '<span class="badge bg-info">Pending</span>',
        'paid'    => '<span class="badge bg-success">Lunas</span>',
        'failed'  => '<span class="badge bg-danger">Gagal</span>',
        'expired' => '<span class="badge bg-secondary">Kadaluarsa</span>',
        'success' => '<span class="badge bg-success">Berhasil</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateBillCode() {
    return 'BILL-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
