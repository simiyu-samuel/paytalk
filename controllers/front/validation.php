<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class PaytalkValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (!$this->module->active || !$cart->id) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer((int)$cart->id_customer);
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $phone = Tools::getValue('phone_number');

        // Validate phone number
        if (empty($phone)) {
            $this->redirectWithNotifications('index.php?controller=order&step=1&message=Phone number cannot be empty');
        }

        $phone = preg_replace("/\D/", "", $phone);
        if (substr($phone, 0, 1) === "0") {
            $phone = "254" . substr($phone, 1);
        } elseif (substr($phone, 0, 3) !== "254") {
            $phone = "254" . $phone;
        }

        $_SESSION['order_id'] = $cart->id;

        $businessShortCode = Configuration::get('M_PESA_BUSINESS_SHORTCODE');
        $passkey = 'bfa3522138b0ed296b1db9ccfe703b3ddca1933ae3916268acfba4a6a3991e35';
        $timestamp = date('YmdHis');
        $password = base64_encode($businessShortCode . $passkey . $timestamp);

        $callbackUrl = 'https://shwarimatt.com/module/paytalk/callback';
        $requestPayload = [
            'BusinessShortCode' => $businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $total,
            'PartyA' => $phone,
            'PartyB' => $businessShortCode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $cart->id,
            'TransactionDesc' => 'Payment for Order #' . $cart->id,
        ];

        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            $this->handleError($cart, 'Failed to authenticate with M-Pesa. Please try again.');
        }

        // Make M-Pesa request
        $response = $this->makeMpesaRequest($accessToken, $requestPayload);

        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            // STK push initiated successfully
            if (isset($response['CheckoutRequestID'])) {
                Db::getInstance()->insert('stk_requests', [
                    'order_id' => $cart->id,
                    'checkout_request_id' => $response['CheckoutRequestID'],
                    'status' => 'pending', // Initially mark the payment as pending
                ]);
            }

            $this->module->validateOrder(
                (int)$cart->id,
                17,
                $total,
                $this->module->displayName,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );

            $checkoutRequestID = $response['CheckoutRequestID'];
            $url = $this->context->link->getModuleLink('paytalk', 'payment', [
                'ttl' => $total,
                'stk_err' => $cart->id,
                'checkout_request_id' => $checkoutRequestID,
              	'phone' => $phone,
            ], true);
            Tools::redirect($url);
        } else {
            // Handle failure
            $errorMessage = isset($response['errorMessage'])
                ? $response['errorMessage']
                : 'Payment request failed. Please try again.';
            $this->handleError($cart, $errorMessage);
        }
    }

    private function getAccessToken()
    {
        $consumerKey = Configuration::get('M_PESA_CONSUMER_KEY');
        $consumerSecret = Configuration::get('M_PESA_CONSUMER_SECRET');
        $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
        $headers = ['Authorization: Basic ' . $credentials];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }

    private function makeMpesaRequest($accessToken, $payload)
    {
        $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return [];
    }

    private function handleError($cart, $errorMessage)
    {
        $this->errors[] = $errorMessage;
        $_SESSION['stk_err'] = $cart->id;
        $_SESSION['ttl'] = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $_SESSION['lepa_message'] = $errorMessage;

        Tools::redirect('index.php?controller=order&step=1&stk_err=' . (int)$cart->id . '&ttl=' . (float)$cart->getOrderTotal(true, Cart::BOTH));
    }
}
