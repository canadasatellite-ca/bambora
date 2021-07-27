<?php
namespace CanadaSatellite\Bambora;
use CanadaSatellite\Bambora\Method as M;
use Df\API\Operation;
use Df\Core\O as CO;
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
	 * @param Action $a
	 */
	private function __construct(Action $a) {$this->_a = $a;}

	/**
	 * 2021-06-29
	 * @used-by p()
	 * @param string $type
	 * @param float|string $a
	 * @return Operation
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
		$i = $this->ii(); /** @var II|OP $i */
		$o = $this->o(); /** @var O $o */
		$nameFull = df_cc_s($ba->getFirstname(), $ba->getLastname()); /** @var string $nameFull */
		/** @var string $query */ /** @var array(string => mixed) $queryA */
		$query = http_build_query($queryA = [
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
			# 2021-07-11 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# The year should be represented by the last 2 digits:
			# 1) https://github.com/bambora-na/dev.na.bambora.com/blob/0486cc7e/source/docs/references/recurring_payment/index.md#card-info
			# 2) https://dev.na.bambora.com/docs/references/payment_APIs/v1-0-5
			,'trnExpYear' => substr($i->getCcExpYear(), -2)
			,'trnOrderNumber' => $o->getIncrementId()
			# 2021-07-22 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			# 1.1) «Original value sent indicating the type of transaction to perform (P, R, PA, VP, VR, PAC, Q).»
			# 1.2) «3 a/n characters»
			# https://support.na.bambora.com/bic/w/docs/response-variables.htm
			# 2.1) «`trnType` field must be included specifying the value PA for Pre-Authorization.»
			# https://mage2.pro/t/6280, Page 34.
			# 2.2) «Specify `trnType=PA` to process a pre-authorization against a customer's credit card.
			# If omitted, this option will default to P for purchase.» https://mage2.pro/t/6280, Page 35.
			# 2.3) «The request strings for these three types of transactions
			# will vary only in the value passed in the `trnType` field (R=Return, VP=Void Purchase, VR=Void Return).»
			# https://mage2.pro/t/6280, Page 35.
			# 2.4) «A void is the removal of the entire amount,
			# while a return will allow you do partial to full refunds of a transaction.
			# The amount sent in needs to reflect this, otherwise it will be rejected from our system.»
			# https://mage2.pro/t/6280, Page 35.
			# 2.5)
			# 	«*) R=Return
			# 	*) VR=Void Return
			#	*) V=Void
			#	*) VP=Void Purchase
			#	*) PAC=Pre-Authorization Completion
			# If omitted, this field will default to P for purchase.
			# Please note that "R" is the only valid adjustment for INTERAC Online.»
			# https://mage2.pro/t/6280, Page 39.
			,'trnType' => $this->_a->trnType()
			,'username' => $this->cfg('merchant_username')
		# 2021-07-27
		# «A Pre-Authorization Completion (PAC) is the second part of a pre-authorization.
		# A PAC has a shorter transaction string than the original authorization
		# as no card or billing information is required.
		# The request must include an `adjId` variable that identifies the original PA transaction number.»
		# https://mage2.pro/t/6283, page 45.
		] + (!in_array($type, [self::VOID, self::PRIOR_AUTH_CAPTURE]) ? [] : ['adjId' => ParentId::get($i)]));
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
		$resRaw = curl_exec($curl); /** @var string $resRaw */
		$curlError = curl_error($curl); /** @var string $curlError */
		curl_close($curl);
		if ($curlError) {
			df_log_l(__CLASS__, ['request' => $query, 'response' => $curlError], 'error-curl');
			df_error("Error: $curlError");
		}
		# 2021-03-20 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# "Beanstream: «Microsoft OLE DB Driver for SQL Server» / «TCP Provider: The wait operation timed out» /
		# «C:\INETPUB\BEANSTREAM\ERRORPAGES\../admin/include/VBScript_ado_connection_v2.asp»":
		# https://github.com/canadasatellite-ca/site/issues/18
		if (df_contains($resRaw, 'Microsoft OLE DB Driver for SQL Server')) {
			df_log_l(__CLASS__, ['request' => $query, 'response' => $resRaw], 'error-ole');
			df_error('Error: ' . $resRaw);
		}
		parse_str($resRaw, $resA); /** @var array(string => mixed) $resA */
		$r = new Response($resA); /** @var Response $r */
		# 2021-03-20 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
		# "Prevent the `Schogini_Beanstream` module from logging successful transactions to `beanstream.log`":
		# https://github.com/canadasatellite-ca/site/issues/17
		if (!$r->valid()) { /** @var string $errorType */
			df_log_l(__CLASS__, ['request' => $query, 'response' => $resA], "error-{$r->errorType()}");
		}
		return new Operation(new CO($queryA), $r);
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
	private function cfg($k) {return $this->m()->getConfigData($k);}

	/**
	 * 2021-07-14
	 * @used-by api()
	 * @used-by o()
	 * @return II|I|OP|QP
	 */
	private function ii() {return $this->m()->getInfoInstance();}

	/**
	 * 2021-07-22
	 * @used-by cfg()
	 * @used-by ii()
	 * @return M
	 */
	private function m() {return $this->_a->m();}

	/**
	 * 2021-07-16
	 * @used-by ba()
	 * @used-by api()
	 * @used-by p()
	 * @return O
	 */
	private function o() {return $this->ii()->getOrder();}

	/**
	 * 2021-07-14
	 * @used-by __construct()
	 * @used-by m()
	 * @var Action
	 */
	private $_a;

	/**
	 * 2021-07-17
	 * @used-by \CanadaSatellite\Bambora\Action\Authorize::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Capture::p()
	 * @used-by \CanadaSatellite\Bambora\Action\Refund::p()
	 * @used-by \CanadaSatellite\Bambora\Action\_Void::p()
	 * @param Action $a
	 * @param string $type
	 * @param float|string $amt
	 * @return Operation
	 */
	static function p(Action $a, $type, $amt) {
		$i = new self($a); /** @var self $i */
		return $i->api($type, $amt);
	}

	/**
	 * 2021-07-06
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Method::capture()
	 * @var string
	 */
	const AUTH_CAPTURE = 'AUTH_CAPTURE';

	/**
	 * 2021-07-06
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Method::authorize()
	 * @var string
	 */
	const AUTH_ONLY = 'AUTH_ONLY';

	/**
	 * 2021-07-01
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Method::capture()
	 * @var string
	 */
	const PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';

	/**
	 * 2021-07-01
	 * @used-by api()
	 * @used-by \CanadaSatellite\Bambora\Method::void()
	 * @var string
	 */
	const VOID = 'VOID';
}