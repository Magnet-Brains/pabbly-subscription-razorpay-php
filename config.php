<?php
//Razorpay api credential
$keyId = ''; //Put your Razorpay key 
$keySecret = ''; //Pur your Razorpay secret key

//Pably Subscriptions api credentials
$apiKey = ''; //Put Pabbly api key here
$apiSecret = ''; //Put Pabbly api secret here

$displayCurrency = 'INR'; //Set your currency for Razorpay

//These should be commented out in production
// This is for error reporting
// Add it to config.php to report any errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
