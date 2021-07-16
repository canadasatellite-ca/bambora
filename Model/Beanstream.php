<?php
namespace CanadaSatellite\Bambora\Model;
use CanadaSatellite\Bambora\BankCardNetworkDetector;
use CanadaSatellite\Bambora\Facade as F;
use Magento\Framework\DataObject as _DO;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Framework\ObjectManager\NoninterceptableInterface as INonInterceptable;
use Magento\Framework\Phrase;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Quote\Api\Data\CartInterface as ICart;
use Magento\Quote\Model\Quote as Q;
use Magento\Quote\Model\Quote\Payment as QP;
use Magento\Sales\Model\Order\Payment as OP;
# 2021-06-27 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
final class Beanstream extends \Magento\Payment\Model\Method\Cc implements INonInterceptable {
	const CODE = 'beanstream';
	protected $_code = self::CODE;
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = false;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid = true;
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;
	protected $_canUseForMultishipping = true;
	protected $_canSaveCc = false;
	protected $_canOrder = false;

	/**
	 * 2021-06-27 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @override
	 * @see \Magento\Payment\Model\MethodInterface::authorize()
	 * @used-by \Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation::authorize()
	 * https://github.com/magento/magento2/blob/2.1.5/app/code/Magento/Sales/Model/Order/Payment/Operations/AuthorizeOperation.php#L45
	 * 2021-07-01
	 * $a is a string because it is a result of the @see \Magento\Sales\Model\Order\Payment::formatAmount() call:
	 * 		$amount = $payment->formatAmount($amount, true);
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment/Operations/AuthorizeOperation.php#L36
	 * @param II|I|OP $i
	 * @param string|float $a
	 * @return $this
	 * @throws LE
	 */
	function authorize(II $i, $a) {
		$m = false; /** @var string|false $m */
		$res = F::p($this, F::AUTH_ONLY, $a); /** @var _DO $res */
		$i->setCcApproval($res->getApprovalCode())->setLastTransId($res->getTransactionId())->setCcTransId($res->getTransactionId())->setCcAvsStatus($res->getAvsResultCode())->setCcCidStatus($res->getCardCodeResponseCode());
		$reasonC = $res->getResponseReasonCode();
		$reasonS = $res->getResponseReasonText();
		switch ($res->getResponseCode()) {
			case self::$APPROVED:
				$i->setStatus(self::STATUS_APPROVED);
				if ($res->getTransactionId() != $i->getParentTransactionId()) {
					$i->setTransactionId($res->getTransactionId());
				}
				$i->setIsTransactionClosed(0)->setTransactionAdditionalInfo('real_transaction_id', $res->getTransactionId());
				break;
			case 2:
				$m = "Payment authorization transaction has been declined. \n$reasonS";
				break;
			default:
				$m = "Payment authorization error. \n$reasonS";
		}
		if ($m) {
			dfp_report($this, [/*'request' => $req->getData(), */'response' => $res->getData()]);
			self::err($m);
		}
		return $this;
	}

	/**
	 * 2021-06-27 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @override
	 * @see \Magento\Payment\Model\MethodInterface::capture()
	 * @used-by \Magento\Sales\Model\Order\Payment\Operations\CaptureOperation::capture():
	 * 		$method->capture($payment, $amountToCapture);
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment/Operations/CaptureOperation.php#L82
	 * 2021-07-01
	 * $a is a string because it is a result of the @see \Magento\Sales\Model\Order\Payment::formatAmount() call:
	 * 		$amountToCapture = $payment->formatAmount($invoice->getBaseGrandTotal());
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment/Operations/CaptureOperation.php#L37
	 * @param II|I|OP $i
	 * @param string|float $a
	 * @return $this
	 * @throws LE
	 */
	function capture(II $i, $a) {
		$m = false; /** @var string|false $m */
		$type = $i->getParentTransactionId() ? F::PRIOR_AUTH_CAPTURE : F::AUTH_CAPTURE; /** @var string $type */
		$res = F::p($this, $type, $a); /** @var _DO $res */
		if ($res->getResponseCode() == self::$APPROVED) {
			$i->setStatus(self::STATUS_APPROVED);
			$i->setCcTransId($res->getTransactionId());
			$i->setLastTransId($res->getTransactionId());
			if ($res->getTransactionId() != $i->getParentTransactionId()) {
				$i->setTransactionId($res->getTransactionId());
			}
			$i->setIsTransactionClosed(0)->setTransactionAdditionalInfo('real_transaction_id', $res->getTransactionId());
		}
		else {
			$m = $res->getResponseReasonText() ?: 'Error in capturing the payment';
			$oq = $i->getOrder() ?: $i->getQuote();
			$oq->addStatusToHistory($oq->getStatus(), urldecode($m) . ' at Beanstream', $m . ' from Beanstream');
		}
		if ($m) {
			dfp_report($this, [/*'request' => $req->getData(), */'response' => $res->getData()]);
			self::err($m);
		}
		return $this;
	}

