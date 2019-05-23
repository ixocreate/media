<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Handler;

use Ixocreate\Filesystem\FilesystemInterface;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Entity\MediaDefinitionInfo;
use Ixocreate\Media\ImageDefinition\ImageDefinitionSubManager;
use Ixocreate\Media\ImageDefinitionInterface;
use Ixocreate\Media\MediaHandlerInterface;
use Ixocreate\Media\MediaInterface;
use Ixocreate\Media\MediaPaths;
use Ixocreate\Media\Processor\ImageProcessor;
use Ixocreate\Media\Repository\MediaDefinitionInfoRepository;
use Ixocreate\Media\Repository\MediaRepository;

final class ImageHandler implements MediaHandlerInterface
{
    /**
     * @var array
     */
    private $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * @var array
     */
    private $allowedFileExtensions = [
        'jpeg',
        'jpg',
        'jpe',
        'png',
        'gif',
    ];

    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var string
     */
    private $mediaPath;

    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var MediaDefinitionInfoRepository
     */
    private $mediaDefinitionInfoRepository;

    /**
     * @var ImageDefinitionSubManager
     */
    private $imageDefinitionSubManager;

    /**
     * Image constructor.
     *
     * @param MediaRepository $mediaRepository
     * @param MediaDefinitionInfoRepository $mediaDefinitionInfoRepository
     * @param ImageDefinitionSubManager $imageDefinitionSubManager
     * @param MediaConfig $mediaConfig
     */
    public function __construct(
        MediaRepository $mediaRepository,
        MediaDefinitionInfoRepository $mediaDefinitionInfoRepository,
        ImageDefinitionSubManager $imageDefinitionSubManager,
        MediaConfig $mediaConfig
    ) {
        $this->imageDefinitionSubManager = $imageDefinitionSubManager;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDefinitionInfoRepository = $mediaDefinitionInfoRepository;
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @return string
     */
    public static function serviceName(): string
    {
        return 'imageHandler';
    }

    /**
     * @param MediaInterface $media
     * @return bool
     */
    public function isResponsible(MediaInterface $media): bool
    {
        $pathInfo = \pathinfo($media->filename());
        $extension = $pathInfo['extension'];
        if ((!\in_array($media->mimeType(), $this->allowedMimeTypes)) &&
            (!\in_array($extension, $this->allowedFileExtensions))) {
            return false;
        }
        return true;
    }

    /**
     * * Directs a Image-File to the Image Processor (which transforms the Image with the given ImageDefinition Parameters)
     * & creates + saves a MediaDefinitionInfo-Entity in the Database.
     *
     * @param MediaInterface $media
     * @param FilesystemInterface $filesystem
     * @throws \Exception
     */
    public function process(MediaInterface $media, FilesystemInterface $filesystem): void
    {
        $this->media = $media;
        $this->mediaPath = $this->media->publicStatus() ? MediaPaths::PUBLIC_PATH : MediaPaths::PRIVATE_PATH;

        foreach ($this->imageDefinitionSubManager->getServices() as $imageDefinitionClassName) {
            /** @var ImageDefinitionInterface $imageDefinition */
            $imageDefinition = $this->imageDefinitionSubManager->get($imageDefinitionClassName);

            $process = (new ImageProcessor($this->media, $imageDefinition, $this->mediaConfig, $filesystem))->process();

            if ($process === true) {

                $file = $this->mediaPath . MediaPaths::IMAGE_DEFINITION_PATH . $imageDefinition->directory() . '/' . $this->media->basePath() . $this->media->filename();

                $imageData = \getimagesizefromstring($filesystem->read($file));
                $fileSize = $filesystem->getSize($file);

                $mediaDefinitionInfo = new MediaDefinitionInfo([
                    'mediaId' => $this->media->id(),
                    'imageDefinition' => $imageDefinition::serviceName(),
                    'width' => $imageData[0],
                    'height' => $imageData[1],
                    'fileSize' => $fileSize,
                    'createdAt' => new \DateTimeImmutable(),
                    'updatedAt' => new \DateTimeImmutable()
                ]);

                $this->mediaDefinitionInfoRepository->save($mediaDefinitionInfo);

            }
        }
        $this->updateMediaMetaData($filesystem);
    }

    /**
     * @return array
     */
    public function directories(): array
    {
        $directories = [];
        foreach ($this->imageDefinitionSubManager->getServices() as $imageDefinitionClassName) {
            /** @var ImageDefinitionInterface $imageDefinition */
            $imageDefinition = $this->imageDefinitionSubManager->get($imageDefinitionClassName);

            $directories[] = MediaPaths::IMAGE_DEFINITION_PATH . $imageDefinition->directory() . '/';
        }
        return $directories;
    }

    /**
     * @param FilesystemInterface $filesystem
     * @return void
     */
    private function updateMediaMetaData(FilesystemInterface $filesystem): void
    {
        $file = $this->mediaPath . $this->media->basePath() . $this->media->filename();

        $imageData = \getimagesizefromstring($filesystem->read($file));

        $metaData = [
            'width' => $imageData[0],
            'height' => $imageData[1]
        ];

        if ($this->media->metaData() === array()) {
            $metaData = \array_merge($this->media->metaData(), $metaData);
        }

        $media = $this->media->with('metaData', $metaData);

        $this->mediaRepository->save($media);
    }
}
