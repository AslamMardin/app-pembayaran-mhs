<?php
require_once '../config/config.php';
requireMahasiswa();
$db = getDB();
$sid = $_SESSION['student_id'];

$snap_token_to_pay = null;
$order_id_to_pay = null;

/* ===============================
   HANDLE PAYMENT REQUEST
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill_id'])) {

    $bill_id = (int)$_POST['pay_bill_id'];

    // Validasi tagihan milik mahasiswa
    $stmt = $db->prepare("
        SELECT b.*, s.nim, s.name, s.email, s.phone
        FROM bills b
        JOIN students s ON b.student_id=s.id
        WHERE b.id=? AND b.student_id=?
    ");
    $stmt->bind_param('ii', $bill_id, $sid);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();

    if (!$bill || $bill['status'] !== 'unpaid') {
        setFlash('error', 'Tagihan tidak valid atau sudah dibayar.');
        redirect('mahasiswa/tagihan.php');
    }

    $order_id = 'ORDER-' . $bill['bill_code'] . '-' . time();
    $amount   = (int)$bill['amount'];

    $params = [
        'transaction_details' => [
            'order_id'     => $order_id,
            'gross_amount' => $amount,
        ],
        'customer_details' => [
            'first_name' => $bill['name'],
            'email'      => $bill['email'] ?: 'mahasiswa@unasman.ac.id',
            'phone'      => $bill['phone'] ?: '081234567890',
        ],
        'item_details' => [[
            'id'       => $bill['bill_code'],
            'price'    => $amount,
            'quantity' => 1,
            'name'     => substr($bill['description'], 0, 50),
        ]],
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

    if ($response === false) {
        die('Curl Error: ' . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['token'])) {

        $snap_token = $result['token'];

        // Simpan ke tabel payments
        $stmtPay = $db->prepare("
            INSERT INTO payments (bill_id, order_id, snap_token)
            VALUES (?,?,?)
        ");
        $stmtPay->bind_param('iss', $bill_id, $order_id, $snap_token);
        $stmtPay->execute();

        // Update status jadi pending
        $stmtUpdate = $db->prepare("UPDATE bills SET status='pending' WHERE id=?");
        $stmtUpdate->bind_param('i', $bill_id);
        $stmtUpdate->execute();

        $snap_token_to_pay = $snap_token;
        $order_id_to_pay   = $order_id;

    } else {
        echo "<pre>";
        print_r($result);
        die("Gagal mendapatkan Snap Token.");
    }
}

/* ===============================
   LOAD TAGIHAN
================================ */
$stmtBills = $db->prepare("
    SELECT * FROM bills
    WHERE student_id=?
    ORDER BY created_at DESC
");
$stmtBills->bind_param('i', $sid);
$stmtBills->execute();
$bills = $stmtBills->get_result();

$pageTitle = 'Tagihan Saya';
include '../includes/header.php';
include '../includes/mhs_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <h5 class="page-title">
      <i class="bi bi-receipt me-2"></i>Tagihan Saya
    </h5>
  </div>

  <div class="page-content">

    <?php if ($snap_token_to_pay): ?>
    <script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
    <script>
    window.addEventListener('load', function () {
        snap.pay('<?= $snap_token_to_pay ?>', {
            onSuccess: function(result) {
                window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=success';
            },
            onPending: function(result) {
                window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=pending';
            },
            onError: function(result) {
                window.location.href = '<?= APP_URL ?>/mahasiswa/payment_finish.php?order_id=<?= $order_id_to_pay ?>&result=failed';
            }
        });
    });
    </script>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Daftar Tagihan</div>
      <div class="card-body">

        <?php if ($bills->num_rows === 0): ?>
          <div class="text-center text-muted py-5">
            Tidak ada tagihan
          </div>
        <?php else: ?>

          <div class="row g-3">
          <?php while ($b = $bills->fetch_assoc()): ?>
            <div class="col-md-6">
              <div class="card border h-100">
                <div class="card-body">
                  <code><?= $b['bill_code'] ?></code>
                  <h6><?= htmlspecialchars($b['description']) ?></h6>
                  <div class="fw-bold text-success mb-2">
                    <?= formatRupiah($b['amount']) ?>
                  </div>
                  <?= getStatusBadge($b['status']) ?>

                  <?php if ($b['status'] === 'unpaid'): ?>
                  <form method="POST" class="mt-3">
                    <input type="hidden" name="pay_bill_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-primary w-100">
                      Bayar Sekarang
                    </button>
                  </form>
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