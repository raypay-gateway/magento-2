<?php
/**
 * RayPay payment gateway
 *
 * @developer hanieh729
 * @publisher RayPay
 * @copyright (C) 2021 RayPay
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * https://raypay.ir
 */
namespace RayPay\RayPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\UrlInterface;

class AfterPlaceOrder implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $observer->getEvent()->getOrder()->getId();

        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
        setcookie('raypay_order_id', $orderId, time()+3600, '/', $domain, false);
    }

}
