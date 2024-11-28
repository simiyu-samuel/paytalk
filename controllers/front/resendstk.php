<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class PaytalkResendstkModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $orderId = Tools::getValue('id_order');

        if (!$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

      //var_dump('Order id: ' . $orderId);
      	
      $order = new Order((int)$orderId);

      if (!Validate::isLoadedObject($order)) {
         $this->redirectWithNotifications('index.php?controller=order&step=1&message=Order not found.');
      }

      $customer = new Customer((int)$order->id_customer);
      $total = (float)$order->total_paid;
      $currency = new Currency((int)$order->id_currency);
      $cartId = $order->id_cart;
          
      $phone = Tools::getValue('phone');

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
            'AccountReference' => $cartId,
            'TransactionDesc' => 'Payment for Order #' . $cartId,
        ];

        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            $this->handleError();
        }

        // Make M-Pesa request
        $response = $this->makeMpesaRequest($accessToken, $requestPayload);

        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            if (isset($response['CheckoutRequestID'])) {
                Db::getInstance()->insert('stk_requests', [
                    'order_id' => $cartId,
                    'checkout_request_id' => $response['CheckoutRequestID'],
                    'status' => 'pending',
                ]);
            }

            // Redirect to payment status page
            $checkoutRequestID = $response['CheckoutRequestID'];
            $url = $this->context->link->getModuleLink('paytalk', 'payment', [
                'ttl' => $total,
                'stk_err' => $cartId,
                'checkout_request_id' => $checkoutRequestID,
            ], true);
            Tools::redirect($url);
        } else {
            // Handle failure
            $errorMessage = isset($response['errorMessage'])
                ? $response['errorMessage']
                : 'Payment request failed. Please try again.';
            $this->handleError();
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

    private function handleError()
    {
        Tools::redirect('index.php?controller=order&step=1&message=Order not found.');
    }
}
