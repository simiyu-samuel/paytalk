<?php
// Include PrestaShop's configuration and classes using the absolute path
include('/kunden/homepages/23/d904095313/htdocs/clickandbuilds/PrestaShop/Shwarimatt_shop/config/config.inc.php');
include_once('/kunden/homepages/23/d904095313/htdocs/clickandbuilds/PrestaShop/Shwarimatt_shop/init.php');
include_once(dirname(__FILE__).'/../../classes/db/Db.php');
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test if Db and pSQL are available
if (class_exists('Db') && function_exists('pSQL')) {
    error_log('Db and pSQL are available.');
} else {
    error_log('Db or pSQL are not available.');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkoutRequestID'])) {
    try {
        // Retrieve checkoutRequestID from POST data
        $checkoutRequestID = pSQL(trim($_POST['checkoutRequestID']));
        
        error_log('checkoutRequestID: ' . $checkoutRequestID);

        // Query the database for the transaction
        $sql = 'SELECT * FROM ' . pSQL(_DB_PREFIX_ . 'stk_requests') . ' WHERE checkout_request_id = "' . $checkoutRequestID . '"';
        $order = Db::getInstance()->getRow($sql);

		$sqlRes = 'SELECT * FROM ' . _DB_PREFIX_ . 'stk_requests 
		           WHERE status = "pending" 
		           AND checkout_request_id = "' . pSQL($checkoutRequestID) . '"';
		$orderRes = Db::getInstance()->getRow($sqlRes);
      
		//check if callback has received any data
      	if($orderRes){
          //no data received
          $isRes = 'no';
        } else{
          //data received
          $isRes = 'yes';
        }

        if ($order) {
            error_log('Order found: ' . var_export($order, true));

            $orderId = (int)$order['order_id'];

            // Retrieve the corresponding order
            $orderData = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . $orderId
            );

            if ($orderData) {
                error_log('Order data: ' . var_export($orderData, true));
                $orderStatus = (int)$orderData['current_state'];
				$orderId = (int)$orderData['id_order'];
                // Check if the order is in the expected status
                if ($orderStatus == '14') {
                    echo json_encode(['status' => 'success', 'isRes' => $isRes]);
                } else {
                    echo json_encode(['status' => 'failed', 'orderId' => $orderId, 'isRes' => $isRes]);
                }
            } else {
                echo json_encode(['status' => 'failed', 'error' => 'Order not found', 'orderId' => $orderId, 'isRes' => $isRes]);
            }
        } else {
            echo json_encode(['status' => 'failed', 'error' => 'Transaction not found', 'orderId' => $orderId, 'isRes' => $isRes]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'failed', 'error' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    // Handle invalid requests
    echo json_encode(['status' => 'failed', 'error' => 'Invalid request']);
}

exit;
