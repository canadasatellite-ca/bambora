<?php
namespace CanadaSatellite\Bambora\Action;
use CanadaSatellite\Bambora\Facade as F;
use CanadaSatellite\Bambora\Method as M;
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
final class Authorize extends \CanadaSatellite\Bambora\Action {
	/**
	 * 2021-07-22
	 * @used-by \CanadaSatellite\Bambora\Method::authorize()
	 * @param string|float $a
	 * @throws DFE
	 */
	function p($a) {
		$op = F::p($this, F::AUTH_ONLY, $a); /** @var Operation $op */
		$res = $op->res(); /** @var Response $res */
		$i = $this->ii(); /** @var II|I|OP $i */
		$i->setCcApproval($res->authCode());
		$i->setCcAvsStatus($res->avsResult());
		$i->setCcCidStatus($res->avsResult());
		$i->setCcTransId($res->trnId());
		$i->setLastTransId($res->trnId());
		if (!$res->trnApproved()) {
			dfp_report($this, ['request' => $op->req(), 'response' => $res->a()]);
			df_error($res->reason());
		}
		$i->setStatus(M::STATUS_APPROVED);
		if ($res->trnId() != $i->getParentTransactionId()) {
			$i->setTransactionId($res->trnId());
		}
		$i->setIsTransactionClosed(0);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->trnId());
	}

	/**
	 * 2021-07-23
	 * «`trnType` field must be included specifying the value PA for Pre-Authorization.»
	 * https://mage2.pro/t/6280, Page 34.
	 * @override
	 * @see \CanadaSatellite\Bambora\Action::trnType()
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return string
	 */
	protected function trnType() {return 'PA';}
}