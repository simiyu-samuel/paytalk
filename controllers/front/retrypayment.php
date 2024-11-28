<?php
class PaytalkRetrypaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
      
      	//$action = $this->context->link->getModuleLink($this->name, 'validation', [], true)
        $ttl = Tools::getValue('ttl');
      	$stk_err = Tools::getValue('stk_err');

        $this->context->smarty->assign(['stk_err' => $stk_err, 'ttl' => $ttl]);
        $this->setTemplate('module:paytalk/views/templates/hook/repayment.tpl');
    }
}
