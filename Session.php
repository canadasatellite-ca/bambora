<?php
namespace CanadaSatellite\Bambora;
# 2021-10-28
# "Temporary ban IP addresses of guest payers after 3 consecutive failed bank card payment attempts"
# https://github.com/canadasatellite-ca/bambora/issues/14
final class Session extends \Df\Customer\SessionBase {
	/**
	 * 2021-10-31
	 * @used-by \CanadaSatellite\Bambora\Action::check()
	 * @param int|string $v [optional]
	 * @return $this|int
	 */
	function failedCount($v = DF_N) {return df_prop($this, $v, 0);}
}