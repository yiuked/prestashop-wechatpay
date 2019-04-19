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

require_once(_PS_MODULE_DIR_ . 'weixinpay/defines.php');
require_once(WXP_MODDULE_DIR . 'classes/WxPay.NativePay.php');
require_once(WXP_MODDULE_DIR . 'classes/WxPay.JsApiPay.php');
require_once(WXP_MODDULE_DIR . 'classes/qrcode/QRencode.php');

class WeixinpayJssdkPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $tools = new JsApiPay();

        $cart = $this->context->cart;
        $customer = $this->context->customer;
        if (!$this->module->checkCurrency($cart) || !Validate::isLoadedObject($cart) || $cart->id_customer != $customer->id) {
            Tools::redirect('index.php?controller=order');
        }
        if (Tools::getValue('jssdkConfrimOrder') == 1) {
            die(Tools::jsonEncode(array(
                'statusCode' => 1,
            )));
        }
        if (Tools::getValue('jssdkUnifiedOrder') == '1' && Tools::getValue('openID')) {
            $cart = new Cart($cart->id);
            $result = $this->unifiedOrder($cart, Tools::getValue('openID'));

            die(Tools::jsonEncode(array(
                'statusCode' => 1,
                'jsApiParameters' => Tools::jsonDecode($tools->GetJsApiParameters($result))
            )));
        }


        $tools->curl_timeout = 5;
        $openId = $tools->GetOpenid();

        //统一下单
        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'openId' => $openId,
            'rand_args'=> $cart->id,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'return_url' => Context::getContext()->link->getModuleLink('weixinpay', 'return', array('id_cart' => $cart->id)),
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
        ));

        $this->setTemplate('payment_mobile.tpl');
    }

    /**
     * JSSDK 支付调用统一下单过程
     * @param $cart
     * @param $reference
     * @param $openId
     * @return 成功时返回
     * @throws WxPayException
     */
    public function unifiedOrder($cart, $openId)
    {
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $total = (int)($total * 100);

        $detail = '';
        $nbProducts = $cart->nbProducts();
        if ($nbProducts > 1) {
            $detail = $this->module->l('Cart') . ' ' . $nbProducts . ' ' . $this->module->l('Products');
        } else {
            $products = $cart->getProducts();
            $detail = $products[0]['name'];
        }

        $time_start = date("YmdHis");
        $time_expire = date("YmdHis", time() + WXP_TIMEOUT);
        if (WXP_TIMEZONE != Configuration::get('PS_TIMEZONE')) {
            $china_timezone = new DateTimeZone(WXP_TIMEZONE);
            $system_timezone = new DateTimeZone(Configuration::get('PS_TIMEZONE'));
            $start = new DateTime($time_start, $system_timezone);
            $start->setTimezone($china_timezone);
            $time_start = $start->format("YmdHis");

            $expire = new DateTime($time_expire, $system_timezone);
            $expire->setTimezone($china_timezone);
            $time_expire = $expire->format("YmdHis");
        }

        $input = new WxPayUnifiedOrder();
        $input->SetBody($detail);
        $input->SetAttach($detail);
        $input->SetOut_trade_no($cart->id);
        $input->SetTotal_fee($total);
        $input->SetTime_start($time_start);
        $input->SetTime_expire($time_expire);
        $input->SetNotify_url(Configuration::get('WEIXIN_NOTIFY_URL'));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);

        return $order;
    }
}
