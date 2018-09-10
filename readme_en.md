## Note:
This module allows you to accept secure payments by WeChat payment.
WeChat payment currently only supports the Chinese currency, currency ISO code:CNY
Please make sure that your website has been added to the CNY currency.

### Install
1. Download weixinpay.zip
2. Extract weixinpay.zip
3. Copy weixinpay folder to /moudles
4. chomd 777 modules/logs and  chomd 777 modules/views/cache 
5. Now,You can Background>>modules>>WeChat Pay

### 2016/10/25 v1.0.4 update
1. Add WeChat payment "Transaction ID",Now you can see it at Order -> Order detail -> Payment -> Transaction ID。
2. Modify payment process,After entering the WeChat payment page can be returned to choose other payments, but the premise is that the user does not scan the QRcode.
3. Add WeChat public number pay.
(The update, you need to configure a wide range of parameters WeChat, please strictly in accordance with the document configuration"2016-10-25升级说明文档.docx")

### 2015/11/21 v1.0.2.1 update
1. fixed BUG, when the background switch template, the translation does not show the Chinese problem.
2. fixed BUG, when the user pays the success, will send e-mail to customers.

### 2015/11/12 v1.0.2 update
1. Update transaction settlement process,more：
Users scan the two-dimensional code before generating the "Awaiting WeChat Payment" status of the order in the background,
After the user scans the two-dimensional code and pay the success, the change of the status of the order has been paid for success
2. add "Awaiting WeChat Payment" status
3. Merchant orders number will display the order reference
4. Delete payment page button "other payment" button