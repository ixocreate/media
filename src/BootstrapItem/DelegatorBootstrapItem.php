<?php
declare(strict_types=1);

namespace KiwiSuite\Media\BootstrapItem;

use KiwiSuite\Contract\Application\BootstrapItemInterface;
use KiwiSuite\Contract\Application\ConfiguratorInterface;
use KiwiSuite\Media\Delegator\DelegatorConfigurator;

final class DelegatorBootstrapItem implements BootstrapItemInterface
{
    /**
     * @return mixed
     */
    public function getConfigurator(): ConfiguratorInterface
    {
        return new DelegatorConfigurator();
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return 'delegator.php';
    }

    /**
     * @return string
     */
    public function getVariableName(): string
    {
        return 'delegator';
    }
}