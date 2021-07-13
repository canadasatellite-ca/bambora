<?php

namespace CanadaSatellite\Bambora\Model\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/beanstream.log';
}

