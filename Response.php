<?php
namespace CanadaSatellite\Bambora;
/**
 * 2021-07-17
 * 1) A response:
 * 	{
 *		"authCode": "TEST",
 *		"avsAddrMatch": "0",
 *		"avsId": "N",
 *		"avsMessage": "Street address and Postal/ZIP do not match.",
 *		"avsPostalMatch": "0",
 *		"avsProcessed": "1",
 *		"avsResult": "0",
 *		"cardType": "VI",
 *		"cvdId": "1",
 *		"errorFields": "",
 *		"errorType": "N",
 *		"hashValue": "5961d1ce5492e25403c2d27bdd83ebb02d83c19f",
 *		"messageId": "1",
 *		"messageText": "Approved",
 *		"paymentMethod": "CC",
 *		"ref1": "",
 *		"ref2": "",
 *		"ref3": "",
 *		"ref4": "",
 *		"ref5": "",
 *		"responseType": "T",
 *		"trnAmount": "32.69",
 *		"trnApproved": "1",
 *		"trnDate": "7/16/2021 8:15:28 PM",
 *		"trnId": "10000025",
 *		"trnOrderNumber": "190630",
 *		"trnType": "PA"
 *	}
 * 2) «Bambora response variables»: https://support.na.bambora.com/bic/w/docs/response-variables.htm
 */
final class Response extends \Df\Core\O {
	/**
	 * 2021-07-17
	 * «0-32 alphanumeric characters»
	 * «If the transaction is approved this parameter contains a unique bank-issued code.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return string
	 */
	function authCode() {return df_prop($this);}

	/**
	 * 2021-07-17
	 * «1 digit»
	 * «1 – if AVS was validated with both a match against address, and a match against postal/ZIP code»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return bool
	 */
	function avsResult() {return 1 === (int)df_prop($this);}

	/**
	 * 2021-07-20
	 * 1) «List of fields»
	 * «For a user generated error, this variable includes a list of fields that failed form validation.
	 * Notify the customer that they must correct these fields before the transaction can be completed.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * 2) «The `errorFields` variable will contain a list of fields that failed validation.»
	 * https://mage2.pro/t/6280, Page 11.
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return string
	 */
	function errorFields() {return df_prop($this);}

	/**
	 * 2021-07-17
	 * 1) «1 character»
	 * «Returns the value: N, S, or U.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * 2) «The `errorType` response variable will indicate “U” if a form field error occurs.»: https://mage2.pro/t/6280, Page 11.
	 * 3) «System generated errors can be identified in a Server to Server integration
	 * by a response message “errorType=S” in the Beanstream response string.
	 * If a system generated error occurs, validate your integration and website setup.»: https://mage2.pro/t/6280, Page 12.
	 * @used-by valid()
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return string
	 */
	function errorType() {return df_prop($this);}

	/**
	 * 2021-07-17
	 * «1-3 digits»
	 * «References a detailed approved/declined transaction response message.
	 * Review our gateway response message table for a full description of each message.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return int
	 */
	function messageId() {return (int)df_prop($this);}

	/**
	 * 2021-07-17
	 * «Returns a basic approved/declined message that can be displayed to the customer on a confirmation page.
	 * Review our gateway response message table for details.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return string
	 */
	function messageText() {return df_prop($this);}

	/**
	 * 2021-07-17
	 * «0 – Transaction refused, 1 – Transaction approved»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return bool
	 */
	function trnApproved() {return !!df_prop($this);}

	/**
	 * 2021-07-17
	 * «8 digits»
	 * «Unique id number identifying an individual transaction.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * @used-by \CanadaSatellite\Bambora\Facade::p()
	 * @return bool
	 */
	function trnId() {return (int)df_prop($this);}

	/**
	 * 2021-07-17
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return bool
	 */
	function valid() {return 'N' === $this->errorType();}
}