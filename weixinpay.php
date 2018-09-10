<?php
/**
 * 2010-2015 Yiuked
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade yiukedautoseo to newer
 * versions in the future.
 *
 * @author    Yiuked SA <yiuked@vip.qq.com>
 * @copyright 2010-2015 Yiuked
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Weixinpay extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public $appid;
    public $mchid;
    public $notify;
    public $wxkey;

    public function __construct()
    {
        $this->name = 'weixinpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.4.17';
        $this->author = 'Yiuked';
        $this->controllers = array('payment', 'return');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->module_key = '3ec3536af1d608ffbd41748925a9fd55';

        $config = Configuration::getMultiple(array('WEIXIN_APPID', 'WEIXIN_MCH_ID', 'WEIXIN_NOTIFY_URL', 'WEIXIN_KEY',  'WEIXIN_ENABLED_H5PAY', 'WEIXIN_APPSECRET'));
        if (!empty($config['WEIXIN_APPID'])) {
            $this->appid = $config['WEIXIN_APPID'];
        }
        if (!empty($config['WEIXIN_MCH_ID'])) {
            $this->mchid = $config['WEIXIN_MCH_ID'];
        }
        if (!empty($config['WEIXIN_KEY'])) {
            $this->wxkey = $config['WEIXIN_KEY'];
        }
        if (!empty($config['WEIXIN_NOTIFY_URL'])) {
            $this->notify = $config['WEIXIN_NOTIFY_URL'];
        }
        if (isset($config['WEIXIN_ENABLED_H5PAY'])) {
            $this->H5Pay = $config['WEIXIN_ENABLED_H5PAY'];
        }
        if (!empty($config['WEIXIN_APPSECRET'])) {
            $this->appsecret = $config['WEIXIN_APPSECRET'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('WeChat Pay');
        $this->description = $this->l('Accept your products by tencent WeChat payment.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        if (!isset($this->appid) || !isset($this->mchid) || !isset($this->wxkey) || !isset($this->notify) || !isset($this->validate) || (isset($this->H5Pay) && $this->H5Pay == 1  && !isset($this->appsecret))) {
            $this->warning = $this->l('Account all details must be configured before using this module.');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        $this->initOrderStatus();
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentOptions')|| !$this->registerHook('displayPaymentEU') || !$this->registerHook('displayHeader') || !$this->registerHook('paymentReturn')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('WEIXIN_APPID')
            || !Configuration::deleteByName('WEIXIN_MCH_ID')
            || !Configuration::deleteByName('WEIXIN_KEY')
            || !Configuration::deleteByName('WEIXIN_NOTIFY_URL')
            || !Configuration::deleteByName('WEIXIN_ENABLED_H5PAY')
            || !Configuration::deleteByName('WEIXIN_APPSECRET')
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('WEIXIN_APPID')) {
                $this->_postErrors[] = $this->l('App id are required.');
            } elseif (!Tools::getValue('WEIXIN_MCH_ID')) {
                $this->_postErrors[] = $this->l('Merchant id is required.');
            } elseif (!Tools::getValue('WEIXIN_KEY')) {
                $this->_postErrors[] = $this->l('Merchant key is required.');
            } elseif (!Tools::getValue('WEIXIN_NOTIFY_URL')) {
                $this->_postErrors[] = $this->l('Notify url is required.');
            }elseif (Tools::getValue('WEIXIN_ENABLED_H5PAY') && !Tools::getValue('WEIXIN_APPSECRET')) {
                $this->_postErrors[] = $this->l('APPSecret is required.');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('WEIXIN_APPID', Tools::getValue('WEIXIN_APPID'));
            Configuration::updateValue('WEIXIN_MCH_ID', Tools::getValue('WEIXIN_MCH_ID'));
            Configuration::updateValue('WEIXIN_KEY', Tools::getValue('WEIXIN_KEY'));
            Configuration::updateValue('WEIXIN_NOTIFY_URL', Tools::getValue('WEIXIN_NOTIFY_URL'));
            Configuration::updateValue('WEIXIN_ENABLED_H5PAY', Tools::getValue('WEIXIN_ENABLED_H5PAY'));
            Configuration::updateValue('WEIXIN_APPSECRET', Tools::getValue('WEIXIN_APPSECRET'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displayWeiXinPay()
    {
        $this->smarty->assign(array(
            'text_1' => $this->l('This module allows you to accept secure payments by WeChat payment.'),
            'text_2' => $this->l('WeChat payment currently only supports the Chinese currency, currency ISO code:CNY'),
            'text_3' => $this->l('Please make sure that your website has been added to the CNY currency.'),
        ));

        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayWeiXinPay();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'enable_h5pay' => (int)$this->H5Pay,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        return array(
            'cta_text' => $this->l('Pay by Weixin'),
            'logo' => Media::getMediaPath(dirname(__FILE__) . '/views/img/weixinpay.png'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        );
    }

    public function ajaxCall()
    {
        if (Tools::getIsset('rangargs')) {
            $id_cart = Tools::getValue('rangargs');
            $id_order = Order::getOrderByCartId($id_cart);
            if ($id_order > 0) {
                $order = new Order($id_order);
                if ($order->current_state == Configuration::get('PS_OS_PAYMENT')) {
                    die(json_encode(array('status' => 1)));
                }
            }
        }
        sleep(1);
        die(json_encode(array('status' => 0)));
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        if (!isset($params['order'])) {
            $params['order'] = $params['objOrder'];
        }

        $state = $params['order']->getCurrentState();
        if ($state == Configuration::get('PS_OS_PAYMENT')) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'shop_name' => $this->context->shop->name,
                'status' => 'ok',
                'id_order' => $params['order']->id
            ));
            if (isset($params['order']->reference) && !empty($params['order']->reference)) {
                $this->smarty->assign('reference', $params['order']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->fetch('module:weixinpay/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookdisplayHeader($params)
    {
        $haystack = array('module-weixinpay-payment', 'order-opc', 'order');

        $show_header = false;
        if (isset($this->context->controller->page_name) && $this->context->controller->page_name == 'module-weixinpay-payment') {
            $show_header = true;
        }

        if (isset($this->context->controller->php_self) && ($this->context->controller->php_self == 'order' || $this->context->controller->php_self == 'order-opc')) {
            $show_header = true;
        }
        if (!$show_header) {
            return;
        }

        $this->context->controller->addCss($this->_path . 'views/css/weixinpay.css', 'all');
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Contact details'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('App id'),
                        'name' => 'WEIXIN_APPID',
                        'desc' => $this->l('Open an account in the email to view.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Merchant ID'),
                        'name' => 'WEIXIN_MCH_ID',
                        'desc' => $this->l('Open an account in the email to view.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'WEIXIN_KEY',
                        'desc' => $this->l('Login WeChat merchant platform set up on its own.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Notify Url'),
                        'name' => 'WEIXIN_NOTIFY_URL',
                        'desc' => $this->l('Asynchronous receive return status.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Public number payment'),
                        'name' => 'WEIXIN_ENABLED_H5PAY',
                        'desc' => $this->l('Users in WeChat to enter the business H5 page, the page is called JSSDK to complete the payment'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('APPSecret'),
                        'name' => 'WEIXIN_APPSECRET',
                        'desc' => $this->l('Application key.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $domian = Tools::getShopDomainSsl(true);

        return array(
            'WEIXIN_APPID' => Tools::getValue('WEIXIN_APPID', Configuration::get('WEIXIN_APPID')),
            'WEIXIN_MCH_ID' => Tools::getValue('WEIXIN_MCH_ID', Configuration::get('WEIXIN_MCH_ID')),
            'WEIXIN_KEY' => Tools::getValue('WEIXIN_KEY', Configuration::get('WEIXIN_KEY')),
            'WEIXIN_NOTIFY_URL' => Tools::getValue('WEIXIN_NOTIFY_URL', Configuration::get('WEIXIN_NOTIFY_URL') ? Configuration::get('WEIXIN_NOTIFY_URL') : $domian . $this->_path . 'notify.php')
        );
    }

    public function initOrderStatus()
    {
        $id_order_status = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
        SELECT id_order_state
        FROM `' . _DB_PREFIX_ . 'order_state`
        WHERE module_name = "' . pSQL($this->name) . '"');

        if (!$id_order_status) {
            $status = new OrderState();
            $langs = Language::getLanguages();
            foreach ($langs as $id_lang) {
                $status->name[$id_lang['id_lang']] = 'Awaiting WeChat Payment';
            }
            $status->module_name = $this->name;
            $status->color = '#4169E1';
            if (!$status->add()) {
                return false;
            }

            $id_order_status = $status->id;

            if (Configuration::updateValue('WEIXIN_STATUS_AWAITING', $id_order_status)) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }
}
