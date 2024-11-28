{*
 * Paytalkmod - Lipa Na M-Pesa Module for Prestashop 1.7
 *
 * Form to be displayed in the payment step
 *
 * @author 
 * @license https://opensource.org/licenses/afl-3.0.php
 *}

{extends file='page.tpl'}

{block name='page_content'}
<div class="container text-center mt-5">
    <div class="card shadow-lg p-4 mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <div id="initial_content">
                <input type="hidden" id="checkout_request_id" value="{$checkout_request_id}">
                <h2 class="text-primary mb-4">
                    <i class="fas fa-spinner fa-spin"></i> Payment Processing
                </h2>
                <p class="lead text-success">
                    Your payment for Order: <b>{$stk_err|escape:'html'}</b> is in progress.<br/>
                    Please enter your M-Pesa PIN to confirm.
                </p>
                <p class="text-muted mt-3">
                    Ensure your M-Pesa account is ready for confirmation to avoid delays.
                </p>
            </div>
            <!-- Placeholder for success or failure message -->
            <div id="status_message"></div>

            <!-- Success content - Hidden initially -->
            <div id="success_content" style="display: none;">
                <h1 class="text-success mb-4">🎉 Congratulations!</h1>
                <p class="lead">
                    You have successfully completed an order using <b>Lipa Na M-Pesa</b>.<br/>
                    <span class="text-muted">Order:</span> <b>{$stk_err|escape:'html'}</b>
                </p>
                <p class="text-muted">
                    An email has been sent to your inbox with the order details.<br/>
                    We appreciate your trust in us. Continue shopping with us. Thank you!
                </p>
                <a href="{$link->getPageLink('index')}" class="btn btn-primary mt-3">
                    <i class="fas fa-shopping-cart"></i> Continue Shopping
                </a>
            </div>
            
<div id="failure_content" style="display: none;">
    <h1 class="text-danger mb-4">❌ Payment Failed</h1>
    <p class="lead">
        Unfortunately, your payment using <b>Lipa Na M-Pesa</b> was unsuccessful.<br />
        <span class="text-muted">Order:</span> <b>{$stk_err|escape:'html'}</b>
    </p>
    <p class="text-muted">
        An order was made with pending status.
    </p>

    <!-- Form to retry payment via POST -->
    <div class="row align-items-center">
        <!-- Column for the forms -->
        <div class="col-md-6 d-flex flex-column gap-2">
            <!-- Resend STK Form -->
            <form action="{$link->getModuleLink('paytalk', 'resendstk')}" method="POST" class="d-inline">
                <input type="hidden" id="orderId" name="id_order">
                <input type="hidden" id="phone" name="phone" value="{$phone}">
                <button type="submit" class="btn btn-warning w-100">
                    Resend stk
                </button>
            </form>

            <!-- Retry Payment Form -->
            <form action="{$link->getModuleLink('paytalk', 'retrypayment')}" method="POST" class="d-inline mt-4">
                <input type="hidden" name="stk_err" value="{$stk_err}">
                <input type="hidden" name="ttl" value="{$ttl}">
                <button type="submit" class="btn btn-danger w-100">
                    Manual Pay
                </button>
            </form>
        </div>

        <!-- Column for the standalone button -->
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="{$link->getPageLink('index')}" class="btn btn-secondary w-100">
                Home
            </a>
        </div>
    </div>
</div>

</div>

        </div>
    </div>
</div>


<!-- Include Bootstrap and Font Awesome CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Get the checkoutRequestID from the hidden input
    var checkoutRequestID = '{$checkout_request_id}';
    var statusUrl = 'https://shwarimatt.com/modules/paytalk/ajax/status.php';
    
    console.log("Initial checkoutRequestID: " + checkoutRequestID);
    console.log("Status URL: " + statusUrl);

    // Function to check the payment status
    function checkTransactionStatus() {
        $.ajax({
            url: statusUrl,
            type: 'POST',
            data: { checkoutRequestID: checkoutRequestID },
            success: function(response) {
                console.log("Response: ", response); // Debugging the response
                
                // Check if the response is a valid JSON object
                try {
                    var responseObject = JSON.parse(response);
                    console.log("Parsed Response: ", responseObject);

                    // Check if the payment status is successful
                    if (responseObject.status === 'success') {
                        console.log("Payment Successful: " + responseObject.status);
                        // Display success content and hide the processing message
                        $('#status_message').html('Payment was successful!');
                        $('#success_content').show();
                        $('#initial_content').hide();
                        clearInterval(statusCheckInterval); // Stop further checks once successful
                    } else if (responseObject.status === 'failed' && responseObject.isRes === 'yes') {
                        $('#orderId').val(responseObject.orderId);
                        $('#failure_content').show();
                        $('#initial_content').hide();
                        console.log("Payment Failed: " + responseObject.status);
                        clearInterval(statusCheckInterval);
                    } else {
                        $('#status_message').html('Checking payment status...');
                    }
                } catch (e) {
                    console.log("Error parsing response: ", e);
                    $('#status_message').html('Error processing payment response.');
                }
            },
            error: function(xhr, status, error) {
                console.log("Error checking payment status for checkoutRequestID: " + checkoutRequestID);
                $('#status_message').html('Error checking payment status.');
            }
        });
    }

    // Set the interval to check the payment status every 5 seconds (5000 milliseconds)
    var statusCheckInterval = setInterval(checkTransactionStatus, 5000);
});
</script>

{/block}
