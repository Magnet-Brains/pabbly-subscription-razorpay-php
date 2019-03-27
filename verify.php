<?php

require('config.php');
session_start();
require('vendor/autoload.php');
require __DIR__ . '/lib/Subscription.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

if (!isset($_POST['razorpay_payment_id'])) {
    die("Access denied");
}
$success = true;

$error = "Payment Failed";

if (empty($_POST['razorpay_payment_id']) === false) {
    $api = new Api($keyId, $keySecret);
    try {
        // Please note that the razorpay order ID must
        // come from a trusted source (session here, but
        // could be database or something else)
        $attributes = array(
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        );

        $api->utility->verifyPaymentSignature($attributes);
    } catch (SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true) {
    $html = "<p>Your payment was successful</p>
             <p>Payment ID: {$_POST['razorpay_payment_id']}</p>";
    $subscription = new Subscription($apiKey, $apiSecret);
    //Record payment for the subscribed plan
    //Parameters would be payment_mode, payment_note and transaction details
    try {
        $api_data = $subscription->recordPayment($_POST['shopping_order_id'], 'razorpay', '', $attributes);

        //If requested from Pabbly checkout page, redirect to thank you page
        if (isset($_POST['hostedpage'])) {
            $subscription->redirectThanktyou($api_data->subscription->id, $api_data->subscription->customer_id);
        }
    } catch (Exception $e) {
        die($e->getMessage());
    }

    //Your success code here
    echo $html;
} else {
    $html = "<p>Your payment failed</p>
             <p>{$error}</p>";
    echo $html;
    //Your failed code here
}
