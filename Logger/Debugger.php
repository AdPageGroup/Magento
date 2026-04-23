<?php declare(strict_types=1);

namespace Tagging\GTM\Logger;

use Magento\Framework\Filesystem\DirectoryList;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tagging\GTM\Config\Config;

class Debugger
{
    /**
     * Debugger constructor.
     *
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(private readonly Config $config, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param string $msg
     *
     * @return bool
     */
    public function debug(string $msg, mixed $data = null): bool
    {
        if ($this->config->isDebug() === false) {
            return false;
        }

        if (!empty($data)) {
            $msg .= ': ' . var_export($data, true);
        }

        $this->logger->notice($msg);
        return true;
    }
}
