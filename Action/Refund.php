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
final class Refund extends \CanadaSatellite\Bambora\Action {
	/**
	 * 2021-07-22
	 * @used-by \CanadaSatellite\Bambora\Method::refund()
	 * @param string|float $a
	 * @throws DFE
	 */
	function p($a) {
		$i = $this->ii(); /** @var II|I|OP $i */
		# 2021-07-06 A string like «10000003».
		df_assert_sne($parentId = $i->getParentTransactionId()); /** @var string $parentId */
		$op = $this->check(F::p($this, 'REFUND', $a)); /** @var Operation $op */
		$res = $op->res(); /** @var Response $res */
		$i->setStatus(M::STATUS_SUCCESS);
		if ($res->trnId() != $parentId) {
			$i->setTransactionId($res->trnId());
		}
		$i->setIsTransactionClosed(1);
		$i->setShouldCloseParentTransaction($i->getOrder()->canCreditmemo() ? 0 : 1);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->trnId());
	}

	/**
	 * 2021-07-23
	 * 1) «The request strings for these three types of transactions
	 * will vary only in the value passed in the `trnType` field (R=Return, VP=Void Purchase, VR=Void Return).»
	 * https://mage2.pro/t/6280, Page 35.
	 * 2) «A void is the removal of the entire amount,
	 * while a return will allow you do partial to full refunds of a transaction.
	 * The amount sent in needs to reflect this, otherwise it will be rejected from our system.»
	 * https://mage2.pro/t/6280, Page 35.
	 * 3)
	 * 	«*) R=Return
	 * 	*) VR=Void Return
	 *	*) V=Void
	 *	*) VP=Void Purchase
	 *	*) PAC=Pre-Authorization Completion
	 * If omitted, this field will default to P for purchase.
	 * Please note that "R" is the only valid adjustment for INTERAC Online.»
	 * https://mage2.pro/t/6280, Page 39.
	 * 4) «What’s the difference between a "Void" and a "Return"?
	 * 4.1) A "Void" can only be done for the full transaction amount
	 * and must be completed before the credit card company posts the purchase to the credit card owner’s account.
	 * Voided transactions will not show up on the customer’s statement.
	 * 4.2) A return can be processed at any time for any amount up to the full purchase value.
	 * Use the "Return" option to process a full or partial refund.»
	 * https://mage2.pro/t/6282, Page 9.
	 * @override
	 * @see \CanadaSatellite\Bambora\Action::trnType()
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return string
	 */
	function trnType() {return 'R';}
}