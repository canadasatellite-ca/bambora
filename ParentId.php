<?php
namespace CanadaSatellite\Bambora;
use Magento\Sales\Model\Order\Payment as P;
# 2021-07-23
final class ParentId {
	/**
	 * 2021-07-23
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return int
	 */
	static function get(P $p) {return $p->getCcTransId();}

	/**
	 * 2021-07-23
	 * @used-by \CanadaSatellite\Bambora\Action\Authorize::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Capture::p()
	 * @param P $p
	 * @param int $v
	 */
	static function set(P $p, $v) {$p->setCcTransId($v);}
}
