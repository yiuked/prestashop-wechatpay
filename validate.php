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

include(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . 'weixinpay/defines.php');
require_once(_PS_MODULE_DIR_ . 'weixinpay/weixinpay.php');
require_once(WXP_MODDULE_DIR . 'classes/lib/WxPay.Notify.php');
require_once(WXP_MODDULE_DIR . 'classes/Log.php');

//初始化日志
$logHandler = new CLogFileHandler(WXP_MODDULE_DIR . WXP_MODDULE_LOGS . date('Y-m-d') . '.log');
$log = Log::init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
    public function notifyProcess($data, &$msg)
    {
        Log::DEBUG("call back(validate.php:notifyProcess):" .  Tools::jsonEncode($data));

        if (!array_key_exists("openid", $data) || !array_key_exists("product_id", $data)) {
            Log::DEBUG("call back:回调数据异常");
            return false;
        }

        $cart_id = $data["product_id"];
        $openid = $data["openid"];

        Log::DEBUG("ccall back(notify.php:notifyProcess):开始生成定单");
        $id_order = $this->generateOrder($cart_id);
        $cart = new Cart($cart_id);
        $order = new Order($id_order);
        Log::DEBUG("001");
        //统一下单
        $result = $this->unifiedOrder($cart, $order->reference, $openid);
        if (!array_key_exists("appid", $result) || !array_key_exists("mch_id", $result) || !array_key_exists("prepay_id", $result)) {
            Log::DEBUG("call back:统一下单失败");
            return false;
        }

        $this->SetData("appid", $result["appid"]);
        $this->SetData("mch_id", $result["mch_id"]);
        $this->SetData("nonce_str", WxPayApi::getNonceStr());
        $this->SetData("prepay_id", $result["prepay_id"]);
        $this->SetData("result_code", "SUCCESS");
        $this->SetData("return_code", "SUCCESS");
        $this->SetData("err_code_des", "OK");
        Log::DEBUG("ccall back(notify.php:notifyProcess):生成回调数据");
        return true;
    }

    public function unifiedOrder($cart, $reference, $openid)
    {
        Log::DEBUG("002");
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
        Log::DEBUG("003");
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
        $input->SetAttach("test");
        $input->SetDetail($detail);
        $input->SetOut_trade_no($reference);
        $input->SetTotal_fee($total);
        $input->SetTime_start($time_start);
        $input->SetTime_expire($time_expire);
        $input->SetGoods_tag("test");
        $input->SetNotify_url(Configuration::get('WEIXIN_NOTIFY_URL'));
        $input->SetTrade_type("NATIVE");
        $input->SetOpenid($openid);
        $input->SetProduct_id($reference);
        Log::DEBUG("004");
        try {
            $result = WxPayApi::unifiedOrder($input);
        } catch (Exception $e) {
            Log::DEBUG("unifiedorder:" . print_r($e, true));
        }
        Log::DEBUG("unifiedorder:" . Tools::jsonEncode($result));
        return $result;
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
}
Log::DEBUG("call back:begin validate");
$notify = new PayNotifyCallBack();
$notify->handle(true);
