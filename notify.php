<?php
/**
 * 2010-2016 Yiuked
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
require_once(WXP_MODDULE_DIR . 'weixinpay.php');
require_once(WXP_MODDULE_DIR . 'classes/lib/WxPay.Notify.php');
require_once(WXP_MODDULE_DIR . 'classes/Log.php');

//初始化日志
$logHandler = new CLogFileHandler(WXP_MODDULE_DIR . WXP_MODDULE_LOGS . date('Y-m-d') . '.notify.log');
$log = Log::init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function queryOrder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Log::DEBUG($transaction_id . "查询结果:" . Tools::jsonEncode($result));
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            if (!$this->generateOrder($result)) {
                Log::DEBUG($transaction_id . "在生成定单时出现错误");
            }
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function notifyProcess($data, &$msg)
    {
        Log::DEBUG("HTTP_RAW_POST_DATA解析后数据:" . Tools::jsonEncode($data));

        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }

        //查询订单，判断订单真实性
        if (!$this->queryOrder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }

        return true;
    }

    public function generateOrder($order_query_result)
    {
        $id_cart = (int)$order_query_result['out_trade_no'];
        $valida_total = (int)$order_query_result['total_fee'];
        $transaction_id = $order_query_result['transaction_id'];

        $cart = new Cart($id_cart);
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
        $total_conver = $valida_total / 100;
        $order_status = _PS_OS_PAYMENT_;
        if ($weixinpay->validateOrder($cart->id, $order_status, $total_conver, $weixinpay->displayName, null, array(), (int)$currency->id, false, $customer->secure_key)) {
            $order = new Order($weixinpay->currentOrder);
            $orderPayments = OrderPayment::getByOrderReference($order->reference);
            foreach ($orderPayments as $payment) {
                $payment->transaction_id = $transaction_id;
                $payment->update();
            }
            return $weixinpay->currentOrder;
        }
        return false;
    }
}
ob_start();
$notify = new PayNotifyCallBack();
$notify->handle(false);
$content = ob_get_contents();
Log::DEBUG("页面调试输出:" . $content);
echo $content;
