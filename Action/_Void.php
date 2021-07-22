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
final class _Void extends \CanadaSatellite\Bambora\Action {
	/**
	 * 2021-07-22
	 * @used-by \CanadaSatellite\Bambora\Method::void()
	 * @throws DFE
	 */
	function p() {
		$i = $this->ii(); /** @var II|I|OP $i */
		# 2021-07-06 A string like «10000003».
		df_assert_sne($parentId = $i->getParentTransactionId()); /** @var string $parentId */
		$op = F::p($this, F::VOID, 0.0); /** @var Operation $op */
		$res = $op->res(); /** @var Response $res */
		if (!$res->trnApproved()) {
			dfp_report($this, ['request' => $op->req(), 'response' => $res->a()]);
			df_error($res->reason());
		}
		$i->setStatus(M::STATUS_VOID);
		if ($res->trnId() != $parentId) {
			$i->setTransactionId($res->trnId());
		}
		$i->setIsTransactionClosed(1);
		$i->setShouldCloseParentTransaction(1);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->trnId());
	}
}