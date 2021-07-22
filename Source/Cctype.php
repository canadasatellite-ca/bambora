<?php
namespace CanadaSatellite\Bambora\Source;
# 2021-07-21 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
class Cctype extends \Magento\Payment\Model\Source\Cctype {
	/**
	 * 2021-07-21
	 * @return array(array(string => string))
	 */
	function toOptionArray() {return [
		['value' => 'VI', 'label' => 'VISA']
		,['value' => 'MC', 'label' => 'MasterCard']
		,['value' => 'AE', 'label' => 'American Express']
		,['value' => 'DI', 'label' => 'Discover']
		,['value' => 'OT', 'label' => 'Others']
	];}
}