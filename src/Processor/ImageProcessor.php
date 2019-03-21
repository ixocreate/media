<?php
/**
 * @see https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Processor;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Ixocreate\Contract\Media\ImageDefinitionInterface;
use Ixocreate\Contract\Media\MediaInterface;
use Ixocreate\Media\Config\MediaConfig;
use Intervention\Image\Constraint;
use Ixocreate\Media\MediaPaths;
use League\Flysystem\FilesystemInterface;

final class ImageProcessor implements ProcessorInterface
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
    private $storage;

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
     * @param MediaInterface $media
     * @param ImageDefinitionInterface $imageDefinition
     * @param MediaConfig $mediaConfig
     * @param FilesystemInterface $storage
     * @param Image|null $image
     */
    public function __construct(
        MediaInterface $media,
        ImageDefinitionInterface $imageDefinition,
        MediaConfig $mediaConfig,
        FilesystemInterface $storage,
        Image $image = null
    )
    {
        $this->media = $media;
        $this->imageDefinition = $imageDefinition;
        $this->mediaConfig = $mediaConfig;
        $this->storage = $storage;
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
            'definitionWidth'     => $this->imageDefinition->width(),
            'definitionHeight'    => $this->imageDefinition->height(),
            'definitionMode'      => $this->imageDefinition->mode(),
            'definitionUpscale'   => $this->imageDefinition->upscale(),
        ];
    }

    /**
     * Processes UploadAction Images
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function process(): void
    {
        $imageManager = new ImageManager(['driver' => $this->mediaConfig->driver()]);

        $mediaPath = $this->media->publicStatus() ? MediaPaths::PUBLIC_PATH : MediaPaths::PRIVATE_PATH;

        $image = ($this->image != null) ? $this->image : $imageManager->make($this->storage->read($mediaPath . $this->media->basePath() . $this->media->filename()));

        $this->imageParameters['imageWidth'] = $image->width();
        $this->imageParameters['imageHeight'] = $image->height();

        $this->checkMode($image, $this->imageParameters);

        /** @var Image $tempImage */
        $this->storage->put($mediaPath . MediaPaths::IMAGE_DEFINITION_PATH . $this->imageDefinition->directory() . '/' . $this->media->basePath() . $this->media->filename(), $image->response());

        $image->destroy();
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

        $image->resize($definitionWidth, $definitionHeight, function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
            if ($definitionUpscale === false) {
                $constraint->upsize();
            }
            $constraint->aspectRatio();
        });
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
            $image->fit($definitionWidth, $definitionHeight, function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
                if ($definitionUpscale === false) {
                    $constraint->upsize();
                }
            });
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

        $image->resize($definitionWidth, $definitionHeight, function (Constraint $constraint) use ($definitionWidth, $definitionHeight, $definitionUpscale) {
            if ($definitionUpscale === false) {
                $constraint->upsize();
            }
            $constraint->aspectRatio();
        });

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
