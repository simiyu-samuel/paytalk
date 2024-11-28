<?php
/**
 * Paytalk - A Sample Payment Module for PrestaShop
 *
 * This file is the declaration of the module.
 *
 * @author Simiyu Samuel <simiyusamuel869@gmail.com>
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
 
class Paytalk extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
 
    public $address;
 
    /**
     * Paytalk constructor.
     *
     * Set the information about this module
     */
    public function __construct()
    {
        $this->name                   = 'paytalk';
        $this->tab                    = 'payments_gateways';
        $this->version                = '2.0.6';
        $this->author                 = 'Paysatalk Kenya';
        $this->controllers            = array('payment', 'validation');
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';
        $this->bootstrap              = true;
        $this->displayName            = 'Lipa Na M-PESA';
        $this->description            = 'Paytalk Lipa Na M-PESA Module(STK Push, C2B)';
        $this->confirmUninstall       = 'Are you sure you want to uninstall Paytalk module?';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
      
       parent::__construct();
    }
     
    /**
     * Install this module and register the following Hooks:
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn') || !Configuration::updateValue('RAVEPAYMENTGATEWAY_NAME', 'paytalk')) {
            return false;
          }

        if (!$this->installOrderState())
            return false;
      
       // Create the custom table for storing STK requests
    $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stk_requests` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `checkout_request_id` VARCHAR(255) NOT NULL,
        `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE (`checkout_request_id`)
    ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

    // Execute the SQL
    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

          return true;
    }
 
    /**
     * Uninstall this module and remove it from all hooks
     *
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('LIPANAMPESA_LIVE_MODE');
      
      $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "stk_requests`;";
    if (!Db::getInstance()->execute($sql)) {
        return false;
    }
        return parent::uninstall();
    }
 
    /**
     * Returns a string containing the HTML necessary to
     * generate a configuration screen on the admin
     *
     * @return string
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitpaytalkModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        //$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitpaytalkModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }





    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'LIPANAMPESA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col'  => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-lock"></i>',
                        'name' => 'M_PESA_BUSINESS_SHORTCODE',
                        'label' => $this->l('Paybill/Till Number'),
                    ),
                    array(
                        'col'  => 5,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Get your API username from https://api.safaricom.co.ke'),
                        'name' => 'M_PESA_CONSUMER_KEY',
                        'label' => $this->l('Consumer Key'),
                    ),
                    array(
                        'col'  => 5,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Get your API username from https://api.safaricom.co.ke'),
                        'name' => 'M_PESA_CONSUMER_SECRET',
                        'label' => $this->l('Consumer Secret'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'LIPANAMPESA_LIVE_MODE' => Configuration::get('LIPANAMPESA_LIVE_MODE', true),
            'M_PESA_BUSINESS_SHORTCODE' => Configuration::get('M_PESA_BUSINESS_SHORTCODE', 'Consumer Key'),
            'M_PESA_CONSUMER_KEY' => Configuration::get('M_PESA_CONSUMER_KEY', 'Paybill/Till Number'),
            'M_PESA_CONSUMER_SECRET' => Configuration::get('M_PESA_CONSUMER_SECRET', 'Consumer Secret'),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }



    public function hookPaymentOptions($params)
{
    // Verify if this module is active
    if (!$this->active) {
        return;
    }

    // Get the current cart ID and currency ID
    $cart = $this->context->cart;
    $order_id = $cart->id;
    $currency_id = $cart->id_currency;

    // Add these values as parameters to the form action URL
    $formAction = $this->context->link->getModuleLink(
        $this->name,
        'validation',
        ['order_id' => $order_id, 'currency_id' => $currency_id],
        true
    );

     // Retrieve values from the session
    if (isset($_SESSION['stk_err'])) {
        $this->context->smarty->assign('stk_err', $_SESSION['stk_err']);
        unset($_SESSION['stk_err']); 
    }
    if (isset($_SESSION['m_err'])) {
        $this->context->smarty->assign('m_err', $_SESSION['m_err']);
        unset($_SESSION['m_err']); 
    }
    if (isset($_SESSION['ttl'])) {
        $this->context->smarty->assign('ttl', $_SESSION['ttl']);
        unset($_SESSION['ttl']);
    }
    if (isset($_SESSION['lepa_message'])) {
        $this->context->smarty->assign('lepa_message', $_SESSION['lepa_message']);
        unset($_SESSION['lepa_message']);
    }


    // Assign form action to Smarty
    $this->smarty->assign(['action' => $formAction]);

    // Optional: Assign the controller name
    $this->smarty->assign(['kb_controller' => Tools::getValue('controller')]);

    // Load form template to be displayed in the checkout step
    $paymentForm = $this->fetch('module:paytalk/views/templates/hook/payment_options.tpl');

    // Create a PaymentOption object
    $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
    $newOption->setModuleName($this->displayName)
        ->setCallToActionText($this->displayName)
        ->setAction($formAction) // Include the updated action with parameters
        ->setForm($paymentForm);

    return [$newOption];
}


    public function hookPaymentReturn($params)
    {
        /**
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }
 
        return $this->fetch('module:paytalk/views/templates/hook/payment_return.tpl');
    }

    public function installOrderState()
    {
      if (!Configuration::get('PS_OS_PAYTALK_PAYMENT')) {
        $order_state = new OrderState();
        $order_state->send_email = false;
        $order_state->module_name = $this->name;
        $order_state->invoice = false;
        $order_state->color = '#32CD32';
        $order_state->logable = true;
        $order_state->shipped = false;
        $order_state->unremovable = false;
        $order_state->delivery = false;
        $order_state->hidden = false;
        $order_state->paid = false;
        $order_state->deleted = false;
        $order_state->name = array(
          (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('LNM payment accepted'))
        );

        if ($order_state->add()) {
          // Save order state id in tehe config table
          Configuration::updateValue('PS_OS_PAYTALK_PAYMENT', $order_state->id);
          // Clone module logo into the order state logo dir
          copy(dirname(__FILE__).'/views/img/order_state_mini_2_1.gif', dirname(__FILE__).'/../../img/os/'.$order_state->id.'.gif');
          copy(dirname(__FILE__).'/views/img/order_state_mini_2_1.gif', dirname(__FILE__).'/../../img/tmp/order_state_mini_'.$order_state->id.'.gif');

        } else {
          return false;
        }
      }

      return true;
    }

}