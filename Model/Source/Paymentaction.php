<?php
namespace CanadaSatellite\Bambora\Model\Source;
class Paymentaction
{
    function toOptionArray()
    {
        return array(array('value' => \Magento\Payment\Model\Method\Cc::ACTION_AUTHORIZE, 'label' => 'Authorize Only'), array('value' => \Magento\Payment\Model\Method\Cc::ACTION_AUTHORIZE_CAPTURE, 'label' => 'Sale'));
    }
}