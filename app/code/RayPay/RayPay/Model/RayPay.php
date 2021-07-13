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
namespace RayPay\RayPay\Model;

class RayPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'raypay';
    protected $_isOffline = false;
}
