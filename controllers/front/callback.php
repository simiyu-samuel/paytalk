<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class PaytalkCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        try {
            // Get raw JSON input from the Paytalk callback
            $input = file_get_contents('php://input');
            $this->logMessage('Raw Data: ' . $input);
          

            // Decode JSON into an associative array
            $response = json_decode($input, true);
            $this->logMessage('Decoded Data: ' . json_encode($response));

            // Validate response structure
            if (!isset($response['Body']['stkCallback'])) {
                throw new Exception('Invalid response structure');
            }

            // Extract callback data
            $callbackData = $response['Body']['stkCallback'];
            $this->logMessage('Raw Callback Data: ' . json_encode($callbackData));

            // Process transaction based on ResultCode
            if (isset($callbackData['ResultCode']) && $callbackData['ResultCode'] == 0) {
                $this->processTransaction($callbackData);
            } else {
                $this->handleFailedTransaction($callbackData);
            }

            // Respond with success
            echo json_encode(['status' => 'success']);
            http_response_code(200);
        } catch (Exception $e) {
            $this->logMessage('Error: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
            http_response_code(400);
        }
    }

    private function processTransaction($callbackData)
    {
        try {
            // Extract metadata from callback
            $metadata = $callbackData['CallbackMetadata']['Item'] ?? null;
            if (!is_array($metadata)) {
                throw new Exception('CallbackMetadata is missing or invalid');
            }

            $amount = $receiptNumber = $transactionDate = $phoneNumber = null;
            foreach ($metadata as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $amount = $item['Value'];
                        break;
                    case 'MpesaReceiptNumber':
                        $receiptNumber = $item['Value'];
                        break;
                    case 'TransactionDate':
                        $transactionDate = $item['Value'];
                        break;
                    case 'PhoneNumber':
                        $phoneNumber = $item['Value'];
                        break;
                }
            }

            $checkoutRequestID = $callbackData['CheckoutRequestID'] ?? null;
            if (!$checkoutRequestID) {
                throw new Exception('CheckoutRequestID is missing');
            }

            // Find the order in the database
            $order = Db::getInstance()->getRow(
                'SELECT * FROM ' . pSQL(_DB_PREFIX_ . 'stk_requests') . ' WHERE checkout_request_id = "' . pSQL($checkoutRequestID) . '"'
            );

            if (!$order) {
                throw new Exception('Order not found for the provided CheckoutRequestID');
            }

            $orderId = $order['order_id'];
			$result = Db::getInstance()->update(
			    'stk_requests',
			    ['status' => 'completed'],
			    'checkout_request_id = "' . pSQL($checkoutRequestID) . '"'
			);

            $this->logMessage("Order ID found: $orderId");

            // Validate and update the order
            $this->validateAndUpdateOrder($orderId, $amount, $receiptNumber, $transactionDate, $phoneNumber);
        } catch (Exception $e) {
            throw new Exception('Transaction processing error: ' . $e->getMessage());
        }
    }

    private function handleFailedTransaction($callbackData)
    {
        try {
            $checkoutRequestID = $callbackData['CheckoutRequestID'] ?? 'Unknown';
            $this->logMessage('Transaction failed or incomplete: ' . json_encode($callbackData));

            // Retrieve the corresponding order based on the checkout request ID
            $order = Db::getInstance()->getRow(
                'SELECT * FROM ' . pSQL(_DB_PREFIX_ . 'stk_requests') . ' WHERE checkout_request_id = "' . pSQL($checkoutRequestID) . '"'
            );

            if (!$order) {
                throw new Exception('Order not found for the provided CheckoutRequestID');
            }

            $orderId = $order['order_id'];
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . (int)$orderId;
            $orderData = Db::getInstance()->getRow($sql);

            if ($orderData) {
                $orderId = $orderData['id_order'];
            }

            // Update session variables for error messages
            $_SESSION['stk_err'] = "Transaction failed for order ID: $orderId";
            $_SESSION['ttl'] = $orderData['total_paid'] ?? 0;
            $_SESSION['lepa_message'] = "Please try again!";

            // Update the status in the database
			$result = Db::getInstance()->update(
			    'stk_requests',
			    ['status' => 'failed'],
			    'checkout_request_id = "' . pSQL($checkoutRequestID) . '"'
			);
        } catch (Exception $e) {
            throw new Exception('Failed transaction handling error: ' . $e->getMessage());
        }
    }

    private function validateAndUpdateOrder($orderId, $amount, $receiptNumber, $transactionDate, $phoneNumber)
    {
        try {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . (int)$orderId;
            $orderData = Db::getInstance()->getRow($sql);

            if ($orderData) {
                $orderId = $orderData['id_order'];
            }

            $order = new Order((int)$orderId);
            if (!Validate::isLoadedObject($order)) {
                throw new Exception('Invalid order ID: ' . $orderId);
            }

            $customer = new Customer($order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                throw new Exception('Invalid customer for order: ' . $order->id_customer);
            }

            $orderStatusId = Configuration::get('PS_OS_PAYTALK_PAYMENT');
            if (!$orderStatusId) {
                throw new Exception('Payment status configuration missing: PS_OS_PAYTALK_PAYMENT');
            }

            // Update order status
            $order->setCurrentState($orderStatusId);
          	$order->sendOrderStateEmail();
    		// Ensure the frontend knows about the status change by updating the session or forcing a page reload
    		$this->context->cookie->write();
          
            $this->logMessage("Order status updated for Order ID: $orderId");
        } catch (Exception $e) {
            throw new Exception('Order validation and update error: ' . $e->getMessage());
        }
    }

    private function logMessage($message)
    {
        Logger::addLog($message, 1, null, null, null, true);

        $logFile = _PS_MODULE_DIR_ . 'paytalk/logs/log.txt';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        file_put_contents($logFile, $message, FILE_APPEND);
    }
}
