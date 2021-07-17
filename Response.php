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
	 * 1) «1 character»
	 * «Returns the value: N, S, or U.»
	 * https://support.na.bambora.com/bic/w/docs/response-variables.htm
	 * 2) «The `errorType` response variable will indicate “U” if a form field error occurs.»: https://mage2.pro/t/6280, Page 11.
	 * 3) «System generated errors can be identified in a Server to Server integration
	 * by a response message “errorType=S” in the Beanstream response string.
	 * If a system generated error occurs, validate your integration and website setup.»: https://mage2.pro/t/6280, Page 12.
	 * @return string|$this
	 */
	function errorType() {return df_prop($this);}
}