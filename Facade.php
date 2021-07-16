<?php
namespace CanadaSatellite\Bambora;
use CanadaSatellite\Bambora\Model\Beanstream as M;
use Magento\Framework\DataObject as _DO;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Quote\Model\Quote\Payment as QP;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Address as OA;
use Magento\Sales\Model\Order\Payment as OP;
# 2021-07-14
final class Facade {
	/**
	 * 2021-06-28
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::authorize()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::refund()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::void()
	 * @param string $type
	 * @param float|string $a
	 * @return _DO
	 */
	function build($type, $a) {
		$i = $this->ii(); /** @var II|OP $i */
		$o = $this->o(); /** @var O $o */
		$req = new _DO;
		if ($a) {
			$req[self::$AMOUNT] = $a;
		}
		if (!empty($o)) {
			$ba = $o->getBillingAddress(); /** @var OA $ba */
			if (!empty($ba)) {
				$req[self::$STATE] = $ba->getRegion();
			}
			$amtShipping = $o->getShippingAmount(); /** @var float $amtShipping */
			$amtTax = $o->getTaxAmount(); /** @var float $amtTax */
			$subtotal = $o->getSubtotal(); /** @var float $subtotal */
			$sa = $this->sa(); /** @var OA $sa */
			if (!empty($sa)) {
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
		$req[self::$CARD_NUMBER] = $i->getCcNumber();
		return $req;
	}

	/**
	 * 2021-06-28
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::authorize()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::refund()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::void()
	 * @param _DO $req
	 * @param string $type
	 * @return mixed
	 * @throws LE
	 */
	function post(_DO $req, $type) {
		$res = new _DO;
		$reqA = $req->getData();
		$resA = $this->beanstreamapi($reqA, $type); /** @var array(string => mixed) $resA */
		$res->setResponseCode((int)str_replace('"', '', $resA['response_code']));
		$res->setResponseSubcode((int)str_replace('"', '', $resA['response_subcode']));
		$res->setResponseReasonCode((int)str_replace('"', '', $resA['response_reason_code']));
		$res->setResponseReasonText($resA['response_reason_text']);
		$res->setApprovalCode($resA['approval_code']);
		$res->setAvsResultCode($resA['avs_result_code']);
		$res->setTransactionId($resA['transaction_id']);
		$res->setInvoiceNumber($this->o()->getIncrementId());
		$res->setDescription('');
		$res->setAmount($reqA[self::$AMOUNT]);
		$res->setMethod(null);
		$res->setTransactionType($type);
		$res->setCustomerId($this->ba()->getCustomerId());
		$res->setMd5Hash($resA['md5_hash']);
		$res->setCardCodeResponseCode($resA['card_code_response']);
		return $res;
	}

	/**
	 * 2021-07-14
	 * @used-by s()
	 * @param M $m
	 */
	private function __construct(M $m) {$this->_m = $m;}

	/**
	 * 2021-07-16
	 * @used-by beanstreamapi()
	 * @used-by post()
	 * @used-by sa()
	 * @return OA
	 */
	private function ba() {return $this->o()->getBillingAddress();}

	/**
	 * 2021-06-29
	 * @used-by post()
	 * @param array(string => mixed) $reqA
	 * @param string $type
	 * @return array
	 * @throws LE
	 */
	private function beanstreamapi(array $reqA, $type) {
		$ba = $this->ba(); /** @var OA $ba */
		$state = dftr($reqA[self::$STATE], Regions::ca()); /** @var string $state */
		$country = $ba->getCountryId() ?: (!$state ? null : (
			dfa(Regions::ca(), $state) ? 'CA' : (dfa(Regions::us(), $state) ? 'US' : null)
		)); /** @var string $country */
		if ('US' === $country) {
			$state = dftr($state, Regions::us());
		}
		if (!in_array($country, ['CA', 'US'])) {
			$state = '--';
		}
		$trnType = 'P';
		$query2 = []; /** @var array(string => string) $query2 */
		$i = $this->ii(); /** @var II|OP $i */
		if ($type == self::AUTH_CAPTURE) {
			$trnType = 'P';
		}
		elseif ($type == self::AUTH_ONLY) {
			$trnType = 'PA';
		}
		elseif ($type == self::PRIOR_AUTH_CAPTURE) {
			$trnType = 'PAC';
			$query2 = ['adjId' => $i->getCcTransId()];
		}
		elseif ($type == self::VOID) {
			$trnType = 'PAC';
			$spd28804 = explode('--', $i->getCcTransId());
			$query2 = ['adjId' => $spd28804[0]];
			$reqA[self::$AMOUNT] = 0.0;
		}
		$o = $this->o(); /** @var O $o */
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
			,'merchant_id' => $this->cfg('merchant_id')
			,'ordAddress1' => $ba->getStreet()[0]
			,'ordAddress2' => ''
			,'ordCity' => $ba->getCity()
			,'ordCountry' => $country
			,'ordEmailAddress' => $ba->getEmail() ?: $o->getCustomerEmail()
			,'ordName' => df_cc_s($ba->getFirstname(), $ba->getLastname())
			,'ordPhoneNumber' => $ba->getTelephone()
			,'ordPostalCode' => $ba->getPostcode()
			,'ordProvince' => $state
			,'password' => $this->cfg('merchant_password')
			,'requestType' => 'BACKEND'
			,'trnAmount' => $reqA[self::$AMOUNT]
			# 2021-06-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) «Include the 3 or 4-digit CVD number from the back of the customer's credit card.
			# CVD numbers are not stored in the Bambora system
			# and will only be used for a first recurring billing transaction if passed.»
			# 2) «4 digits Amex, 3 digits all other cards»
			# https://dev.na.bambora.com/docs/references/recurring_payment/#card-info
			# https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			,'trnCardCvd' => df_ets($i->getCcCid())
			,'trnCardNumber' => $reqA[self::$CARD_NUMBER]
			,'trnCardOwner' => df_cc_s($ba->getFirstname(), $ba->getLastname())
			,'trnExpMonth' => sprintf('%02d', $i->getCcExpMonth())
			# 2021-07-07 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# The year should be represented by the last 2 digits:
			# 1) https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			# 2) https://dev.na.bambora.com/docs/references/payment_APIs/v1-0-5
			,'trnExpYear' => substr($i->getCcExpYear(), -2)
			,'trnOrderNumber' => $o->getIncrementId()
			,'trnType' => $trnType
			,'username' => $this->cfg('merchant_username')
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
			df_log_l(__CLASS__, ['request' => $query, 'response' => $sp35fa42], 'error-curl');
			df_error('Error: ' . $sp35fa42);
		}
		# 2021-03-20 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# "Beanstream: «Microsoft OLE DB Driver for SQL Server» / «TCP Provider: The wait operation timed out» /
		# «C:\INETPUB\BEANSTREAM\ERRORPAGES\../admin/include/VBScript_ado_connection_v2.asp»":
		# https://github.com/canadasatellite-ca/site/issues/18
		if (df_contains($spf8f74c, 'Microsoft OLE DB Driver for SQL Server')) {
			df_log_l(__CLASS__, ['request' => $query, 'response' => $spf8f74c], 'error-ole');
			df_error('Error: ' . $spf8f74c);
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
			df_log_l(__CLASS__, [
				'request' => $query, 'response parsed' => $spb41165, 'response raw' => $spf8f74c
			], "error-$errorType");
		}
		$resA = [];
		$resA['response_code'] = '1';
		$resA['response_subcode'] = '1';
		$resA['response_reason_code'] = '1';
		$resA['response_reason_text'] = '(TESTMODE2) This transaction has been approved.';
		$resA['approval_code'] = '000000';
		$resA['avs_result_code'] = 'P';
		$resA['transaction_id'] = '0';
		$resA['md5_hash'] = '382065EC3B4C2F5CDC424A730393D2DF';
		$resA['card_code_response'] = '';
		if ($spb41165['trnApproved'] == 1) {
			$resA['response_reason_text'] = '';
			$resA['response_code'] = '1';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$resA['response_reason_text'] = $spb41165['messageText'];
			}
			if (isset($spb41165['messageId']) && !empty($spb41165['messageId'])) {
				$resA['response_reason_code'] = $spb41165['messageId'];
			}
			if (isset($spb41165['authCode']) && !empty($spb41165['authCode'])) {
				$resA['approval_code'] = $spb41165['authCode'];
			}
			if (isset($spb41165['avsResult']) && !empty($spb41165['avsResult'])) {
				$resA['avs_result_code'] = $spb41165['avsResult'];
			}
			if (isset($spb41165['trnId']) && !empty($spb41165['trnId'])) {
				$resA['transaction_id'] = $spb41165['trnId'];
			}
		} else {
			$resA['response_code'] = '0';
			$resA['response_subcode'] = '0';
			$resA['response_reason_code'] = '0';
			$resA['approval_code'] = '000000';
			$resA['avs_result_code'] = 'P';
			$resA['transaction_id'] = '0';
			$resA['response_reason_text'] = '';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$resA['response_reason_text'] = $spb41165['messageText'];
			}
			if (empty($spb41165['errorFields'])) {
				$spb41165['errorFields'] = 'Transaction has been DECLINED.';
			}
			$resA['response_reason_text'] .= '-' . $spb41165['errorFields'];
		}
		return $resA;
	}

	/**
	 * 2021-07-14
	 * @used-by beanstreamapi()
	 * @param string $k
	 * @return mixed
	 */
	private function cfg($k) {return $this->_m->getConfigData($k);}

	/**
	 * 2021-07-14
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @used-by o()
	 * @param string|null $k [optional]
	 * @return II|I|OP|QP
	 */
	private function ii() {return $this->_m->getInfoInstance();}

	/**
	 * 2021-07-16
	 * @used-by ba()
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @used-by post()
	 * @return O
	 */
	private function o() {return $this->ii()->getOrder();}

	/**
	 * 2021-07-14
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::f()
	 * @param M $m
	 * @return self
	 */
	static function s(M $m) {return dfcf(function(M $m) {return new self($m);}, [$m]);}

	/**
	 * 2021-07-16
	 * @used-by build()
	 * @return OA
	 */
	private function sa() {return $this->o()->getShippingAddress() ?: $this->ba();}

	/**
	 * 2021-07-14
	 * @used-by __construct()
	 * @used-by cfg()
	 * @used-by ii()
	 * @var M
	 */
	private $_m;

	/**
	 * 2021-07-06
	 * @used-by beanstreamapi()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @var string
	 */
	const AUTH_CAPTURE = 'AUTH_CAPTURE';

	/**
	 * 2021-07-06
	 * @used-by beanstreamapi()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::authorize()
	 * @var string
	 */
	const AUTH_ONLY = 'AUTH_ONLY';

	/**
	 * 2021-07-01
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @var string
	 */
	const PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';

	/**
	 * 2021-07-01
	 * @used-by build()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::refund()
	 * @var string
	 */
	const REFUND = 'REFUND';

	/**
	 * 2021-07-01
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::void()
	 * @var string
	 */
	const VOID = 'VOID';
	
	/**
	 * 2021-07-07
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @used-by post()
	 * @var string
	 */
	private static $AMOUNT = 'amount';

	/**
	 * 2021-07-07
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @var string
	 */
	private static $CARD_NUMBER = 'card_number';

	/**
	 * 2021-07-14
	 * @used-by beanstreamapi()
	 * @used-by build()
	 * @var string
	 */
	private static $STATE = 'state';
}