	/**
	 * 2021-06-27 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @override
	 * @see \Magento\Payment\Model\MethodInterface::isAvailable()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/MethodInterface.php#L343-L350
	 * @see \Magento\Payment\Model\Method\AbstractMethod::isAvailable()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/Method/AbstractMethod.php#L805-L825
	 * @used-by \Magento\Payment\Block\Form\Container::getMethods()
	 * @used-by \Magento\Payment\Helper\Data::getStoreMethods()
	 * @used-by \Magento\Payment\Model\MethodList::getAvailableMethods()
	 * @used-by \Magento\Quote\Model\Quote\Payment::importData()
	 * @used-by \Magento\Sales\Model\AdminOrder\Create::_validate()
	 * @param ICart|Q|null $q
	 * @return array|bool|mixed|null
	 */
	function isAvailable(ICart $q = null) {/** @var bool $r */
		if ($r = $this->isActive($q ? $q->getStoreId() : null)) {
			df_dispatch('payment_method_is_active', ['method_instance' => $this, 'quote' => $q,
				'result' => ($evR = new _DO(['is_available' => true])) /** @var _DO $evR */
			]);
			$r = $evR['is_available'];
		}
		return $r;
	}

	/**
	 * 2021-06-28 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * 2021-07-02
	 * $a is a string because it is a result of the @see \Magento\Sales\Model\Order\Payment::formatAmount() call:
	 * 		$baseAmountToRefund = $this->formatAmount($creditmemo->getBaseGrandTotal());
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment.php#L655
	 * @override
	 * @see \Magento\Payment\Model\MethodInterface::refund()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/MethodInterface.php#L269-L277
	 * @see \Magento\Payment\Model\Method\AbstractMethod::refund()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/Method/AbstractMethod.php#L640-L656
	 * @used-by \Magento\Sales\Model\Order\Payment::refund()
	 * 		$gateway->refund($this, $baseAmountToRefund);
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment.php#L684
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/Order/Payment.php#L701
	 * @param II|I|OP $payment
	 * @param string|float $a
	 * @return $this
	 */
	function refund(II $i, $a) {
		$m = false; /** @var Phrase|string|false $m */
		# 2021-07-06 A string like «10000003».
		df_assert_sne($parentId = $i->getParentTransactionId()); /** @var string $parentId */
		$res = F::p($this, 'REFUND', $a);
		if ($res->getResponseCode() == self::$APPROVED) {
			$i->setStatus(self::STATUS_SUCCESS);
			if ($res->getTransactionId() != $parentId) {
				$i->setTransactionId($res->getTransactionId());
			}
			$sp41f7d8 = $i->getOrder()->canCreditmemo() ? 0 : 1;
			$i->setIsTransactionClosed(1)->setShouldCloseParentTransaction($sp41f7d8)->setTransactionAdditionalInfo('real_transaction_id', $res->getTransactionId());
		}
		else {
			$m = $res->getResponseReasonText();
		}
		if ($m !== false) {
			self::err($m);
		}
		return $this;
	}
	
	/**
	 * 2021-06-28 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @override
	 * How is a payment method's validate() used? https://mage2.pro/t/698
	 * @see \Magento\Payment\Model\MethodInterface::validate()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/MethodInterface.php#L230-L237
	 * @see \Magento\Payment\Model\Method\AbstractMethod::validate()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/Method/AbstractMethod.php#L566-L583
	 * @used-by \Magento\Quote\Model\Quote\Payment::importData()
	 * 		$method->validate();
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Quote/Model/Quote/Payment.php#L202
	 * @used-by \Magento\Sales\Model\AdminOrder\Create::_validate()
	 * 		$method->validate();
	 * https://github.com/magento/magento2/blob/2.3.5-p2/app/code/Magento/Sales/Model/AdminOrder/Create.php#L2012
	 * @used-by \Magento\Sales\Model\Order\Payment::place()
	 * 		$methodInstance->validate();
	 * @return $this
	 * @throws LE
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	function validate() {
		$i = $this->getInfoInstance(); /** @var QP $i */
		$i->setCcNumber($n = preg_replace('/[\-\s]+/', '', $i->getCcNumber()));
		$i->setCcType(BankCardNetworkDetector::p($n));
		return parent::validate();
	}	

	/**
	 * 2021-06-28 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @override
	 * @see \Magento\Payment\Model\MethodInterface::void()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/MethodInterface.php#L288-L295
	 * @see \Magento\Payment\Model\Method\AbstractMethod::void()
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Payment/Model/Method/AbstractMethod.php#L671-L686
	 * @param II|I|OP $i
	 * @return $this
	 * @uses _void()
	 */
	function void(II $i) {
		# 2021-07-06 A string like «10000003».
		df_assert_sne($parentId = $i->getParentTransactionId()); /** @var string $parentId */
		$res = F::p($this, F::VOID, 0.0);
		if (self::$APPROVED != $res->getResponseCode()) {
			self::err($res->getResponseReasonText());
		}
		$i->setStatus(self::STATUS_VOID);
		if ($res->getTransactionId() != $parentId) {
			$i->setTransactionId($res->getTransactionId());
		}
		$i->setIsTransactionClosed(1);
		$i->setShouldCloseParentTransaction(1);
		$i->setTransactionAdditionalInfo('real_transaction_id', $res->getTransactionId());
		return $this;
	}

	/**
	 * 2021-06-29 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by capture()
	 * @used-by refund()
	 * @used-by void()
	 * @param Phrase|string|null $m [optional]
	 * @throws LE
	 */
	private static function err($m = null) {throw new LE(__($m ?: 'Payment error occurred.'));}

	/**
	 * 2021-07-01 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by capture()
	 * @used-by refund()
	 * @used-by void()
	 * @var int
	 */
	private static $APPROVED = 1;
}