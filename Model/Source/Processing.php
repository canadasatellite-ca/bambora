<?php
namespace CanadaSatellite\Bambora\Model\Source;
use Magento\Sales\Model\Order as O;
# 2021-07-21 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
class Processing extends \Magento\Sales\Model\Config\Source\Order\Status {
	protected $_stateStatuses = [O::STATE_PENDING_PAYMENT, O::STATE_PROCESSING, O::STATE_COMPLETE, O::STATE_CLOSED];
}