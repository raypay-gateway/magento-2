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
namespace RayPay\RayPay\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Afterpayment extends Status
{
    protected $_stateStatuses = [
        Order::STATE_HOLDED,
        Order::STATE_CANCELED,
        Order::STATE_CLOSED,
        Order::STATE_COMPLETE,
        Order::STATE_NEW,
        Order::STATE_PAYMENT_REVIEW,
        Order::STATE_PROCESSING
    ];
}
