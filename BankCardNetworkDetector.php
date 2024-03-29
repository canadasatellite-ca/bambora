<?php
namespace CanadaSatellite\Bambora;
# 2021-07-14
final class BankCardNetworkDetector {
	/**
	 * 2021-06-27
	 * @used-by \CanadaSatellite\Bambora\Method::validate()
	 * @see \Df\Payment\BankCardNetworkDetector::p()
	 * @param string $n
	 * @return string
	 */
	static function p($n) {
		$r = null; /** @var string|null $list */
		$list = [
			//Solo, Switch or Maestro. International safe
			'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
			'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)' .
				'|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)' .
				'|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))' .
				'|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))' .
				'|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
			// Visa
			'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
			// Master Card
			'MC' => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/',
			// American Express
			'AE' => '/^3[47][0-9]{13}$/',
			// Discover
			'DI' => '/^(6011((0|9|[2-4])[0-9]{11,14}|(74|7[7-9]|8[6-9])[0-9]{10,13})|6(4[4-9][0-9]{13,16}|' .
				'5[0-9]{14,17}))/',
			'DN' => '/^3(0[0-5][0-9]{13,16}|095[0-9]{12,15}|(6|[8-9])[0-9]{14,17})/',
			// UnionPay
			'UN' => '/^622(1(2[6-9][0-9]{10,13}|[3-9][0-9]{11,14})|[3-8][0-9]{12,15}|9([[0-1][0-9]{11,14}|' .
				'2[0-5][0-9]{10,13}))|62[4-6][0-9]{13,16}|628[2-8][0-9]{12,15}/',
			// JCB
			'JCB' => '/^35(2[8-9][0-9]{12,15}|[3-8][0-9]{13,16})/',
			'MI' => '/^(5(0|[6-9])|63|67(?!59|6770|6774))\d*$/',
			'MD' => '/^(6759(?!24|38|40|6[3-9]|70|76)|676770|676774)\d*$/',
		];
		foreach($list as $brand => $exp) {
			if (preg_match($exp, $n)){
				$r = $brand;
				break;
			}
		}
		return $r;
	}
}