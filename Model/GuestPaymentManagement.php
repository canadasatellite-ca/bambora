<?php
namespace CanadaSatellite\Bambora\Model;
use Magento\Framework\Exception\CouldNotSaveException as CNSE;
use Magento\Quote\Api\Data\AddressInterface as IA;
use Magento\Quote\Api\Data\PaymentInterface as IP;
# 2021-07-21 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
class GuestPaymentManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement {
	/**
	 * 2021-07-21 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
	 * @param string $cartId
	 * @param string $email
	 * @param IP $p
	 * @param IA|null $a [optional]
	 * @return int
	 * @throws CNSE
	 */
	function savePaymentInformationAndPlaceOrder($cartId, $email, IP $p, IA $a = null) {
		$this->savePaymentInformation($cartId, $email, $p, $a);
		try {$r = $this->cartManagement->placeOrder($cartId);}
		catch (\Exception $e) {
			df_log_e($e, $this);
			throw new CNSE(__('Cannot place order: ' . $e->getMessage()), $e);
		}
		return $r;
	}
}