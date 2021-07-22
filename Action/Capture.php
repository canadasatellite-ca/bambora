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
			dfp_report($this, ['request' => $op->req(), 'response' => $res->a()]);
			df_error($res->reason());
		}
		$i->setStatus(M::STATUS_APPROVED);
		$i->setCcTransId($res->trnId());
		$i->setLastTransId($res->trnId());
		if ($res->trnId() != $i->getParentTransactionId()) {
			$i->setTransactionId($res->trnId());
		}
		$i->setIsTransactionClosed(0);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->trnId());
	}
}