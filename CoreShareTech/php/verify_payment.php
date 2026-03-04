<?php
require 'db_connect.php';

// Check if user is logged in and Paystack returned a reference
if (!isset($_SESSION['user_id']) || !isset($_GET['reference']) || !isset($_GET['plan'])) {
    header("Location: ../html/billing.php?error=" . urlencode("Invalid payment request."));
    exit;
}

$reference = htmlspecialchars(trim($_GET['reference']));
$plan = htmlspecialchars(trim($_GET['plan']));
$userId = $_SESSION['user_id'];
$allowed = ['pro', 'semester', 'exam_pass'];

if (!in_array($plan, $allowed)) {
    header("Location: ../html/billing.php?error=" . urlencode("Invalid plan selected."));
    exit;
}

// === REPLACE THIS WITH YOUR PAYSTACK TEST SECRET KEY ===
$paystackSecretKey = "sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
// =======================================================

// Verify the transaction with Paystack API via cURL
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer " . $paystackSecretKey,
    "Cache-Control: no-cache",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    // Connection to Paystack failed
    header("Location: ../html/billing.php?plan=" . $plan . "&error=" . urlencode("Verification failed: " . $err));
    exit;
} else {
    $result = json_decode($response);
    
    // Check if Paystack confirms the payment was successful
    if ($result && $result->status == true && $result->data->status === 'success') {
        
        // Calculate expiration date based on the plan they bought
        $expires = null;
        if ($plan === 'pro') {
            $expires = date('Y-m-d H:i:s', strtotime('+1 month'));
        } elseif ($plan === 'semester') {
            $expires = date('Y-m-d H:i:s', strtotime('+4 months'));
        } elseif ($plan === 'exam_pass') {
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        }

        // Upgrade the user in the database
        $stmt = $conn->prepare("UPDATE users SET plan = ?, plan_expires = ? WHERE id = ?");
        $stmt->bind_param('ssi', $plan, $expires, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['plan'] = $plan; 
            header('Location: ../html/dashboard.php?success=payment_successful');
        } else {
            // DB Error but payment went through (Rare)
            error_log("Database update failed for User $userId after successful payment $reference");
            header('Location: ../html/billing.php?error=' . urlencode("Payment successful but database error. Please contact support."));
        }
        $stmt->close();
        exit;
    } else {
        // Payment was declined, failed, or fake
        header("Location: ../html/billing.php?plan=" . $plan . "&error=" . urlencode("Payment was not successful."));
        exit;
    }
}
?>