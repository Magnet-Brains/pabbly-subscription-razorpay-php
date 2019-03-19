<?php

require __DIR__ . '/vendor/autoload.php';
require('config.php');
require __DIR__ . '/lib/Subscription.php';
session_start();

// Create the Razorpay Order
use Razorpay\Api\Api;

if (!isset($_GET['hostedpage']) && !$_GET['hostedpage']) {
    throw new Exception('Hosted page data is required');
}

//Api credential of Pabbly Subscription
$apiKey = ""; //Put your api key here
$apiSecret = ""; // Put your api secret here

$hostedpage = $_GET['hostedpage'];
$subscription = new Subscription($apiKey, $apiSecret);
//Get hosted page details
try {
    $api_data = $subscription->hostedPage($hostedpage);
} catch (Exception $e) {
    die($e->getMessage());
}
$customer = $api_data->customer;
$product = $api_data->product;
$plan = $api_data->plan;
$invoice = $api_data->invoice;

//
// We create an razorpay order using orders api
// Docs: https://docs.razorpay.com/docs/orders
//
$orderData = [
    'receipt' => $invoice->invoice_id,
    'amount' => $invoice->due_amount * 100, // 2000 rupees in paise
    'currency' => 'INR',
    'payment_capture' => 1 // auto capture
];
$api = new Api($keyId, $keySecret);
$razorpayOrder = $api->order->create($orderData);

$razorpayOrderId = $razorpayOrder['id'];

$_SESSION['razorpay_order_id'] = $razorpayOrderId;

$displayAmount = $amount = $orderData['amount'];

if ($displayCurrency !== 'INR') {
    $url = "https://api.fixer.io/latest?symbols=$displayCurrency&base=INR";
    $exchange = json_decode(file_get_contents($url), true);

    $displayAmount = $exchange['rates'][$displayCurrency] * $amount / 100;
}

$customer_name = $customer->first_name . ' ' . $customer->last_name;
$data = [
    "key" => $keyId,
    "amount" => $amount,
    "name" => $product->product_name,
    "description" => $plan->plan_name,
    "image" => "",
    "prefill" => [
        "name" => $customer_name,
        "email" => $customer->email_id,
        "contact" => isset($customer->phone) ? $customer->phone : '',
    ],
    "notes" => [
        "address" => isset($customer->billing_address->street1) ? $customer->billing_address->street1 : '',
        "merchant_order_id" => $invoice->invoice_id,
    ],
    "theme" => [
        "color" => "#F37254"
    ],
    "order_id" => $razorpayOrderId,
];

if ($displayCurrency !== 'INR') {
    $data['display_currency'] = $displayCurrency;
    $data['display_amount'] = $displayAmount;
}

$json = json_encode($data);
//Process the razorpay checkout
require("checkout.php");

