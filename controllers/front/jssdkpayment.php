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
            $id_order = $this->generateOrder($cart->id);
            $cart = new Cart($cart->id);
            $order = new Order($id_order);
            $result = $this->unifiedOrder($cart, $order->reference, Tools::getValue('openID'));
            die(Tools::jsonEncode(array(
                'statusCode' => 1,
                'reference' => $order->reference,
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

    public function generateQR($cart)
    {
        $signkey = Configuration::get('WEIXIN_KEY');
        $appid = Configuration::get('WEIXIN_APPID');
        $merchart = Configuration::get('WEIXIN_MCH_ID');
        $params = array();
        $params['appid'] = $appid;
        $params['mch_id'] = $merchart;
        $params['nonce_str'] = md5(time());
        $params['product_id'] = $cart->id;
        $params['time_stamp'] = time();
        ksort($params);
        $params_str = "";
        foreach ($params as $key => $val) {
            if (isset ($val) && @$val != "") {
                $params_str .= $key . "=" . $val . "&";
            }
        }
        $sign_str = $params_str . "key=" . $signkey;
        $sign = Tools::strtoupper(md5($sign_str));
        $url = "weixin://wxpay/bizpayurl?" . $params_str . "sign=" . $sign;
        return $url;
    }

    /**
     * JSSDK 支付调用统一下单过程
     * @param $cart
     * @param $reference
     * @param $openId
     * @return 成功时返回
     * @throws WxPayException
     */
    public function unifiedOrder($cart, $reference, $openId)
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
        $input->SetOut_trade_no($reference);
        $input->SetTotal_fee($total);
        $input->SetTime_start($time_start);
        $input->SetTime_expire($time_expire);
        $input->SetNotify_url(Configuration::get('WEIXIN_NOTIFY_URL'));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);

        return $order;
    }

    public function generateOrder($id_cart)
    {
        $cart = new Cart((int)$id_cart);

        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $currency = new Currency(Currency::getIdByIsoCode(WXP_CURRENCY_ISO_CODE));
        if (!Validate::isLoadedObject($currency)) {
            return false;
        }

        $weixinpay = new Weixinpay();
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        if ($weixinpay->validateOrder($cart->id, Configuration::get('WEIXIN_STATUS_AWAITING'), $total, $weixinpay->displayName, null, array(), (int)$currency->id, false, $customer->secure_key)) {
            return $weixinpay->currentOrder;
        }

        return false;
    }

    public function changeOrderStatus($result_order)
    {
        $orders = Order::getByReference($result_order);
        $isOk = true;
        if ($orders) {
            foreach ($orders as $order) {
                $isOk &= $this->changeOrderStatusSub($order->id);
            }
        }
        return $isOk;
    }

    public function changeOrderStatusSub($id_order)
    {
        Log::DEBUG("change:1." . $id_order);
        if ($id_order) {
            Log::DEBUG("change:2." . $id_order);
            $lastHistory = OrderHistory::getLastOrderState($id_order);
            if ($lastHistory->id == Configuration::get('PS_OS_PAYMENT')) {
                Log::DEBUG("change:2.1." . $id_order);

                return true;
            }

            $history = new OrderHistory();
            $history->id_order = (int)$id_order;
            $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), (int)$id_order);
            Log::DEBUG("change:3." . $id_order);
            if ($history->addWithemail()) {
                Log::DEBUG("change:4." . $id_order);
                return true;
            }
        }
        return false;
    }
}
