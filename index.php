<?php
require __DIR__ . '/src/db.php';

session_start();

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = sha1($ip . '|' . $ua);

$error = '';

// Define available services
$services = [
  'btc' => 'Bitcoin (BTC)',
  'eth' => 'Ethereum (ETH)',
  'ltc' => 'Litecoin (LTC)',
  'bch' => 'Bitcoin Cash (BCH)',
  'doge' => 'Dogecoin (DOGE)',
  'usdt' => 'Tether (USDT)',
  'xrp' => 'XRP (XRP)',
  'bnb' => 'Binance Coin (BNB)',
  'ada' => 'Cardano (ADA)',
  'sol' => 'Solana (SOL)'
];

// auth handling
$action = $_GET['action'] ?? null;
$selectedService = $_GET['service'] ?? null;
if ($selectedService && !array_key_exists($selectedService, $services)) {
  $selectedService = null;
}

// Logout
if ($action === 'logout') {
  unset($_SESSION['user']);
  header('Location: index.php');
  exit;
}

// Signup or login POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emailRaw = $_POST['email'] ?? '';
  $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
  // If this POST is for auth (signup/login)
  if (isset($_POST['auth_action'])) {
    $authAct = $_POST['auth_action'];
    if ($authAct === 'signup') {
      $password = $_POST['password'] ?? '';
      if (!$email || strlen($password) < 4) {
        $error = 'Provide valid email and password (min 4 chars).';
      } else {
        $res = create_user($email, $password, $device, $ip);
        if (is_string($res)) {
          $error = $res;
        } else {
          $_SESSION['user'] = $res;
          header('Location: index.php');
          exit;
        }
      }
    } elseif ($authAct === 'login') {
      $password = $_POST['password'] ?? '';
      if (!$email || $password === '') {
        $error = 'Provide email and password.';
      } else {
        $u = verify_user_credentials($email, $password);
        if (!$u) {
          $error = 'Invalid credentials.';
        } else {
          $_SESSION['user'] = $u;
          header('Location: index.php');
          exit;
        }
      }
    }
  } else {
    // Receipt generation POST (requires login)
    $servicePost = $_POST['service'] ?? $selectedService ?? null;
    if (!isset($_SESSION['user'])) {
      $error = 'You must be logged in to generate receipts.';
    } elseif (!$email) {
      $error = 'Please provide a valid email address.';
    } elseif (!$servicePost || !array_key_exists($servicePost, $services)) {
      $error = 'Please select a valid service.';
    } else {
      $res = add_or_verify_user($email, $device, $ip);
      if ($res !== true) {
        $error = $res;
      } else {
        // create receipt for selected service
        $from = trim($_POST['from'] ?? '');
        $to = trim($_POST['to'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $txid = trim($_POST['txid'] ?? '');
        $memo = trim($_POST['memo'] ?? '');

        $receipt = [
          'id' => uniqid('r_'),
          'service' => $servicePost,
          'service_name' => $services[$servicePost],
          'email' => $email,
          'user_id' => $_SESSION['user']['id'] ?? null,
          'device' => $device,
          'ip' => $ip,
          'from' => $from,
          'to' => $to,
          'amount' => $amount,
          'txid' => $txid,
          'memo' => $memo,
          'created_at' => date('c')
        ];
        add_receipt($receipt);
        header('Location: index.php?receipt=' . $receipt['id']);
        exit;
      }
    }
  }
}

$viewReceipt = null;
if (!empty($_GET['receipt'])) {
  $viewReceipt = find_receipt($_GET['receipt']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receipto — BTC Receipt Generator</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#f97316' }
        }
      }
    }
  </script>
