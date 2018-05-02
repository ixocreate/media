<?php
declare(strict_types=1);

namespace KiwiSuite\Media\ImageDefinition\Definitions;

use KiwiSuite\Media\ImageDefinition\ImageDefinitionInterface;

final class AdminThumb implements ImageDefinitionInterface
{
    /**
     * @var int
     */
    private $width = 500;

    /**
     * @var int
     */
    private $height = 500;

    /**
     * @var bool
     */
    private $fit = true;

    /**
     * @var string
     */
    private $directory = 'admin-thumb';

    /**
     * @return string
     */
    public static function getName(): string
    {
        return "AdminThumb";
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @return bool
     */
    public function getFit(): bool
    {
        return $this->fit;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

}