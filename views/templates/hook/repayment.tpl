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

<div class="container mt-5">
    <div class="card shadow-lg p-4 mx-auto" style="max-width: 700px;">
        <div class="card-body">
            <h2 class="text-primary text-center mb-4">
                <i class="fas fa-credit-card"></i> Complete Your Payment
            </h2>

            <p><b>Follow these simple steps to complete your payment:</b></p>
            <ol class="text-muted">
                <li>Go to the M-PESA menu on your phone.</li>
                <li>Select *Lipa na M-PESA* and then the *Paybill* option.</li>
                <li>Enter the Business Number: <b>4016535</b>.</li>
                <li>Enter the Account Number: <b>{$stk_err}</b>.</li>
                <li>Enter the amount: <b>KES {$ttl}</b>.</li>
                <li>Enter your M-PESA PIN and confirm.</li>
                <li>Check the SMS confirmation on your phone.</li>
                <li>Provide the phone number used and the Transaction ID below to confirm the payment.</li>
            </ol>

            <div class="mb-3">
                <label for="phone_number" class="form-label">{l s='Enter M-PESA Phone No.'}</label>
                <input type="text" class="form-control" name="phone_number" placeholder="07XXXXXXXX" style="border-radius: 5px;" required>
            </div>

            <div class="mb-4">
                <label for="trans_id" class="form-label">{l s='Transaction ID'}</label>
                <input type="text" class="form-control" name="trans_id" placeholder="Enter Transaction ID" style="border-radius: 5px;" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{$link->getPageLink('index')}" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Submit Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap and Font Awesome CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js"></script>


{/block}