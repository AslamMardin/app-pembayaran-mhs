<?php
require_once '../config/config.php';
requireMahasiswa();
$db = getDB();
$sid = $_SESSION['student_id'];

// Handle pay request - create Midtrans transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill_id'])) {
    $bill_id = (int)$_POST['pay_bill_id'];

    // Verify bill belongs to this student
    $bill = $db->prepare("SELECT b.*, s.nim, s.name, s.email, s.phone FROM bills b JOIN students s ON b.student_id=s.id WHERE b.id=? AND b.student_id=?");
    $bill->bind_param('ii', $bill_id, $sid);
    $bill->execute();
    $bill = $bill->get_result()->fetch_assoc();

    if (!$bill || $bill['status'] !== 'unpaid') {
        setFlash('error', 'Tagihan tidak valid atau sudah dibayar.');
        redirect('mahasiswa/tagihan.php');
    }

    $order_id = 'ORDER-' . $bill['bill_code'] . '-' . time();
    $amount = (int)$bill['amount'];

    // Midtrans Snap API Call
    $params = [
        'transaction_details' => [
            'order_id'     => $order_id,
            'gross_amount' => $amount,
        ],
        'customer_details' => [
            'first_name' => $bill['name'],
            'email'      => $bill['email'] ?: 'mahasiswa@unasman.ac.id',
            'phone'      => $bill['phone'] ?: '08000000000',
        ],
        'item_details' => [[
            'id'       => $bill['bill_code'],
            'price'    => $amount,
            'quantity' => 1,
            'name'     => substr($bill['description'], 0, 50),
        ]],
        'callbacks' => [
            'finish' => APP_URL . '/mahasiswa/payment_finish.php',
        ],
    ];

    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($params),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth,
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code === 201 && isset($result['token'])) {
        $snap_token = $result['token'];
        // Save payment record
        $stmt = $db->prepare("INSERT INTO payments (bill_id, order_id, snap_token) VALUES (?,?,?)");
        $stmt->bind_param('iss', $bill_id, $order_id, $snap_token);
        $stmt->execute();

        // Update bill status to pending
        $db->prepare("UPDATE bills SET status='pending' WHERE id=?")->bind_param('i', $bill_id)->execute();
        $stmt2 = $db->prepare("UPDATE bills SET status='pending' WHERE id=?");
        $stmt2->bind_param('i', $bill_id);
        $stmt2->execute();

        // Pass snap_token to frontend
        $snap_token_to_pay = $snap_token;
        $order_id_to_pay = $order_id;
        $amount_to_pay = $amount;
    } else {
        // Sandbox demo mode - simulate token
        $snap_token = 'DEMO-TOKEN-' . time();
        $order_id_now = $order_id;
        $stmt = $db->prepare("INSERT INTO payments (bill_id, order_id, snap_token) VALUES (?,?,?)");
        $stmt->bind_param('iss', $bill_id, $order_id, $snap_token);
        $stmt->execute();
        $stmt2 = $db->prepare("UPDATE bills SET status='pending' WHERE id=?");
        $stmt2->bind_param('i', $bill_id);
        $stmt2->execute();
        setFlash('info', 'Mode Demo: Midtrans API key belum dikonfigurasi. Silakan konfigurasi SERVER_KEY di config.php.');
        redirect('mahasiswa/tagihan.php');
    }
}

$bills = $db->prepare("SELECT * FROM bills WHERE student_id=? ORDER BY created_at DESC");
$bills->bind_param('i', $sid);
$bills->execute();
$bills = $bills->get_result();

$pageTitle = 'Tagihan Saya';
include '../includes/header.php';
include '../includes/mhs_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <h5 class="page-title"><i class="bi bi-receipt me-2"></i>Tagihan Saya</h5>
  </div>
  <div class="page-content">
    <?php if (isset($snap_token_to_pay)): ?>
    <!-- Payment Modal Auto-Show -->
    <div class="alert alert-info d-flex gap-2 align-items-center">
      <i class="bi bi-info-circle-fill"></i>
      Jendela pembayaran sedang dibuka... Jika tidak muncul, klik tombol di bawah.
      <button id="payBtn" class="btn btn-primary btn-sm ms-2">Bayar Sekarang</button>
    </div>
    <script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
    <script>
    function openSnap() {
      window.snap.pay('<?= $snap_token_to_pay ?>', {
        onSuccess: function(result) {
          window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=success';
        },
        onPending: function(result) {
          window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=pending';
        },
        onError: function(result) {
          window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=failed';
        },
        onClose: function() {
          alert('Pembayaran dibatalkan. Anda dapat mencoba kembali.');
        }
      });
    }
    document.getElementById('payBtn').addEventListener('click', openSnap);
    window.addEventListener('load', openSnap);
    </script>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Daftar Tagihan</div>
      <div class="card-body">
        <?php if ($bills->num_rows === 0): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          Tidak ada tagihan
        </div>
        <?php else: ?>
        <div class="row g-3">
          <?php while ($b = $bills->fetch_assoc()): ?>
          <div class="col-md-6">
            <div class="card border h-100 <?= $b['status']==='paid' ? 'border-success' : ($b['status']==='pending' ? 'border-warning' : '') ?>">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <code class="text-muted small"><?= $b['bill_code'] ?></code>
                    <h6 class="mb-0 mt-1"><?= htmlspecialchars($b['description']) ?></h6>
                  </div>
                  <?= getStatusBadge($b['status']) ?>
                </div>
                <div class="row text-center g-2 mb-3">
                  <div class="col-6">
                    <div class="bg-light rounded p-2">
                      <div class="small text-muted">Jumlah</div>
                      <div class="fw-bold text-success"><?= formatRupiah($b['amount']) ?></div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="bg-light rounded p-2">
                      <div class="small text-muted">Jatuh Tempo</div>
                      <div class="fw-bold"><?= $b['due_date'] ? date('d/m/Y', strtotime($b['due_date'])) : '-' ?></div>
                    </div>
                  </div>
                </div>
                <?php if ($b['status'] === 'unpaid'): ?>
                <form method="POST">
                  <input type="hidden" name="pay_bill_id" value="<?= $b['id'] ?>">
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                  </button>
                </form>
                <?php elseif ($b['status'] === 'pending'): ?>
                <div class="alert alert-warning py-2 text-center mb-0 small">
                  <i class="bi bi-hourglass-split me-1"></i>Menunggu konfirmasi pembayaran
                </div>
                <?php elseif ($b['status'] === 'paid'): ?>
                <div class="alert alert-success py-2 text-center mb-0 small">
                  <i class="bi bi-check-circle me-1"></i>Tagihan telah dilunasi
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
