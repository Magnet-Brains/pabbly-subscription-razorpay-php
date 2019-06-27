<?php

require('vendor/autoload.php');
require __DIR__ . '/lib/Subscription.php';
require('config.php');

use Razorpay\Api\Api;

$error = '<strong style="color:red;">Error: </strong>';

if (empty($_POST)) {
    throw new Exception('Api form data is required');
}
session_start();

//Subscription plan id
$plan_id = "Put your Pabbly Subscription plan id here";

$subscription = new Subscription($apiKey, $apiSecret);
$api_data = array(
    'first_name' => $_POST['first_name'],
    'last_name' => $_POST['last_name'],
    'email' => $_POST['email'],
    'gateway_name' => 'razorpay',
    'street' => $_POST['street'],
    'city' => $_POST['city'],
    'state' => $_POST['state'],
    'zip_code' => $_POST['zip_code'],
    'country' => $_POST['country'],
    'plan_id' => $plan_id,
);

//Subscribe the plan
try {
    $apiResponse = $subscription->subscribe($api_data);
} catch (Exception $e) {
    die($error . $e->getMessage());
}

$_subscription = $apiResponse->subscription;

//If the subscription is trial, activate it and redirect to thank you page
if ($_subscription->trial_days > 0) {
    //Do here your additional things if require.

    //Activate the trial subscription
    $api_data = $subscription->activateTrialSubscription($_subscription->id);

    //Redirect to the thank you page
    $subscription->redirectThankYou($api_data->subscription->id, $api_data->subscription->customer_id);
}

$user = $apiResponse->user;
$customer = $apiResponse->customer;
$product = $apiResponse->product;
$plan = $apiResponse->plan;
$invoice = $apiResponse->invoice;
$currency = $user->currency;
$displayCurrency = $currency;

//
// We create an razorpay order using orders api
// Docs: https://docs.razorpay.com/docs/orders
//
$orderData = [
    'receipt' => $invoice->invoice_id,
    'amount' => $invoice->due_amount * 100, // 2000 rupees in paise
    'currency' => $currency,
    'payment_capture' => 1 // auto capture
];

$api = new Api($keyId, $keySecret);
try {
    $razorpayOrder = $api->order->create($orderData);
} catch (Exception $e) {
    die($error . $e->getMessage());
}
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
