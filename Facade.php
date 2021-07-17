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
	 * 2021-07-14
	 * @used-by p()
	 * @param M $m
	 */
	private function __construct(M $m) {$this->_m = $m;}

	/**
	 * 2021-06-29
	 * @used-by p()
	 * @param string $type
	 * @param float|string $a
	 * @return array
	 * @throws LE
	 */
	private function api($type, $a) {
		$ba = $this->ba(); /** @var OA $ba */
		$state = dftr($ba->getRegion(), Regions::ca()); /** @var string $state */
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
		}
		$o = $this->o(); /** @var O $o */
		$nameFull = df_cc_s($ba->getFirstname(), $ba->getLastname()); /** @var string $nameFull */
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
			,'ordName' => $nameFull
			,'ordPhoneNumber' => $ba->getTelephone()
			,'ordPostalCode' => $ba->getPostcode()
			,'ordProvince' => $state
			,'password' => $this->cfg('merchant_password')
			,'requestType' => 'BACKEND'
			,'trnAmount' => $a
			# 2021-06-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1) «Include the 3 or 4-digit CVD number from the back of the customer's credit card.
			# CVD numbers are not stored in the Bambora system
			# and will only be used for a first recurring billing transaction if passed.»
			# 2) «4 digits Amex, 3 digits all other cards»
			# https://dev.na.bambora.com/docs/references/recurring_payment/#card-info
			# https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			,'trnCardCvd' => df_ets($i->getCcCid())
			,'trnCardNumber' => $i->getCcNumber()
			,'trnCardOwner' => $nameFull
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
		$r = []; /** @var array(string => mixed) $r */
		$r['response_code'] = '1';
		$r['response_subcode'] = '1';
		$r['response_reason_code'] = '1';
		$r['response_reason_text'] = '(TESTMODE2) This transaction has been approved.';
		$r['approval_code'] = '000000';
		$r['avs_result_code'] = 'P';
		$r['transaction_id'] = '0';
		$r['md5_hash'] = '382065EC3B4C2F5CDC424A730393D2DF';
		$r['card_code_response'] = '';
		if ($spb41165['trnApproved'] == 1) {
			$r['response_reason_text'] = '';
			$r['response_code'] = '1';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$r['response_reason_text'] = $spb41165['messageText'];
			}
			if (isset($spb41165['messageId']) && !empty($spb41165['messageId'])) {
				$r['response_reason_code'] = $spb41165['messageId'];
			}
			if (isset($spb41165['authCode']) && !empty($spb41165['authCode'])) {
				$r['approval_code'] = $spb41165['authCode'];
			}
			if (isset($spb41165['avsResult']) && !empty($spb41165['avsResult'])) {
				$r['avs_result_code'] = $spb41165['avsResult'];
			}
			if (isset($spb41165['trnId']) && !empty($spb41165['trnId'])) {
				$r['transaction_id'] = $spb41165['trnId'];
			}
		}
		else {
			$r['response_code'] = '0';
			$r['response_subcode'] = '0';
			$r['response_reason_code'] = '0';
			$r['approval_code'] = '000000';
			$r['avs_result_code'] = 'P';
			$r['transaction_id'] = '0';
			$r['response_reason_text'] = '';
			if (isset($spb41165['messageText']) && !empty($spb41165['messageText'])) {
				$r['response_reason_text'] = $spb41165['messageText'];
			}
			if (empty($spb41165['errorFields'])) {
				$spb41165['errorFields'] = 'Transaction has been DECLINED.';
			}
			$r['response_reason_text'] .= '-' . $spb41165['errorFields'];
		}
		return $r;
	}

	/**
	 * 2021-07-16
	 * @used-by api()
	 * @used-by p()
	 * @return OA
	 */
	private function ba() {return $this->o()->getBillingAddress();}

	/**
	 * 2021-07-14
	 * @used-by api()
	 * @param string $k
	 * @return mixed
	 */
	private function cfg($k) {return $this->_m->getConfigData($k);}

	/**
	 * 2021-07-14
	 * @used-by api()
	 * @used-by o()
	 * @param string|null $k [optional]
	 * @return II|I|OP|QP
	 */
	private function ii() {return $this->_m->getInfoInstance();}

	/**
	 * 2021-07-16
	 * @used-by ba()
	 * @used-by api()
	 * @used-by p()
	 * @return O
	 */
	private function o() {return $this->ii()->getOrder();}

	/**
	 * 2021-07-17
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::authorize()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::refund()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::void()
	 * @param M $m
	 * @param string $type
	 * @param float|string $a
	 * @return _DO
	 */
	static function p(M $m, $type, $a) {
		$i = new self($m); /** @var self $i */
		$r = new _DO; /** @var _DO $r */
		$resA = $i->api($type, $a); /** @var array(string => mixed) $resA */
		$r->setResponseCode((int)str_replace('"', '', $resA['response_code']));
		$r->setResponseSubcode((int)str_replace('"', '', $resA['response_subcode']));
		$r->setResponseReasonCode((int)str_replace('"', '', $resA['response_reason_code']));
		$r->setResponseReasonText($resA['response_reason_text']);
		$r->setApprovalCode($resA['approval_code']);
		$r->setAvsResultCode($resA['avs_result_code']);
		$r->setTransactionId($resA['transaction_id']);
		$r->setInvoiceNumber($i->o()->getIncrementId());
		$r->setDescription('');
		$r->setAmount($a);
		$r->setMethod(null);
		$r->setTransactionType($type);
		$r->setCustomerId($i->ba()->getCustomerId());
		$r->setMd5Hash($resA['md5_hash']);
		$r->setCardCodeResponseCode($resA['card_code_response']);
		return $r;
	}

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
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @var string
	 */
	const AUTH_CAPTURE = 'AUTH_CAPTURE';

	/**
	 * 2021-07-06
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::authorize()
	 * @var string
	 */
	const AUTH_ONLY = 'AUTH_ONLY';

	/**
	 * 2021-07-01
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::capture()
	 * @var string
	 */
	const PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';

	/**
	 * 2021-07-01
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Model\Beanstream::void()
	 * @var string
	 */
	const VOID = 'VOID';
}