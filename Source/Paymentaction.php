<?php
namespace CanadaSatellite\Bambora\Source;
use Magento\Payment\Model\Method\Cc;
# 2021-07-21 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
class Paymentaction {
	/**
	 * 2021-07-21
	 * @return array(array(string => string))
	 */
	function toOptionArray() {return [
		['value' => Cc::ACTION_AUTHORIZE, 'label' => 'Authorize Only']
		,['value' => Cc::ACTION_AUTHORIZE_CAPTURE, 'label' => 'Sale']
	];}
}