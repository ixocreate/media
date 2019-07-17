<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Config;

use Ixocreate\Application\Service\SerializableServiceInterface;
use Ixocreate\Media\MediaConfigurator;

class MediaPackageConfig implements SerializableServiceInterface
{
    private $whitelist = [
        'image' => [],
        'video' => [],
        'audio' => [],
        'global' => [],
        'document' => [],
    ];

    /**
     * @var string
     */
    private $driver;

    /*
     * @var bool
     */
    private $publicStatus;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var bool
     */
    private $parallelImageProcessing;

    /**
     * MediaPackageConfig constructor.
     *
     * @param MediaConfigurator $mediaConfigurator
     */
    public function __construct(MediaConfigurator $mediaConfigurator)
    {
        $this->whitelist = $mediaConfigurator->whitelist();

        $this->whitelist['image'] = \array_unique(\array_values($this->whitelist['image']));
        $this->whitelist['video'] = \array_unique(\array_values($this->whitelist['video']));
        $this->whitelist['video'] = \array_unique(\array_values($this->whitelist['video']));
        $this->whitelist['document'] = \array_unique(\array_values($this->whitelist['document']));

        $this->whitelist['global'] = \array_unique(
            \array_values(
                \array_merge(
                    $this->whitelist['global'],
                    $this->whitelist['image'],
                    $this->whitelist['video'],
                    $this->whitelist['audio'],
                    $this->whitelist['document']
                )
            )
        );
        $this->driver = $mediaConfigurator->driver();
        $this->publicStatus = $mediaConfigurator->publicStatus();
        $this->uri = $mediaConfigurator->uri();
        $this->parallelImageProcessing = $mediaConfigurator->isParallelImageProcessing();
    }

    /**
     * @return array
     * Fill with MIME-Types
     */
    public function whitelist(): array
    {
        return $this->whitelist['global'];
    }

    /**
     * @return string
     */
    public function driver(): string
    {
        return $this->driver;
    }

    /**
     * @return bool
     */
    public function publicStatus(): bool
    {
        return $this->publicStatus;
    }

    /**
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * @return array
     * Fill with MIME-Types
     */
    public function imageWhitelist(): array
    {
        return $this->whitelist['image'];
    }

    /**
     * @return array
     * Fill with MIME-Types
     */
    public function videoWhitelist(): array
    {
        return $this->whitelist['video'];
    }

    /**
     * @return array
     * Fill with MIME-Types
     */
    public function audioWhitelist(): array
    {
        return $this->whitelist['audio'];
    }

    /**
     * @return array
     * Fill with MIME-Types
     */
    public function documentWhitelist(): array
    {
        return $this->whitelist['document'];
    }

    /**
     * @return bool
     */
    public function isParallelImageProcessing(): bool
    {
        return $this->parallelImageProcessing;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return \serialize([
            'whitelist' => $this->whitelist,
            'publicStatus' => $this->publicStatus,
            'driver' => $this->driver,
            'uri' => $this->uri,
            'parallelImageProcessing' => $this->parallelImageProcessing,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $unserialized = \unserialize($serialized);
        $this->whitelist = $unserialized['whitelist'];
        $this->publicStatus = $unserialized['publicStatus'];
        $this->driver = $unserialized['driver'];
        $this->uri = $unserialized['uri'];
        $this->parallelImageProcessing = $unserialized['parallelImageProcessing'];
    }
}
