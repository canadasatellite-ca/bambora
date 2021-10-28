<?php
namespace CanadaSatellite\Bambora;
# 2021-10-28
# "Temporary ban IP addresses of guest payers after 3 consecutive failed bank card payment attempts"
# https://github.com/canadasatellite-ca/bambora/issues/14
final class Session extends \Df\Core\Session {
	/**
	 * 2021-10-28
	 * @override
	 * @see \Df\Core\Session::c()
	 * @used-by \Df\Core\Session::__construct()
	 * @return string
	 */
	protected function c() {return 'Magento\Checkout\Model\Session\Storage';}
}
