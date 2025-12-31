<?php
require __DIR__ . '/src/db.php';

session_start();

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
// Device logic: Prioritize client-side ID, fallback to IP hash
$device = $_POST['device_id'] ?? sha1($ip . '|' . $ua);

$error = '';

/**
 * Service Configuration System
 */
function get_services_config() {
    return [
        'email_proof' => [
            'label' => 'Email Proof',
            'template' => 'generic',
            'fields' => [
                ['name' => 'sender', 'label' => 'Sender Name', 'type' => 'text'],
                ['name' => 'subject', 'label' => 'Subject', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
            ]
        ],
        'binance_deposit' => [
            'label' => 'Binance Deposit',
            'template' => 'binance_deposit',
            'fields' => [
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => '0.00000001', 'required' => true],
                ['name' => 'currency', 'label' => 'Currency', 'type' => 'text', 'placeholder' => 'BTC', 'default' => 'BTC'],
                ['name' => 'network', 'label' => 'Network', 'type' => 'text', 'placeholder' => 'Bitcoin'],
                ['name' => 'wallet', 'label' => 'Wallet', 'type' => 'select', 'options' => ['Spot Wallet', 'Funding Wallet', 'Margin Wallet', 'Futures Wallet']],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'txid', 'label' => 'TxID', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
                ['name' => 'confirmations', 'label' => 'Confirmations', 'type' => 'text', 'default' => '1/1'], // Input or static text
            ]
        ],
        'binance_withdrawal_completed' => [
            'label' => 'Binance Withdrawal',
            'template' => 'binance_withdrawal',
            'status' => 'completed',
            'fields' => [
                ['name' => 'amount', 'label' => 'Withdrawal Amount', 'type' => 'number', 'step' => '0.00000001', 'required' => true],
                ['name' => 'network_fee', 'label' => 'Network Fee', 'type' => 'text', 'placeholder' => '0.00005 BTC'],
                ['name' => 'currency', 'label' => 'Currency', 'type' => 'text', 'placeholder' => 'BTC', 'default' => 'BTC'],
                ['name' => 'network', 'label' => 'Network', 'type' => 'text', 'placeholder' => 'Bitcoin'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'txid', 'label' => 'TxID', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
            ]
        ],
        'binance_withdrawal_processing' => [
            'label' => 'Binance Withdrawal (Processing)',
            'template' => 'binance_withdrawal',
            'status' => 'processing',
            'fields' => [
                 ['name' => 'amount', 'label' => 'Withdrawal Amount', 'type' => 'number', 'step' => '0.00000001', 'required' => true],
                ['name' => 'network_fee', 'label' => 'Network Fee', 'type' => 'text'],
                ['name' => 'currency', 'label' => 'Currency', 'type' => 'text', 'default' => 'BTC'],
                ['name' => 'network', 'label' => 'Network', 'type' => 'text'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'txid', 'label' => 'TxID', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
            ]
        ],
        // Placeholders for other requested services
// ... (User requested to duplicate fields for the '2' variants)
        'binance_completed_2' => [
            'label' => 'Binance Completed 2', 
            'template' => 'binance_deposit', 
            'fields' => [
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => '0.00000001', 'required' => true],
                ['name' => 'currency', 'label' => 'Currency', 'type' => 'text', 'placeholder' => 'BTC', 'default' => 'BTC'],
                ['name' => 'network', 'label' => 'Network', 'type' => 'text', 'placeholder' => 'Bitcoin'],
                ['name' => 'wallet', 'label' => 'Wallet', 'type' => 'select', 'options' => ['Spot Wallet', 'Funding Wallet', 'Margin Wallet', 'Futures Wallet']],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'txid', 'label' => 'TxID', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
                ['name' => 'confirmations', 'label' => 'Confirmations', 'type' => 'text', 'default' => '1/1'],
            ]
        ],
        'binance_withdrawal_processing_2' => [
            'label' => 'Binance Withdrawal (Proc 2)', 
            'template' => 'binance_withdrawal', 
            'status' => 'processing', 
            'fields' => [
                ['name' => 'amount', 'label' => 'Withdrawal Amount', 'type' => 'number', 'step' => '0.00000001', 'required' => true],
                ['name' => 'network_fee', 'label' => 'Network Fee', 'type' => 'text'],
                ['name' => 'currency', 'label' => 'Currency', 'type' => 'text', 'default' => 'BTC'],
                ['name' => 'network', 'label' => 'Network', 'type' => 'text'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'txid', 'label' => 'TxID', 'type' => 'text'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'datetime-local'],
            ]
        ],
        'blockchain_btc' => ['label' => 'Blockchain BTC', 'template' => 'generic', 'fields' => [['name'=>'amount','label'=>'Amount','type'=>'number', 'required'=>true], ['name'=>'sender', 'label'=>'From', 'type'=>'text'], ['name'=>'receiver', 'label'=>'To', 'type'=>'text'], ['name'=>'txid', 'label'=>'Hash', 'type'=>'text'], ['name'=>'date', 'label'=>'Date', 'type'=>'datetime-local']]],
        'blockchain_usdt' => ['label' => 'Blockchain USDT', 'template' => 'generic', 'fields' => [['name'=>'amount','label'=>'Amount','type'=>'number', 'required'=>true], ['name'=>'sender', 'label'=>'From', 'type'=>'text'], ['name'=>'receiver', 'label'=>'To', 'type'=>'text'], ['name'=>'txid', 'label'=>'Hash', 'type'=>'text'], ['name'=>'date', 'label'=>'Date', 'type'=>'datetime-local']]],
        'coinbase_receive' => ['label' => 'Coinbase Receive', 'template' => 'generic', 'fields' => [['name'=>'amount','label'=>'Amount','type'=>'number', 'required'=>true], ['name'=>'currency', 'label'=>'Asset', 'type'=>'text', 'default'=>'BTC']]],
    ];
}

$servicesConfig = get_services_config();
$services = array_map(fn($s) => $s['label'], $servicesConfig);

// auth handling
$action = $_GET['action'] ?? null;
$selectedService = $_GET['service'] ?? null;

if ($selectedService && !array_key_exists($selectedService, $servicesConfig)) {
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
    
    // ... (Keep existing Auth Logic) ...
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
            // Dev check
          $devCheck = verify_user_with_device_check($email, $device);
          if (!$devCheck) {
            $error = 'This email is registered from a different device/IP.';
          } else {
            $_SESSION['user'] = $u;
            header('Location: index.php');
            exit;
          }
        }
      }
    }

  } else {
    // Receipt generation POST
    $servicePost = $_POST['service'] ?? $selectedService ?? null;
    if (!isset($_SESSION['user'])) {
      $error = 'You must be logged in to generate receipts.';
    } elseif (!$email) {
      $error = 'Please provide a valid email address.';
    } elseif (!$servicePost || !array_key_exists($servicePost, $servicesConfig)) {
      $error = 'Please select a valid service.';
    } else {
      $res = add_or_verify_user($email, $device, $ip, false);
      if ($res !== true) {
        $error = $res;
      } else {
        // Collect Dynamic Data
        $config = $servicesConfig[$servicePost];
        $receiptData = [
          'id' => uniqid('r_'),
          'service' => $servicePost,
          'service_name' => $config['label'],
          'email' => $email,
          'user_id' => $_SESSION['user']['id'] ?? null,
          'device' => $device,
          'ip' => $ip,
          'created_at' => date('c'),
          // Store all custom fields in 'details'
          'details' => [] 
        ];

        // Process fields defined in config
        foreach ($config['fields'] as $field) {
            $key = $field['name'];
            $val = $_POST[$key] ?? '';
            // Store specific standard keys at top level if needed for DB indexing, else in details
            // For now, let's store standard ones at top too for backward compat if needed, but mainly 'details'
            if (in_array($key, ['amount', 'from', 'to', 'txid', 'memo'])) {
                $receiptData[$key] = $val;
            }
            $receiptData['details'][$key] = $val;
        }
        
        // Manual helpers for old schema compat
        $receiptData['amount'] = $receiptData['details']['amount'] ?? 0;
        $receiptData['txid'] = $receiptData['details']['txid'] ?? '';

        add_receipt($receiptData);
        header('Location: index.php?receipt=' . $receiptData['id']);
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
  <title>Receipt Generator</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#f97316', binance: '#F0B90B', binanceDark: '#1E2329' }
        }
      }
    }
  </script>
  <style>
    /* Custom fonts or overrides */
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 flex flex-col">
  <div class="max-w-3xl mx-auto p-4 flex-grow w-full">
    <header class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Receipto</h1>
        <p class="text-xs text-gray-500">Service Receipt Generator</p>
      </div>
      <?php if(isset($_SESSION['user'])): ?>
        <a href="?action=logout" class="text-sm text-red-500 hover:underline">Logout</a>
      <?php endif; ?>
    </header>

    <?php if ($error): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <?php if ($viewReceipt): ?>
        <!-- RECEIPT VIEW AREA MATCHED -->
        <?php 
            $svcKey = $viewReceipt['service'];
            $svcConfig = $servicesConfig[$svcKey] ?? ['template'=>'generic'];
            $tpl = $svcConfig['template'] ?? 'generic';
            $details = $viewReceipt['details'] ?? [];
            // Back-compat
            if(empty($details)) {
                $details = $viewReceipt; 
            }
        ?>

        <?php if ($tpl === 'binance_deposit'): ?>
            <!-- BINANCE DEPOSIT TEMPLATE -->
             <section class="mb-6 mx-auto max-w-[400px] bg-white min-h-[800px] flex flex-col font-sans relative shadow-2xl">
                 <!-- Fake Message Bar -->
                 <div class="h-6 w-full"></div>

                 <!-- Header -->
                 <div class="flex justify-between items-center px-4 py-4">
                     <svg class="w-6 h-6 text-gray-600 cursor-pointer" onclick="window.location.href='index.php'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                     <h1 class="text-[17px] font-semibold text-gray-900">Deposit Details</h1>
                     <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2v-8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                 </div>

                 <!-- Amount & Status -->
                 <div class="mt-8 text-center pb-8 border-b border-gray-100 px-6">
                     <div class="text-[32px] font-bold text-green-500 tracking-tighter">
                         +<?=htmlspecialchars($details['amount']??'0')?> <?=htmlspecialchars($details['currency']??'BTC')?>
                     </div>
                     <div class="flex items-center justify-center gap-2 mt-2">
                         <div class="bg-green-500 rounded-full p-0.5">
                             <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                         </div>
                         <span class="text-green-500 font-medium text-[15px]">Completed</span>
                     </div>
                     <div class="mt-6"></div>
                 </div>

                 <!-- Details List -->
                 <div class="px-5 py-6 space-y-7 flex-grow">
                      <!-- Network -->
                     <div class="flex justify-between items-start">
                         <span class="text-[14px] text-gray-400">Network</span>
                         <div class="flex items-center gap-2">
                            <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['network']??'')?></span>
                            <?php if(!empty($details['confirmations'])): ?>
                                <span class="text-[12px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded"><?=htmlspecialchars($details['confirmations'])?></span>
                            <?php endif; ?>
                         </div>
                     </div>
                     
                     <!-- Wallet -->
                     <div class="flex justify-between items-start pt-1">
                         <span class="text-[14px] text-gray-400">Wallet</span>
                         <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['wallet']??'Spot Wallet')?></span>
                     </div>

                     <!-- Address -->
                     <div class="flex justify-between items-start pt-1">
                         <span class="text-[14px] text-gray-400">Address</span>
                         <div class="flex items-start gap-2 max-w-[75%]">
                             <span class="text-[14px] text-[#1E2329] break-all text-right leading-tight font-medium"><?=htmlspecialchars($details['address']??'')?></span>
                             <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                         </div>
                     </div>

                     <!-- TxID -->
                     <div class="flex justify-between items-start pt-1">
                         <span class="text-[14px] text-gray-400">Txid</span>
                         <div class="flex items-start gap-2 max-w-[75%]">
                             <span class="text-[14px] text-[#1E2329] break-all text-right leading-tight underline decoration-gray-300"><?=htmlspecialchars($details['txid']??'')?></span>
                             <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                         </div>
                     </div>

                     <!-- Date -->
                     <div class="flex justify-between items-start pt-1">
                         <span class="text-[14px] text-gray-400">Date</span>
                         <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['date']??date('Y-m-d H:i:s'))?></span>
                     </div>
                 </div>

                 <!-- Buttons -->
                 <div class="p-5 flex gap-4 mt-auto mb-6">
                     <button class="flex-1 bg-[#FCD535] text-[#1E2329] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-[#F0B90B]">Buy Crypto</button>
                     <button class="flex-1 bg-[#EAECEF] text-[#474D57] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-gray-200">Share</button>
                 </div>
             </section>

        <?php elseif ($tpl === 'binance_withdrawal'): ?>
            <!-- BINANCE WITHDRAWAL TEMPLATE -->
            <?php 
                $status = $svcConfig['status'] ?? 'completed';
                $isCompleted = $status === 'completed';
            ?>
            
            <?php if ($isCompleted): ?>
                <!-- COMPLETED VIEW (Image 0 MATCH) -->
                <section class="mb-6 mx-auto max-w-[400px] bg-white min-h-[800px] flex flex-col font-sans relative shadow-2xl">
                    <!-- Fake Message Bar -->
                     <div class="h-6 w-full"></div>

                    <!-- Header -->
                    <div class="flex justify-between items-center px-4 py-4">
                        <svg class="w-6 h-6 text-gray-600" onclick="window.location.href='index.php'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <h1 class="text-[17px] font-semibold text-gray-900">Withdrawal Details</h1>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2v-8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> 
                    </div>

                    <!-- Amount & Status -->
                    <div class="mt-8 text-center pb-8 border-b border-gray-100 px-6">
                        <div class="text-[32px] font-bold text-[#1E2329] tracking-tighter">
                            -<?=htmlspecialchars($details['amount']??'10000')?> <?=htmlspecialchars($details['currency']??'USDT')?>
                        </div>
                        <div class="flex items-center justify-center gap-2 mt-2">
                            <div class="bg-green-500 rounded-full p-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-green-500 font-medium text-[15px]">Completed</span>
                        </div>
                        <div class="mt-6 text-[13px] text-gray-400 leading-relaxed px-2">
                            Crypto transferred out of Binance account. Please contact the recipient platform for your transaction receipt.
                        </div>
                        <div class="mt-3">
                            <span class="text-[13px] text-[#F0B90B] cursor-pointer">Why hasn't my withdrawal arrived?</span>
                        </div>
                    </div>

                    <!-- Details List -->
                    <div class="px-5 py-6 space-y-7 flex-grow">
                        <!-- Network -->
                        <div class="flex justify-between items-start">
                            <span class="text-[14px] text-gray-400">Network</span>
                            <span class="text-[12px] bg-yellow-50 text-[#F0B90B] px-1.5 py-0.5 rounded font-medium"><?=htmlspecialchars($details['network']??'BSC')?></span>
                        </div>

                        <!-- Address -->
                        <div class="flex justify-between items-start pt-1">
                            <span class="text-[14px] text-gray-400">Address</span>
                            <div class="flex items-start gap-2 max-w-[75%]">
                                <span class="text-[14px] text-[#1E2329] break-all text-right leading-tight font-medium"><?=htmlspecialchars($details['address']??'')?></span>
                                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>

                        <!-- TxID -->
                        <div class="flex justify-between items-start pt-1">
                            <span class="text-[14px] text-gray-400">Txid</span>
                            <div class="flex items-start gap-2 max-w-[75%]">
                                <span class="text-[14px] text-[#1E2329] break-all text-right leading-tight underline decoration-gray-300"><?=htmlspecialchars($details['txid']??'')?></span>
                                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>

                        <!-- Withdrawal Amount -->
                         <div class="flex justify-between items-start pt-1">
                            <span class="text-[14px] text-gray-400">Withdrawal Amount</span>
                            <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['amount']??'')?> <?=htmlspecialchars($details['currency']??'')?></span>
                        </div>

                        <!-- Network Fee -->
                        <div class="flex justify-between items-start pt-1">
                            <span class="text-[14px] text-gray-400">Network Fee</span>
                            <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['network_fee']??'0 USDT')?></span>
                        </div>

                        <!-- Date -->
                        <div class="flex justify-between items-start pt-1">
                            <span class="text-[14px] text-gray-400">Date</span>
                            <span class="text-[14px] text-[#1E2329] font-medium"><?=htmlspecialchars($details['date']??date('Y-m-d H:i:s'))?></span>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="p-5 flex gap-4 mt-auto mb-6">
                        <button class="flex-1 bg-[#EAECEF] text-[#474D57] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-gray-200">Scam Report</button>
                        <button class="flex-1 bg-[#EAECEF] text-[#474D57] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-gray-200">Save Address</button>
                    </div>
                </section>

            <?php else: ?>
                <!-- PROCESSING VIEW (Image 1 MATCH) -->
                 <section class="mb-6 mx-auto max-w-[400px] bg-white min-h-[800px] flex flex-col font-sans relative shadow-2xl">
                    <!-- Fake Header (Back Arrow only) -->
                     <div class="flex justify-between items-center px-4 py-4">
                        <svg class="w-6 h-6 text-gray-600" onclick="window.location.href='index.php'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </div>

                    <div class="flex-grow flex flex-col items-center justify-center -mt-20 px-8">
                        <!-- Hourglass Icon (Approximation) -->
                        <div class="w-16 h-16 mb-6">
                             <svg viewBox="0 0 24 24" fill="#F0B90B" class="w-full h-full"><path d="M6 2h12v6l-4 4 4 4v6H6v-6l4-4-4-4V2zm10 14.5l-3.5-3.5 3.5-3.5V4.1H8v2.4L11.5 10 8 13.5v2.4h8v-1.4z"/></svg> 
                        </div>
                        
                        <h2 class="text-[20px] font-bold text-[#1E2329] mb-2">Withdrawal Processing</h2>
                        <div class="text-[28px] font-bold text-[#1E2329] mb-4">
                            <?=htmlspecialchars($details['amount']??'10000')?> <?=htmlspecialchars($details['currency']??'USDT')?>
                        </div>
                        
                        <div class="text-center space-y-1">
                            <p class="text-[13px] text-gray-400">Estimated completion time: <?=date('Y-m-d H:i:s', strtotime('+30 minutes'))?></p>
                            <p class="text-[13px] text-gray-400">You will receive an email once withdrawal is completed.</p>
                            <p class="text-[13px] text-gray-400">View history for latest updates.</p>
                        </div>
                    </div>

                    <!-- Bottom Button -->
                    <div class="p-6">
                         <button class="w-full bg-[#FCD535] text-[#1E2329] py-3.5 rounded-lg text-[16px] font-semibold hover:bg-[#F0B90B] transition-colors">View History</button>
                    </div>

                 </section>

            <?php endif; ?>

        <?php else: ?>
            <!-- GENERIC DEFAULT APP-LIKE TEMPLATE -->
             <section class="mb-6 mx-auto max-w-[400px] bg-white min-h-[800px] flex flex-col font-sans relative shadow-2xl">
                 <!-- Fake Message Bar -->
                 <div class="h-6 w-full"></div>

                 <!-- Header -->
                 <div class="flex justify-between items-center px-4 py-4">
                     <svg class="w-6 h-6 text-gray-600 cursor-pointer" onclick="window.location.href='index.php'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                     <h1 class="text-[17px] font-semibold text-gray-900">Transaction Details</h1>
                     <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2v-8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                 </div>

                 <div class="mt-4 px-6">
                     <h2 class="text-xl font-bold mb-4 border-b pb-2"><?=htmlspecialchars($viewReceipt['service_name'])?></h2>
                 </div>

                 <!-- Details List -->
                 <div class="px-5 py-2 space-y-6 flex-grow">
                        <?php foreach($details as $k => $v): if($k=='details' || $k=='id' || is_array($v))continue; ?>
                             <div class="flex justify-between items-start pt-1">
                                <span class="text-[14px] text-gray-400 capitalize"><?=htmlspecialchars(str_replace('_',' ',$k))?></span>
                                <div class="flex items-start gap-2 max-w-[70%]">
                                    <span class="text-[14px] text-[#1E2329] break-all text-right leading-tight font-medium"><?=htmlspecialchars($v)?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                 </div>

                 <!-- Buttons -->
                 <div class="p-5 flex gap-4 mt-auto mb-6">
                     <button class="flex-1 bg-[#EAECEF] text-[#474D57] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-gray-200">Share</button>
                     <button class="flex-1 bg-[#EAECEF] text-[#474D57] py-3 rounded-lg text-[15px] font-medium font-sans hover:bg-gray-200">Save Image</button>
                 </div>
            </section>
        <?php endif; ?>

        <!-- ACTIONS -->
        <div class="flex justify-center gap-4 mt-6">
            <button id="download-png" class="px-6 py-2 bg-gray-800 text-white rounded shadow">Download PNG</button>
            <a href="index.php" class="px-6 py-2 border bg-white rounded shadow">Create New</a>
        </div>


    <?php else: ?>
    
      <!-- SELECTION & FORM AREA -->
      <?php if (!isset($_SESSION['user'])): ?>
         <!-- LOGIN / REGISTER SCREEN -->
         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             
             <!-- Login -->
             <section class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-bold mb-4">Login</h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="auth_action" value="login">
                    <input name="email" type="email" placeholder="Email" required class="w-full border p-2 rounded">
                    <input name="password" type="password" placeholder="Password" required class="w-full border p-2 rounded">
                    <button class="w-full bg-blue-600 text-white py-2 rounded">Login</button>
                </form>
             </section>

             <!-- Register -->
             <section class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-bold mb-4">Sign Up</h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="auth_action" value="signup">
                    <input name="email" type="email" placeholder="Email" required class="w-full border p-2 rounded">
                    <input name="password" type="password" placeholder="Password (min 4 chars)" minlength="4" required class="w-full border p-2 rounded">
                    <button class="w-full bg-green-600 text-white py-2 rounded">Create Account</button>
                </form>
             </section>
             
         </div>

      <?php elseif (!$selectedService): ?>
        <!-- SERVICE SELECTION -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($servicesConfig as $key => $conf): ?>
                <a href="?service=<?=$key?>" class="block p-4 bg-white rounded shadow hover:shadow-md border-l-4 border-brand">
                    <h3 class="font-bold text-gray-800"><?=$conf['label']?></h3>
                    <p class="text-xs text-gray-500">Create receipt</p>
                </a>
            <?php endforeach; ?>
        </div>

      <?php else: ?>
        <!-- DYNAMIC FORM -->
        <?php $currentConfig = $servicesConfig[$selectedService]; ?>
        <section class="bg-white rounded shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900"><?=htmlspecialchars($currentConfig['label'])?></h2>
                <a href="index.php" class="text-sm text-blue-500">Change Service</a>
            </div>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="service" value="<?=htmlspecialchars($selectedService)?>">
                <input type="hidden" name="email" value="<?=htmlspecialchars($_SESSION['user']['email'])?>">

                <!-- Render Configured Fields -->
                <?php if (!empty($currentConfig['fields'])): ?>
                    <?php foreach($currentConfig['fields'] as $field): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?=htmlspecialchars($field['label'])?></label>
                            <?php if ($field['type'] === 'select'): ?>
                                <select name="<?=htmlspecialchars($field['name'])?>" class="w-full border rounded p-2">
                                    <?php foreach($field['options'] as $opt): ?>
                                        <option value="<?=htmlspecialchars($opt)?>"><?=htmlspecialchars($opt)?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input 
                                    type="<?=htmlspecialchars($field['type'])?>" 
                                    name="<?=htmlspecialchars($field['name'])?>" 
                                    class="w-full border rounded p-2"
                                    placeholder="<?=htmlspecialchars($field['placeholder']??'')?>"
                                    step="<?=htmlspecialchars($field['step']??'any')?>"
                                    value="<?=htmlspecialchars($field['default']??'')?>"
                                    <?=($field['required']??false)?'required':''?>
                                >
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">No fields configured for this service.</p>
                <?php endif; ?>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-brand text-white py-3 rounded font-bold shadow">Generate</button>
                </div>
            </form>
        </section>
      <?php endif; ?>

    <?php endif; ?>
  </div>
  
  <script>
    (function() {
      let devId = localStorage.getItem('receipto_device_id');
      if (!devId) {
        devId = 'dev_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('receipto_device_id', devId);
      }
      function injectDeviceId() {
        document.querySelectorAll('form').forEach(f => {
            if (!f.querySelector('input[name="device_id"]')) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'device_id';
                inp.value = devId;
                f.appendChild(inp);
            }
        });
      }
      document.addEventListener('DOMContentLoaded', injectDeviceId);
      setInterval(injectDeviceId, 1000);
    })();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script>
      document.getElementById('download-png')?.addEventListener('click', function(){
          const el = document.getElementById('receipt-card');
          if(!el) return;
          html2canvas(el, {scale: 2, useCORS: true}).then(c => {
              const a = document.createElement('a');
              a.download = 'receipt.png';
              a.href = c.toDataURL('image/png');
              a.click();
          });
      });
  </script>
</body>
</html>
