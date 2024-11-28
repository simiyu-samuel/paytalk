<?php
use Context;

class PaytalkPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
      
        // Retrieve parameters safely
        $stk_err = Tools::getValue('stk_err');
        $ttl = Tools::getValue('ttl');
      	$checkout_request_id = Tools::getValue('checkout_request_id');
      	$statusUrl = Context::getContext()->link->getModuleLink(
    		'paytalk',
    		'ajax',
    		['file' => 'status.php'],
    		true
		);
      $phone = Tools::getValue('phone');
      
        // Assign variables to Smarty
        $this->context->smarty->assign(['stk_err' => $stk_err,'ttl' => $ttl, 'checkout_request_id' => $checkout_request_id, 'statusUrl' => $statusUrl, 'phone' => $phone]);

        // Render the template
        $this->setTemplate('module:paytalk/views/templates/hook/confirm.tpl');
    }
}
