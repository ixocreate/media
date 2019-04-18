<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Package\Exception;

use Psr\Container\ContainerExceptionInterface;

class InvalidConfigException extends \RuntimeException implements ContainerExceptionInterface
{
}
