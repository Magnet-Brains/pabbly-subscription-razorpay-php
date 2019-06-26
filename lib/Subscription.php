<?php

use \Curl\Curl;

class Subscription
{

    var $apiKey = "";
    var $apiSecret = "";
    var $apiUrl = "https://payments.pabbly.com/api/v1/";
    var $thankyouUrl = "https://payments.pabbly.com/thankyou/";

    function __construct($apiKey = "", $apiSecret = "")
    {
        if (!$apiKey && !$apiSecret) {
            throw new Exception('Error: apikey and api secret are required');
        }
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        // $this->apiUrl = "http://localhost:1337/v1/";
        // $this->thankyouUrl = "http://localhost:5000/thankyou/";
    }

    function apiPath($path)
    {
        return $this->apiUrl . $path;
    }

    /**
     * Subscribe for the plan
     * @param type $data
     * @return type
     * @throws Exception
     */
    function subscribe($data)
    {
        $curl = new Curl();
        $curl->setBasicAuthentication($this->apiKey, $this->apiSecret);
        $curl->post($this->apiPath('subscription'), array(
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gateway_type' => $data['gateway_name'],
            'street' => $data['street'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip_code' => $data['zip_code'],
            'country' => $data['country'],
            'plan_id' => $data['plan_id'],
        ));

        if ($curl->error) {
            throw new Exception('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
        }
        if ($curl->response->status == 'error') {
            throw new Exception('Subscribe Error: ' . $curl->response->message);
        }
        return $curl->response->data;
    }

    /**
     * Record invoice payment
     * @param type $payment_mode
     * @param type $payment_note
     * @param type $attributes
     * @return type
     * @throws Exception
     */
    function recordPayment($invoice_id, $payment_mode, $payment_note, $transaction_data)
    {
        if (!$invoice_id) {
            throw new Exception('Error: invoice id is required');
        }
        $curl = new Curl();
        $curl->setBasicAuthentication($this->apiKey, $this->apiSecret);
        $curl->post($this->apiPath('invoice/recordpayment/' . $invoice_id), array(
            'payment_mode' => $payment_mode,
            'payment_note' => $payment_note,
            'transaction' => $transaction_data
        ));
        if ($curl->error) {
            throw new Exception('Record Payment Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
        }
        if ($curl->response->status == 'error') {
            throw new Exception('Record Payment Error: ' . $curl->response->message);
        }
        return $curl->response->data;
    }

    /**
     * Redirect to thank you page
     * @param type $subscriptionId
     * @param type $customerId
     */
    function redirectThankYou($subscriptionId, $customerId)
    {
        $redirect_url = $this->thankyouUrl . $subscriptionId . "/" . $customerId;
        header('Location:' . $redirect_url);
        exit;
    }

    /**
     * Hosted page request verify and return data
     * @param type $hostedpage
     * @return type
     * @throws Exception        
     */
    function hostedPage($hostedpage)
    {
        $curl = new Curl();
        $curl->setBasicAuthentication($this->apiKey, $this->apiSecret);
        $curl->post($this->apiPath('hostedpage'), array(
            'hostedpage' => $hostedpage
        ));
        if ($curl->error) {
            throw new Exception('Hosted Page Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
        }
        if ($curl->response->status == 'error') {
            throw new Exception('Hosted Page Error: ' . $curl->response->message);
        }
        return $curl->response->data;
    }

    /**
     * Activate the trial subscription
     */
    function activateTrialSubscription($subscription_id)
    {
        if (!$subscription_id) {
            throw new Exception('Error: invoice id is required');
        }
        $curl = new Curl();
        $curl->setBasicAuthentication($this->apiKey, $this->apiSecret);
        $curl->post($this->apiPath('subscription/activatetrial/' . $subscription_id), []);
        if ($curl->error) {
            throw new Exception('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
        }
        if ($curl->response->status == 'error') {
            throw new Exception('Error: ' . $curl->response->message);
        }
        return $curl->response->data;
    }
}
