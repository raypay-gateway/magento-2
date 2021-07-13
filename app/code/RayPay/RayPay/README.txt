== RayPay Gateway
Contributors: hanieh729
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

After installing and enabling this plugin, your customers can pay through RayPay gateway.
For doing a transaction through RayPay gateway, you must have UserID and AcceptorCode. You can obtain the these parameters by going to your RayPay account(https://panel.raypay.ir) .

== Installation/Usage

after copying the plugin code into app directory, run the following commands in magento_root directory

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush

the you should be able to see RayPay payment method in:
Stores -> Configuration -> Sales -> Payment Methods -> Other Payment Methods -> RayPay

== Change log

- 08/10/2021  V 1.0.0 Initial revision

