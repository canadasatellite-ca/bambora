<?php
namespace CanadaSatellite\Bambora\Action;
use CanadaSatellite\Bambora\Facade as F;
use CanadaSatellite\Bambora\Method as M;
use CanadaSatellite\Bambora\ParentId;
use CanadaSatellite\Bambora\Response;
use Df\API\Operation;
use Df\Core\Exception as DFE;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Sales\Model\Order\Payment as OP;
/**
 * 2021-07-22
 * @method static $this s(M $m)
 */
final class Capture extends \CanadaSatellite\Bambora\Action {
	/**
	 * 2021-07-22
	 * @used-by \CanadaSatellite\Bambora\Method::capture()
	 * @param string|float $a
	 * @throws DFE
	 */
	function p($a) {
		$i = $this->ii(); /** @var II|I|OP $i */
		$type = $i->getParentTransactionId() ? F::PRIOR_AUTH_CAPTURE : F::AUTH_CAPTURE; /** @var string $type */
		$op = F::p($this, $type, $a); /** @var Operation $op */
		$res = $op->res(); /** @var Response $res */
		if (!$res->trnApproved()) {
			$oq = $i->getOrder() ?: $i->getQuote();
			$oq->addStatusToHistory($oq->getStatus(), $res->reason());
			dfp_report($i, ['request' => $op->req(), 'response' => $res->a()]);
			df_error($res->reason());
		}
		$i->setStatus(M::STATUS_APPROVED);
		ParentId::set($i, $res->trnId());
		$i->setLastTransId($res->trnId());
		if ($res->trnId() != $i->getParentTransactionId()) {
			$i->setTransactionId($res->trnId());
		}
		$i->setIsTransactionClosed(0);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->trnId());
	}

	/**
	 * 2021-07-23
	 * 1) «Specify `trnType=PA` to process a pre-authorization against a customer's credit card.
	 * If omitted, this option will default to P for purchase.» https://mage2.pro/t/6280, Page 35.
	 * 2) «A Pre-Authorization Completion (`PAC`) is the second part of a pre-authorization.» https://mage2.pro/t/6283, Page 45.
	 * @override
	 * @see \CanadaSatellite\Bambora\Action::trnType()
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return string
	 */
	function trnType() {return $this->ii()->getParentTransactionId() ? 'PAC' : 'P';}
}