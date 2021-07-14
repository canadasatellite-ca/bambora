<?php
namespace CanadaSatellite\Bambora\Model;
use CanadaSatellite\Bambora\Regions;
use Magento\Framework\DataObject as _DO;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Framework\ObjectManager\NoninterceptableInterface as INonInterceptable;
use Magento\Framework\Phrase;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Quote\Api\Data\CartInterface as ICart;
use Magento\Quote\Model\Quote as Q;
use Magento\Quote\Model\Quote\Payment as QP;
use Magento\Sales\Model\Order as O;
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
		$req = $this->buildRequest($i, self::$AUTH_ONLY, $a); /** @var _DO $req */
		$res = $this->postRequest($req, self::$AUTH_ONLY); /** @var _DO $res */
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
			dfp_report($this, ['request' => $req->getData(), 'response' => $res->getData()]);
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
		$type = $i->getParentTransactionId() ? self::$PRIOR_AUTH_CAPTURE : self::$AUTH_CAPTURE; /** @var string $type */
		/** @var _DO $req */
		$req = $this->buildRequest($i, $type, $a);
		$res = $this->postRequest($req, $type); /** @var _DO $res */
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
			dfp_report($this, ['request' => $req->getData(), 'response' => $res->getData()]);
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
		$req = $this->buildRequest($i, self::$REFUND, $a);
		$res = $this->postRequest($req, self::$REFUND);
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
		$i->setCcType(self::cardBrand($n));
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
		$req = $this->buildRequest($i, self::$VOID,  $i->getAmountAuthorized());
		$res = $this->postRequest($req, self::$VOID);
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
	 * 2021-06-27 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by validate()
	 * @see \Df\Payment\BankCardNetworkDetector::p()
	 * @param string $n
	 * @return string
	 */
	private static function cardBrand($n) {
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

	/**
	 * 2021-06-29 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by postRequest()
	 * @param array(string => mixed) $reqA
	 * @param string $type
	 * @return array
	 * @throws LE
	 */
	private function beanstreamapi(array $reqA, $type) {
		$reqA['x_state'] = dftr($reqA['x_state'], Regions::ca());
		if ($reqA[self::$COUNTRY] == '') {
			if ($reqA['x_state'] != '') {
				if (isset($statesCA[$reqA['x_state']])) {
					$reqA[self::$COUNTRY] = 'CA';
				}
				elseif (dfa(Regions::us(), $reqA['x_state'])) {
					$reqA[self::$COUNTRY] = 'US';
				}
			}
		}
		if ($reqA[self::$COUNTRY] == 'US') {
			$reqA['x_state'] = dftr($reqA['x_state'], Regions::us());
		}
		if ($reqA[self::$COUNTRY] != 'US' && $reqA[self::$COUNTRY] != 'CA') {
			$reqA['x_state'] = '--';
		}
		$trnType = 'P';
		$query2 = []; /** @var array(string => string) $query2 */
		if ($type == self::$AUTH_CAPTURE) {
			$trnType = 'P';
		}
		elseif ($type == self::$AUTH_ONLY) {
			$trnType = 'PA';
		}
		elseif ($type == self::$PRIOR_AUTH_CAPTURE) {
			$trnType = 'PAC';
			$query2 = ['adjId' => $reqA['x_trans_id']];
		}
		elseif ($type == self::$VOID) {
			$trnType = 'PAC';
			$spd28804 = explode('--', $reqA['x_trans_id']);
			$query2 = ['adjId' => $spd28804[0]];
			$reqA[self::$X_AMOUNT] = 0.0;
		}
		$query = http_build_query([
			# 2021-06-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# «Ensure that the Customer IP address is being passed in the API request for all transactions»:
			# https://github.com/canadasatellite-ca/site/issues/175
			# 2021-07-12 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) «Ensure that the Customer IP address is being passed in the API request for all transactions»:
			# https://github.com/canadasatellite-ca/site/issues/175
			# 2) I have not found the `customerIp` parameter in the documentation.
			# 3) The documentation mentions the `customer_ip` paramenter: https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/risk_thresholds/index.md#required-fields-for-transactions
			# It does not work.
			'customerIp' => df_visitor_ip()
			# 2021-06-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) «Unique identifier for your Bambora merchant account (not "merchantId")»
			# 2) «9 digits»
			# https://dev.na.bambora.com/docs/references/recurring_payment/#authorization
			,'merchant_id' => $this->getConfigData('merchant_id')
			,'ordAddress1' => $reqA['x_address']
			,'ordAddress2' => ''
			,'ordCity' => $reqA['x_city']
			,'ordCountry' => $reqA[self::$COUNTRY]
			,'ordEmailAddress' => $reqA['x_email']
			,'ordName' => df_cc_s($reqA['x_first_name'], $reqA['x_last_name'])
			,'ordPhoneNumber' => $reqA['x_phone']
			,'ordPostalCode' => $reqA['x_zip']
			,'ordProvince' => $reqA['x_state']
			,'password' => $this->getConfigData('merchant_password')
			,'requestType' => 'BACKEND'
			,'trnAmount' => $reqA[self::$X_AMOUNT]
			# 2021-06-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) «Include the 3 or 4-digit CVD number from the back of the customer's credit card.
			# CVD numbers are not stored in the Bambora system
			# and will only be used for a first recurring billing transaction if passed.»
			# 2) «4 digits Amex, 3 digits all other cards»
			# https://dev.na.bambora.com/docs/references/recurring_payment/#card-info
			# https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			,'trnCardCvd' => df_ets(dfa($reqA, self::$CVV))
			,'trnCardNumber' => $reqA[self::$CARD_NUMBER]
			,'trnCardOwner' => df_cc_s($reqA['x_first_name'], $reqA['x_last_name'])
			,'trnExpMonth' => $reqA[self::$CARD_EXP_MONTH]
			,'trnExpYear' => $reqA[self::$CARD_EXP_YEAR]
			,'trnOrderNumber' => $reqA['x_invoice_num']
			,'trnType' => $trnType
			,'username' => $this->getConfigData('merchant_username')
		] + $query2); /** @var string $query */
		$curl = curl_init();
		# 2021-07-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# 1) https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#request-parameters
		# 2) https://dev.na.bambora.com/docs/references/recurring_payment/#request-parameters
		curl_setopt($curl, CURLOPT_URL, 'https://www.beanstream.com/scripts/process_transaction.asp');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
		$spf8f74c = curl_exec($curl);
		$sp35fa42 = curl_error($curl);
		curl_close($curl);
		if ($sp35fa42 != '') {
			df_log_l($this, ['request' => $query, 'response' => $sp35fa42], 'error-curl');
			self::err('Error: ' . $sp35fa42);
		}
		# 2021-03-20 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# "Beanstream: «Microsoft OLE DB Driver for SQL Server» / «TCP Provider: The wait operation timed out» /
		# «C:\INETPUB\BEANSTREAM\ERRORPAGES\../admin/include/VBScript_ado_connection_v2.asp»":
		# https://github.com/canadasatellite-ca/site/issues/18
		if (df_contains($spf8f74c, 'Microsoft OLE DB Driver for SQL Server')) {
			df_log_l($this, ['request' => $query, 'response' => $spf8f74c], 'error-ole');
			self::err('Error: ' . $spf8f74c);
		}
		$sp1e8be2 = explode('&', $spf8f74c);
		$spb41165 = [];
		foreach (@$sp1e8be2 as $sp107d68) {
			list($sp005512, $sp5b9bbc) = explode('=', $sp107d68);
			$spb41165[$sp005512] = strip_tags(urldecode($sp5b9bbc));
		}
		# 2021-03-20 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# "Prevent the `Schogini_Beanstream` module from logging successful transactions to `beanstream.log`":
		# https://github.com/canadasatellite-ca/site/issues/17
		if ('N' !== ($errorType = dfa($spb41165, 'errorType', 'unknown'))) { /** @var string $errorType */
			df_log_l($this, [
				'request' => $query, 'response parsed' => $spb41165, 'response raw' => $spf8f74c
			], "error-$errorType");
		}
		$spc59ec5 = [];
		$spc59ec5['response_code'] = '1';
		$spc59ec5['response_subcode'] = '1';
		$spc59ec5['response_reason_code'] = '1';
		$spc59ec5['response_reason_text'] = '(TESTMODE2) This transaction has been approved.';
		$spc59ec5['approval_code'] = '000000';
		$spc59ec5['avs_result_code'] = 'P';
		$spc59ec5['transaction_id'] = '0';
		$spc59ec5['md5_hash'] = '382065EC3B4C2F5CDC424A730393D2DF';
		$spc59ec5['card_code_response'] = '';
		if ($spb41165['trnApproved'] == 1) {
			$spc59ec5['response_reason_text'] = '';
			$spc59ec5['response_code'] = '1';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$spc59ec5['response_reason_text'] = $spb41165['messageText'];
			}
			if (isset($spb41165['messageId']) && !empty($spb41165['messageId'])) {
				$spc59ec5['response_reason_code'] = $spb41165['messageId'];
			}
			if (isset($spb41165['authCode']) && !empty($spb41165['authCode'])) {
				$spc59ec5['approval_code'] = $spb41165['authCode'];
			}
			if (isset($spb41165['avsResult']) && !empty($spb41165['avsResult'])) {
				$spc59ec5['avs_result_code'] = $spb41165['avsResult'];
			}
			if (isset($spb41165['trnId']) && !empty($spb41165['trnId'])) {
				$spc59ec5['transaction_id'] = $spb41165['trnId'];
			}
		} else {
			$spc59ec5['response_code'] = '0';
			$spc59ec5['response_subcode'] = '0';
			$spc59ec5['response_reason_code'] = '0';
			$spc59ec5['approval_code'] = '000000';
			$spc59ec5['avs_result_code'] = 'P';
			$spc59ec5['transaction_id'] = '0';
			$spc59ec5['response_reason_text'] = '';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$spc59ec5['response_reason_text'] = $spb41165['messageText'];
			}
			if (empty($spb41165['errorFields'])) {
				$spb41165['errorFields'] = 'Transaction has been DECLINED.';
			}
			$spc59ec5['response_reason_text'] .= '-' . $spb41165['errorFields'];
		}
		return $spc59ec5;
	}

	/**
	 * 2021-06-28 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by capture()
	 * @used-by refund()
	 * @used-by void()
	 * @param II|OP $i
	 * @param string $type
	 * @param float|string $a
	 * @return _DO
	 */
	private function buildRequest(II $i, $type, $a) {
		$o = $i->getOrder(); /** @var O $o */
		$req = new _DO;
		if ($a) {
			$req[self::$X_AMOUNT] = $a;
		}
		switch ($type) {
			case self::$REFUND:
			case self::$VOID:
			case self::$PRIOR_AUTH_CAPTURE:
				$req->setXTransId($i->getCcTransId());
				break;
		}
		if (!empty($o)) {
			$req->setXInvoiceNum($o->getIncrementId());
			$ba = $o->getBillingAddress();
			if (!empty($ba)) {
				$req->setXFirstName($ba->getFirstname());
				$req->setXLastName($ba->getLastname());
				$req->setXCompany($ba->getCompany());
				$req->setXAddress($ba->getStreet(1)[0]);
				$req->setXCity($ba->getCity());
				$req->setXState($ba->getRegion());
				$req->setXZip($ba->getPostcode());
				$req[self::$COUNTRY] = $ba->getCountry() ?: $ba->getCountryId();
				$req->setXPhone($ba->getTelephone());
				$req->setXFax($ba->getFax());
				$req->setXCustId($ba->getCustomerId());
				$req->setXCustomerTaxId($ba->getTaxId());
				$req->setXEmail($ba->getEmail() ?: $o->getCustomerEmail());
			}
			$sa = $o->getShippingAddress();
			if (!$sa) {
				$sa = $ba;
			}
			$amtShipping = $o->getShippingAmount(); /** @var float $amtShipping */
			$amtTax = $o->getTaxAmount(); /** @var float $amtTax */
			$subtotal = $o->getSubtotal(); /** @var float $subtotal */
			if (!empty($sa)) {
				$req->setXShipToFirstName($sa->getFirstname());
				$req->setXShipToLastName($sa->getLastname());
				$req->setXShipToCompany($sa->getCompany());
				$req->setXShipToAddress($sa->getStreet(1)[0]);
				$req->setXShipToCity($sa->getCity());
				$req->setXShipToState($sa->getRegion());
				$req->setXShipToZip($sa->getPostcode());
				$req->setXShipToCountry($sa->getCountry());
				if (!isset($amtShipping) || $amtShipping <= 0) {
					$amtShipping = $sa->getShippingAmount();
				}
				if (!isset($amtTax) || $amtTax <= 0) {
					$amtTax = $sa->getTaxAmount();
				}
				if (!isset($subtotal) || $subtotal <= 0) {
					$subtotal = $sa->getSubtotal();
				}
			}
			$req->setXPoNum($i->getPoNumber())->setXTax($amtTax)->setXSubtotal($subtotal)->setXFreight($amtShipping);
		}
		if ($req[self::$CARD_NUMBER] = $i->getCcNumber()) {
			$req[self::$CVV] = $i->getCcCid();
			$req[self::$CARD_EXP_MONTH] = sprintf('%02d', $i->getCcExpMonth());
			# 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
			# 2) The year should be represented by the last 2 digits:
			# 2.1) https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			# 2.2) https://dev.na.bambora.com/docs/references/payment_APIs/v1-0-5/
			$req[self::$CARD_EXP_YEAR] = substr($i->getCcExpYear(), -2);
		}
		return $req;
	}

	/**
	 * 2021-06-28 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by capture()
	 * @used-by refund()
	 * @used-by void()
	 * @param _DO $req
	 * @param string $type
	 * @return mixed
	 * @throws LE
	 */
	private function postRequest(_DO $req, $type) {
		$res = new _DO;
		$reqA = $req->getData();
		$spa81281 = array(
			0 => '1',
			1 => '1',
			2 => '1',
			3 => '(TESTMODE) This transaction has been approved.',
			4 => '000000',
			5 => 'P',
			6 => '0',
			7 => '100000018',
			8 => '',
			9 => '2704.99',
			10 => 'CC',
			11 => 'auth_only',
			12 => '',
			13 => 'Sreeprakash',
			14 => 'N.',
			15 => 'Schogini',
			16 => 'XYZ',
			17 => 'City',
			18 => 'Idaho',
			19 => '695038',
			20 => 'US',
			21 => '1234567890',
			22 => '',
			23 => '',
			24 => 'Sreeprakash',
			25 => 'N.',
			26 => 'Schogini',
			27 => 'XYZ',
			28 => 'City',
			29 => 'Idaho',
			30 => '695038',
			31 => 'US',
			32 => '',
			33 => '',
			34 => '',
			35 => '',
			36 => '',
			37 => '382065EC3B4C2F5CDC424A730393D2DF',
			38 => '',
			39 => '',
			40 => '',
			41 => '',
			42 => '',
			43 => '',
			44 => '',
			45 => '',
			46 => '',
			47 => '',
			48 => '',
			49 => '',
			50 => '',
			51 => '',
			52 => '',
			53 => '',
			54 => '',
			55 => '',
			56 => '',
			57 => '',
			58 => '',
			59 => '',
			60 => '',
			61 => '',
			62 => '',
			63 => '',
			64 => '',
			65 => '',
			66 => '',
			67 => ''
		);
		$spa81281[7] = $reqA['x_invoice_num'];
		$spa81281[8] = '';
		$spa81281[9] = $reqA[self::$X_AMOUNT];
		$spa81281[10] = null;
		$spa81281[11] = $type;
		$spa81281[12] = $reqA['x_cust_id'];
		$spa81281[13] = $reqA['x_first_name'];
		$spa81281[14] = $reqA['x_last_name'];
		$spa81281[15] = $reqA['x_company'];
		$spa81281[16] = $reqA['x_address'];
		$spa81281[17] = $reqA['x_city'];
		$spa81281[18] = $reqA['x_state'];
		$spa81281[19] = $reqA['x_zip'];
		$spa81281[20] = $reqA[self::$COUNTRY];
		$spa81281[21] = $reqA['x_phone'];
		$spa81281[22] = $reqA['x_fax'];
		$spa81281[23] = '';
		$reqA['x_ship_to_first_name'] = !isset($reqA['x_ship_to_first_name']) ? $reqA['x_first_name'] : $reqA['x_ship_to_first_name'];
		$reqA['x_ship_to_first_name'] = !isset($reqA['x_ship_to_first_name']) ? $reqA['x_first_name'] : $reqA['x_ship_to_first_name'];
		$reqA['x_ship_to_last_name'] = !isset($reqA['x_ship_to_last_name']) ? $reqA['x_last_name'] : $reqA['x_ship_to_last_name'];
		$reqA['x_ship_to_company'] = !isset($reqA['x_ship_to_company']) ? $reqA['x_company'] : $reqA['x_ship_to_company'];
		$reqA['x_ship_to_address'] = !isset($reqA['x_ship_to_address']) ? $reqA['x_address'] : $reqA['x_ship_to_address'];
		$reqA['x_ship_to_city'] = !isset($reqA['x_ship_to_city']) ? $reqA['x_city'] : $reqA['x_ship_to_city'];
		$reqA['x_ship_to_state'] = !isset($reqA['x_ship_to_state']) ? $reqA['x_state'] : $reqA['x_ship_to_state'];
		$reqA['x_ship_to_zip'] = !isset($reqA['x_ship_to_zip']) ? $reqA['x_zip'] : $reqA['x_ship_to_zip'];
		$reqA['x_ship_to_country'] = !isset($reqA['x_ship_to_country']) ? $reqA[self::$COUNTRY] : $reqA['x_ship_to_country'];
		$spa81281[24] = $reqA['x_ship_to_first_name'];
		$spa81281[25] = $reqA['x_ship_to_last_name'];
		$spa81281[26] = $reqA['x_ship_to_company'];
		$spa81281[27] = $reqA['x_ship_to_address'];
		$spa81281[28] = $reqA['x_ship_to_city'];
		$spa81281[29] = $reqA['x_ship_to_state'];
		$spa81281[30] = $reqA['x_ship_to_zip'];
		$spa81281[31] = $reqA['x_ship_to_country'];
		$spa81281[0] = '1';
		$spa81281[1] = '1';
		$spa81281[2] = '1';
		$spa81281[3] = '(TESTMODE2) This transaction has been approved.';
		$spa81281[4] = '000000';
		$spa81281[5] = 'P';
		$spa81281[6] = '0';
		$spa81281[37] = '382065EC3B4C2F5CDC424A730393D2DF';
		$spa81281[39] = '';
		$spc59ec5 = $this->beanstreamapi($reqA, $type);
		$spa81281[0] = $spc59ec5['response_code'];
		$spa81281[1] = $spc59ec5['response_subcode'];
		$spa81281[2] = $spc59ec5['response_reason_code'];
		$spa81281[3] = $spc59ec5['response_reason_text'];
		$spa81281[4] = $spc59ec5['approval_code'];
		$spa81281[5] = $spc59ec5['avs_result_code'];
		$spa81281[6] = $spc59ec5['transaction_id'];
		$spa81281[37] = $spc59ec5['md5_hash'];
		$spa81281[39] = $spc59ec5['card_code_response'];
		if (!$spa81281) {
			self::err('Error in payment gateway');
		}
		$res->setResponseCode((int)str_replace('"', '', $spa81281[0]));
		$res->setResponseSubcode((int)str_replace('"', '', $spa81281[1]));
		$res->setResponseReasonCode((int)str_replace('"', '', $spa81281[2]));
		$res->setResponseReasonText($spa81281[3]);
		$res->setApprovalCode($spa81281[4]);
		$res->setAvsResultCode($spa81281[5]);
		$res->setTransactionId($spa81281[6]);
		$res->setInvoiceNumber($spa81281[7]);
		$res->setDescription($spa81281[8]);
		$res->setAmount($spa81281[9]);
		$res->setMethod($spa81281[10]);
		$res->setTransactionType($spa81281[11]);
		$res->setCustomerId($spa81281[12]);
		$res->setMd5Hash($spa81281[37]);
		$res->setCardCodeResponseCode($spa81281[39]);
		return $res;
	}

	/**
	 * 2021-06-29 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by beanstreamapi()
	 * @used-by capture()
	 * @used-by postRequest()
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

	/**
	 * 2021-07-06 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by capture()
	 * @var string
	 */
	private static $AUTH_CAPTURE = 'AUTH_CAPTURE';

	/**
	 * 2021-07-06 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by authorize()
	 * @used-by beanstreamapi()
	 * @var string
	 */
	private static $AUTH_ONLY = 'AUTH_ONLY';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @var string
	 */
	private static $CARD_EXP_MONTH = 'card_exp_month';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @var string
	 */
	private static $CARD_EXP_YEAR = 'card_exp_year';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @var string
	 */
	private static $CARD_NUMBER = 'card_number';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @used-by postRequest()
	 * @var string
	 */
	private static $COUNTRY = 'country';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @var string
	 */	
	private static $CVV = 'cvv';

	/**
	 * 2021-07-01 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @used-by capture()
	 * @var string
	 */
	private static $PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';		
	
	/**
	 * 2021-07-01 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by buildRequest()
	 * @used-by refund()
	 * @var string 
	 */
	private static $REFUND = 'REFUND';

	/**
	 * 2021-07-01 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @used-by void()
	 * @var string
	 */
	private static $VOID = 'VOID';

	/**
	 * 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @used-by beanstreamapi()
	 * @used-by buildRequest()
	 * @used-by postRequest()
	 * @var string
	 */
	private static $X_AMOUNT = 'x_amount';
}