</head>
<body class="bg-orange-50 min-h-screen text-gray-800">
  <div class="max-w-3xl mx-auto p-6">
    <header class="mb-6">
      <h1 class="text-3xl font-semibold text-brand">Receipto</h1>
      <p class="text-sm text-gray-600">BTC receipt generator — test interface</p>
    </header>

    <?php if ($error): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <?php if ($viewReceipt): ?>
      <section class="mb-6">
        <div id="receipt-card" class="max-w-xl mx-auto bg-white shadow-lg rounded p-6 font-sans text-gray-800" style="border-top:6px solid #f97316">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h2 class="text-2xl font-bold">Receipto</h2>
              <div class="text-sm text-gray-600">Transaction Receipt</div>
            </div>
            <div class="text-right text-sm text-gray-600">
              <div>Receipt ID</div>
              <div class="font-mono text-xs"><?=$viewReceipt['id']?></div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <div class="text-xs text-gray-500">Service</div>
              <div class="font-medium"><?=htmlspecialchars($viewReceipt['service_name'] ?? ($viewReceipt['service'] ?? ''))?></div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Date</div>
              <div class="font-medium"><?=htmlspecialchars($viewReceipt['created_at'])?></div>
            </div>
          </div>

          <div class="border-t border-b py-3 mb-3">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="text-xs text-gray-500">From</div>
                <div class="font-mono text-sm"><?=htmlspecialchars($viewReceipt['from'])?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">To</div>
                <div class="font-mono text-sm"><?=htmlspecialchars($viewReceipt['to'])?></div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <div class="text-xs text-gray-500">Amount</div>
            <div class="text-2xl font-semibold"><?=number_format($viewReceipt['amount'],8)?> <span class="text-sm text-gray-600">units</span></div>
          </div>

          <div class="flex items-start gap-4">
            <div class="flex-1">
              <div class="text-xs text-gray-500">Transaction ID (TXID)</div>
              <div class="font-mono text-sm break-all"><?=htmlspecialchars($viewReceipt['txid'])?></div>
              <?php if (!empty($viewReceipt['memo'])): ?>
                <div class="mt-2 text-xs text-gray-500">Memo</div>
                <div class="text-sm text-gray-700"><?=nl2br(htmlspecialchars($viewReceipt['memo']))?></div>
              <?php endif; ?>
            </div>
            <div class="w-32 text-center">
              <?php $qr = urlencode($viewReceipt['txid'] ?? $viewReceipt['id']); ?>
              <img alt="QR" src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=<?=$qr?>" class="mx-auto bg-white p-1 rounded border">
              <div class="text-xs text-gray-500 mt-2">Scan TXID</div>
            </div>
          </div>

          <div class="mt-6 flex gap-3">
            <button id="download-png" class="px-4 py-2 bg-brand text-white rounded">Download PNG</button>
            <button id="print-receipt" class="px-4 py-2 border rounded">Print</button>
            <a href="index.php" class="ml-auto text-sm text-gray-600">Create another</a>
          </div>
        </div>
      </section>

      <section class="bg-white rounded shadow p-6">
        <h3 class="font-semibold">Additional info (post-generation)</h3>
        <p class="text-sm text-gray-600 mb-3">You can add more details about this receipt below.</p>
        <form method="post" action="">
          <input type="hidden" name="email" value="<?=htmlspecialchars($viewReceipt['email'])?>">
          <input type="hidden" name="from" value="<?=htmlspecialchars($viewReceipt['from'])?>">
          <input type="hidden" name="to" value="<?=htmlspecialchars($viewReceipt['to'])?>">
          <input type="hidden" name="amount" value="<?=htmlspecialchars($viewReceipt['amount'])?>">
          <input type="hidden" name="txid" value="<?=htmlspecialchars($viewReceipt['txid'])?>">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="memo" class="w-full border rounded p-2" rows="3"><?=htmlspecialchars($viewReceipt['memo'])?></textarea>
          </div>
          <div>
            <button type="submit" class="px-4 py-2 bg-brand text-white rounded">Save notes (re-generate)</button>
          </div>
        </form>
      </section>

    <?php else: ?>
      <?php if (!isset($_SESSION['user'])): ?>
        <section class="max-w-md mx-auto bg-white rounded shadow p-6 mb-6">
          <h2 class="text-lg font-medium text-brand mb-4">Sign in or create an account</h2>
          <?php if ($error): ?>
            <div class="mb-3 p-2 bg-red-100 text-red-800 rounded"><?=htmlspecialchars($error)?></div>
          <?php endif; ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <form method="post" class="space-y-3">
              <input type="hidden" name="auth_action" value="login">
              <div>
                <label class="block text-sm">Email</label>
                <input name="email" type="email" required class="mt-1 block w-full border rounded p-2">
              </div>
              <div>
                <label class="block text-sm">Password</label>
                <input name="password" type="password" required class="mt-1 block w-full border rounded p-2">
              </div>
              <div>
                <button type="submit" class="px-4 py-2 bg-brand text-white rounded">Sign in</button>
              </div>
            </form>

            <form method="post" class="space-y-3">
              <input type="hidden" name="auth_action" value="signup">
              <div>
                <label class="block text-sm">Email</label>
                <input name="email" type="email" required class="mt-1 block w-full border rounded p-2">
              </div>
              <div>
                <label class="block text-sm">Password</label>
                <input name="password" type="password" minlength="4" required class="mt-1 block w-full border rounded p-2">
              </div>
              <div>
                <button type="submit" class="px-4 py-2 bg-brand text-white rounded">Create account</button>
              </div>
            </form>
          </div>
        </section>

      <?php elseif (!$selectedService): ?>
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          <?php foreach ($services as $key => $label): ?>
            <a href="?service=<?=$key?>" class="block p-4 bg-white rounded shadow hover:shadow-md border-l-4 border-brand">
              <h3 class="font-semibold text-brand mb-1"><?=$label?></h3>
              <p class="text-sm text-gray-600">Generate a receipt for <?=$label?></p>
            </a>
          <?php endforeach; ?>
        </section>
      <?php else: ?>

        <section class="bg-white rounded shadow p-6 mb-6">
          <h2 class="text-lg font-medium text-brand mb-4"><?=htmlspecialchars($services[$selectedService])?> — Generator</h2>
          <form method="post" class="space-y-4">
            <input type="hidden" name="service" value="<?=htmlspecialchars($selectedService)?>">
            <div>
              <label class="block text-sm font-medium text-gray-700">Your email</label>
              <input name="email" type="email" required class="mt-1 block w-full border rounded p-2" placeholder="you@example.com">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">From (address/name)</label>
                <input name="from" type="text" class="mt-1 block w-full border rounded p-2">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">To (address/name)</label>
                <input name="to" type="text" class="mt-1 block w-full border rounded p-2">
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Amount</label>
                <input name="amount" type="number" step="0.00000001" class="mt-1 block w-full border rounded p-2">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Transaction ID (TXID)</label>
                <input name="txid" type="text" class="mt-1 block w-full border rounded p-2">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Memo / Notes</label>
              <textarea name="memo" rows="3" class="mt-1 block w-full border rounded p-2"></textarea>
            </div>
            <div class="flex items-center gap-3">
              <button type="submit" class="px-4 py-2 bg-brand text-white rounded">Generate Receipt</button>
              <a href="index.php" class="text-sm text-gray-600">Back to services</a>
            </div>
          </form>
        </section>

      <?php endif; ?>

      <section class="text-sm text-gray-600">
        <p>Device/IP rule: each device/IP can have only one email attached. If this device already registered a different email, the form will be blocked.</p>
      </section>

    <?php endif; ?>

    <footer class="mt-8 text-xs text-gray-500">Receipto — JSON DB demo</footer>
  </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
  document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'download-png') {
      const el = document.getElementById('receipt-card');
      if (!el) return;
      html2canvas(el, {scale:2, useCORS:true}).then(canvas => {
        const link = document.createElement('a');
        link.download = 'receipt-<?=(isset($viewReceipt)?$viewReceipt['id']:'')?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }
    if (e.target && e.target.id === 'print-receipt') {
      const el = document.getElementById('receipt-card');
      if (!el) return;
      const w = window.open('', '_blank');
      w.document.write('<html><head><title>Print Receipt</title>');
      w.document.write('<link href="https://cdn.tailwindcss.com" rel="stylesheet">');
      w.document.write('</head><body>');
      w.document.write(el.outerHTML);
      w.document.write('</body></html>');
      w.document.close();
      w.focus();
      setTimeout(()=>{ w.print(); w.close(); }, 500);
    }
  });
</script>
</html>
</html>
