<?php

namespace Application\Model\Service\System;

use Opg\Lpa\Logger\LoggerTrait;

class DynamoCronLock
{
    use LoggerTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * The namespace to prefix keys with.
     *
     * @var string
     */
    private $keyPrefix;

    /**
     * @param array $config
     * @param string $keyPrefix
     */
    public function __construct(array $config, $keyPrefix = 'default')
    {
        $this->config = $config;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Get the lock for a period of time (default 60 minutes)
     *
     * @param $lockName
     * @param int $allowedSecondsSinceLastRun
     * @return bool
     */
    public function getLock($lockName, $allowedSecondsSinceLastRun = 60 * 60)
    {
        //  Create the command to execute
        $command = 'bin/lock acquire ';
        $command .= sprintf('--name "%s/%s" ', $this->keyPrefix, $lockName);
        $command .= sprintf('--table %s ', $this->config['settings']['table_name']);
        $command .= sprintf('--ttl %s ', $allowedSecondsSinceLastRun);

        if (isset($this->config['client']['version'])) {
            $command .= sprintf('--version %s ', $this->config['client']['version']);
        }

        if (isset($this->config['client']['region'])) {
            $command .= sprintf('--region %s ', $this->config['client']['region']);
        }

        //  Add credentials if they are present
        if (isset($this->config['client']['credentials']['key']) && isset($this->config['client']['credentials']['secret'])) {
            $command .= sprintf('--awsKey %s ', $this->config['client']['credentials']['key']);
            $command .= sprintf('--awsSecret %s ', $this->config['client']['credentials']['credentials']);
        }

        //  Initialise the return value
        $output = [];
        $rtnValue = -1;

        exec($command, $output, $rtnValue);

        //  Log an appropriate message
        if ($rtnValue === 0) {
            $this->getLogger()->info(sprintf('This node got the %s cron lock for %s', $lockName, $lockName));

            return true;
        }

        $this->getLogger()->info(sprintf('This node did not get the %s cron lock for %s', $lockName, $lockName));

        return false;
    }
}
