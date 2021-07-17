<?php
namespace CanadaSatellite\Bambora;
/**
 * 2021-07-17
 * A response:
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
 */
final class Response extends \Df\Core\O {}