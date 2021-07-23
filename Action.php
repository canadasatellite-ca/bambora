<?php
namespace CanadaSatellite\Bambora;
use CanadaSatellite\Bambora\Method as M;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Quote\Model\Quote\Payment as QP;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Payment as OP;
/**
 * 2021-07-22
 * @see \CanadaSatellite\Bambora\Action\Authorize
 * @see \CanadaSatellite\Bambora\Action\Capture
 * @see \CanadaSatellite\Bambora\Action\Refund
 * @see \CanadaSatellite\Bambora\Action\_Void
 */
abstract class Action {
	/**
	 * 2021-07-22
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @see \CanadaSatellite\Bambora\Action\Authorize::trnType()
	 * @see \CanadaSatellite\Bambora\Action\Capture::trnType()
	 * @see \CanadaSatellite\Bambora\Action\Refund::trnType()
	 * @see \CanadaSatellite\Bambora\Action\_Void::trnType()
	 * @return string
	 */
	abstract protected function trnType();

	/**
	 * 2021-07-22
	 * @used-by ii()
	 * @used-by \CanadaSatellite\Bambora\Action\Authorize::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Capture::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Refund::p()
	 * @used-by \CanadaSatellite\Bambora\Action\_Void::p()
	 * @return M
	 */
	final function m() {return $this->_m;}

	/**
	 * 2021-07-22
	 * @used-by o()
	 * @used-by \CanadaSatellite\Bambora\Action\Authorize::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Capture::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Refund::p()
	 * @used-by \CanadaSatellite\Bambora\Action\_Void::p()
	 * @return II|I|OP|QP
	 */
	final protected function ii() {return $this->m()->getInfoInstance();}

	/**
	 * 2021-07-22
	 * @return O
	 */
	final protected function o() {return $this->ii()->getOrder();}

	/**
	 * 2021-07-22
	 * @used-by s()
	 * @param M $m
	 */
	private function __construct(M $m) {$this->_m = $m;}

	/**
	 * 2021-07-22
	 * @used-by __construct()
	 * @used-by cfg()
	 * @used-by ii()
	 * @var M
	 */
	private $_m;

	/**
	 * 2021-07-22
	 * @final I do not use the PHP «final» keyword here to allow refine the return type using PHPDoc.
	 * @used-by \CanadaSatellite\Bambora\Method::void()
	 * @param M $m
	 * @return self
	 */
	static function s(M $m) {return dfcf(function($c) use($m) {return new $c($m);}, [static::class]);}
}