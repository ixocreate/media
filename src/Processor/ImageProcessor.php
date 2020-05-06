<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Processor;

use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Ixocreate\Filesystem\FilesystemInterface;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Config\MediaPaths;
use Ixocreate\Media\ImageDefinition\ImageDefinitionInterface;
use Ixocreate\Media\MediaInterface;
use League\Flysystem\FileNotFoundException;

final class ImageProcessor
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var ImageDefinitionInterface
     */
    private $imageDefinition;

    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var Image
     */
    private $image;

    /**
     * @var array
     */
    private $imageParameters = [];

    /**
     * ImageProcessor constructor.
     *
     * @param MediaInterface $media
     * @param ImageDefinitionInterface $imageDefinition
     * @param MediaConfig $mediaConfig
     * @param FilesystemInterface $filesystem
     * @param Image|null $image
     */
    public function __construct(
        MediaInterface $media,
        ImageDefinitionInterface $imageDefinition,
        MediaConfig $mediaConfig,
        FilesystemInterface $filesystem,
        Image $image = null
    ) {
        $this->media = $media;
        $this->imageDefinition = $imageDefinition;
        $this->mediaConfig = $mediaConfig;
        $this->filesystem = $filesystem;
        $this->image = $image;
        $this->setParameters();
    }

    /**
     * @return string
     */
    public static function serviceName(): string
    {
        return 'ImageProcessor';
    }

    private function setParameters(): void
    {
        $this->imageParameters = [
            'definitionWidth' => $this->imageDefinition->width(),
            'definitionHeight' => $this->imageDefinition->height(),
            'definitionMode' => $this->imageDefinition->mode(),
            'definitionUpscale' => $this->imageDefinition->upscale(),
        ];
    }

    /**
     * Processes UploadAction & Editor Images
     *
     * @return bool
     */
    public function process(): bool
    {
        $imageManager = new ImageManager(['driver' => $this->mediaConfig->driver()]);

        $mediaPath = $this->media->publicStatus() ? MediaPaths::PUBLIC_PATH : MediaPaths::PRIVATE_PATH;

        try {
            /** @var Image $image */
            $image = ($this->image !== null) ? $this->image : $imageManager->make($this->filesystem->read($mediaPath . $this->media->basePath() . $this->media->filename()));
        } catch (FileNotFoundException $exception) {
            return false;
        }

        $this->imageParameters['imageWidth'] = $image->width();
        $this->imageParameters['imageHeight'] = $image->height();

        $this->checkMode($image, $this->imageParameters);

        $file = $mediaPath . MediaPaths::IMAGE_DEFINITION_PATH . $this->imageDefinition->directory() . '/' . $this->media->basePath() . $this->media->filename();

        $put = $this->filesystem->put(
            $file,
            (string) $image->encode(\pathinfo($file, PATHINFO_EXTENSION))
        );

        $image->destroy();

        return $put;
    }

    /**
     * @param Image $image
     * @param array $imageParameters
     */
    private function checkMode(Image $image, array $imageParameters)
    {
        switch ($imageParameters['definitionMode']) {
            case 'fit':
                $this->fit($image, $imageParameters);
                break;
            case 'fitCrop':
                $this->fitCrop($image, $imageParameters);
                break;
            case 'canvas':
                $this->canvas($image, $imageParameters);
                break;
            case 'canvasFitCrop':
                $this->canvasFitCrop($image, $imageParameters);
                break;
        }
    }

    /**
     * @param Image $image
     * @param array $imageParameters
     */
    private function fit(Image $image, array $imageParameters)
    {
        \extract($imageParameters);
        /** @var $definitionWidth int */
        /** @var $definitionHeight int */
        /** @var $definitionUpscale bool */
        $image->resize(
            $definitionWidth,
            $definitionHeight,
            function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
                if ($definitionUpscale === false) {
                    $constraint->upsize();
                }
                $constraint->aspectRatio();
            }
        );
    }

    /**
     * @param Image $image
     * @param array $imageParameters
     */
    private function fitCrop(Image $image, array $imageParameters)
    {
        \extract($imageParameters);
        /** @var $definitionWidth int */
        /** @var $definitionHeight int */
        /** @var $definitionUpscale bool */
        if ($definitionWidth != null && $definitionHeight != null) {
            $image->fit(
                $definitionWidth,
                $definitionHeight,
                function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
                    if ($definitionUpscale === false) {
                        $constraint->upsize();
                    }
                }
            );
        }
    }

    /**
     * @param Image $image
     * @param array $imageParameters
     */
    private function canvas(Image $image, array $imageParameters)
    {
        \extract($imageParameters);
        /** @var $definitionWidth int */
        /** @var $definitionHeight int */
        /** @var $definitionUpscale bool */
        $image->resize(
            $definitionWidth,
            $definitionHeight,
            function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
                if ($definitionUpscale === false) {
                    $constraint->upsize();
                }
                $constraint->aspectRatio();
            }
        );

        if (($image->width() != $definitionWidth) || ($image->height() != $definitionHeight)) {
            $image->resizeCanvas($definitionWidth, $definitionHeight);
        }
    }

    /**
     * @param Image $image
     * @param array $imageParameters
     */
    private function canvasFitCrop(Image $image, array $imageParameters)
    {
        \extract($imageParameters);
        /** @var $definitionWidth int */
        /** @var $definitionHeight int */
        /** @var $definitionUpscale bool */
        /** @var $imageWidth int */
        /** @var $imageHeight int */
        if ($imageWidth >= $definitionWidth && $imageHeight >= $definitionHeight) {
            $image->fit($definitionWidth, $definitionHeight);
        } elseif ($imageWidth >= $definitionWidth || $imageHeight >= $definitionHeight) {
            $image->crop($definitionWidth, $definitionHeight);
        }

        if (($image->width() != $definitionWidth) || ($image->height() != $definitionHeight)) {
            $image->resizeCanvas($definitionWidth, $definitionHeight);
        }
    }
}
