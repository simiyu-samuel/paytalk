{*
 * Paytalkmod - Lipa Na M-Pesa Module for Prestashop 1.7
 *
 * Form to be displayed in the payment step
 *
 * @author Paysatalk Kenya
 * @license https://opensource.org/licenses/afl-3.0.php
 *}

<form action="{$action}" id="payment-form" method="post">
    <input type="hidden" name="order_id" value="{$order_id}">
    <input type="hidden" name="currency_id" value="{$currency_id}">
    
    {if isset($smarty.get.message)}
        {$message = $smarty.get.message}
    {/if}
    {if isset($smarty.get.m_err)}
        {$m_err = $smarty.get.m_err}
    {/if}
    {if isset($smarty.get.stk_err)}
        {$stk_err = $smarty.get.stk_err}
    {/if}
    {if isset($smarty.get.ttl)}
        {$ttl = $smarty.get.ttl}
    {/if}

    {if isset($message) || isset($lepa_message)}
        <p style="color:red">*{$message}</p>
    {/if}

    {if isset($m_err)}
        <p style="color:red">*{$m_err}</p>
    {/if}

    {if isset($stk_err)}
        <div>
            <p><b>Follow these simple steps to complete your payment:</b></p>
            <ol>
                <li>Go to the M-PESA menu on your phone.</li>
                <li>Select *Lipa na M-PESA* and then the *Paybill* option.</li>
                <li>Enter the Business Number: <b>4016535</b>.</li>
                <li>Enter the Account Number: <b>{$stk_err}</b>.</li>
                <li>Enter the amount: <b>KES {$ttl}</b>.</li>
                <li>Enter your M-PESA PIN and confirm.</li>
                <li>Check the SMS confirmation on your phone.</li>
                <li>Provide the phone number used and the Transaction ID below to confirm the payment.</li>
            </ol>

            <label>{l s='Enter M-PESA Phone No.'}</label><br>
            <input type="text" name="phone_number" placeholder="07XXXXXXXX" style="border-radius: 4px;" required><br><br>

            <label>{l s='Transaction ID'}</label><br>
            <input type="text" name="trans_id" placeholder="Enter Transaction ID" style="border-radius: 4px;" required>
        </div>
    {else}
        <p>{l s='Please enter your M-PESA Number. Shortly you will receive an M-PESA prompt on your phone requesting you to enter your M-PESA PIN to complete your payment. Ensure your phone is ON and UNLOCKED to enable you to complete the process. Thank you.' mod='paytalk'}</p>

        <div>
            <label>{l s='Enter M-PESA Phone No.'}</label><br>
            <input type="text" name="phone_number" placeholder="07XXXXXXXX" style="border-radius: 4px;" required>
            <input type="hidden" name="trans_id" value="NA">
        </div>
    {/if}

    <div>
        <button type="submit" class="btn btn-primary">{l s='Submit Payment'}</button>
    </div>
</form>
