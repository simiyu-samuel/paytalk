{*
 * Paytalk - Lipa Na M-Pesa Module for Prestashop 1.7
 *
 * HTML to be displayed in the order confirmation page
 *
 * @author 
 * @license https://opensource.org/licenses/afl-3.0.php
 *}
{extends file='page.tpl'}

{block name='page_content'}
<div class="container text-center mt-5">
    <div class="card shadow-lg p-4">
        <div class="card-body">
            <h1 class="text-success mb-4">🎉 Congratulations!</h1>
            <p class="lead">
                You have successfully completed an order using <b>Lipa Na M-Pesa</b>.<br/>
                <span class="text-muted">Order Reference:</span> <b>{$stk_err}</b>
            </p>
            <p class="text-muted">
                An email has been sent to your inbox with the order details.<br/>
                We appreciate your trust in us. Continue shopping with us. Thank you!
            </p>
            <a href="{$link->getPageLink('index')}" class="btn btn-primary mt-3">
                <i class="fas fa-shopping-cart"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>
{/block}
