<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Handler;

use Doctrine\DBAL\Driver\Connection;
use Ixocreate\Filesystem\FilesystemInterface;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Config\MediaPaths;
use Ixocreate\Media\Entity\MediaDefinitionInfo;
use Ixocreate\Media\ImageDefinition\ImageDefinitionInterface;
use Ixocreate\Media\ImageDefinition\ImageDefinitionSubManager;
use Ixocreate\Media\MediaInterface;
use Ixocreate\Media\Processor\ImageProcessor;
use Ixocreate\Media\Repository\MediaDefinitionInfoRepository;
use Ixocreate\Media\Repository\MediaRepository;
use League\Flysystem\FileNotFoundException;

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
     * @var ImageDefinitionInterface
     */
    private $imageDefinition;

    /**
     * @var MediaDefinitionInfoRepository
     */
    private $mediaDefinitionInfoRepository;

    /**
     * @var ImageDefinitionSubManager
     */
    private $imageDefinitionSubManager;

    /**
     * @var Connection
     */
    private $connection;

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
        MediaConfig $mediaConfig,
        Connection $master
    ) {
        $this->imageDefinitionSubManager = $imageDefinitionSubManager;
        $this->mediaConfig = $mediaConfig;
        $this->mediaDefinitionInfoRepository = $mediaDefinitionInfoRepository;
        $this->mediaRepository = $mediaRepository;
        $this->connection = $master;
    }

    /**
     * @return string
     */
    public static function serviceName(): string
    {
        return 'imageHandler';
    }

    /**
     * @param ImageDefinitionInterface $imageDefinition
     * @return ImageHandler
     */
    public function withImageDefinition(ImageDefinitionInterface $imageDefinition)
    {
        $handler = clone $this;
        $handler->imageDefinition = $imageDefinition;
        return $handler;
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

        if ($this->imageDefinition !== null) {
            $this->generate($this->imageDefinition, $filesystem);
        }

        if ($this->imageDefinition === null) {
            // restrict forking to cli, using pcntl_fork in apache or php-fpm environments is not reliable
            if ($this->mediaConfig->isParallelImageProcessing() && \php_sapi_name() === 'cli') {
                $pids = [];
                $this->connection->close();

                foreach ($this->imageDefinitionSubManager->getServices() as $imageDefinitionClassName) {
                    /** @var ImageDefinitionInterface $imageDefinition */
                    $imageDefinition = $this->imageDefinitionSubManager->get($imageDefinitionClassName);

                    $pid = \pcntl_fork();
                    switch ($pid) {
                        case -1:
                            throw new \RuntimeException('unable to fork child');
                        case 0:
                            $this->connection->connect();
                            $this->generate($imageDefinition, $filesystem);
                            exit(0);
                        default:
                            $pids[$pid] = $pid;
                    }
                }

                foreach ($pids as $pid) {
                    \pcntl_waitpid($pid, $status);
                }

                $this->connection->connect();
            } else {
                foreach ($this->imageDefinitionSubManager->getServices() as $imageDefinitionClassName) {
                    /** @var ImageDefinitionInterface $imageDefinition */
                    $imageDefinition = $this->imageDefinitionSubManager->get($imageDefinitionClassName);
                    $this->generate($imageDefinition, $filesystem);
                }
            }
        }

        if ($this->media->metaData() === null) {
            $this->updateMediaMetaData($filesystem);
        }
    }

    /**
     * @param ImageDefinitionInterface $imageDefinition
     * @param FilesystemInterface $filesystem
     * @throws \Exception
     */
    private function generate(ImageDefinitionInterface $imageDefinition, FilesystemInterface $filesystem)
    {
        $process = (new ImageProcessor($this->media, $imageDefinition, $this->mediaConfig, $filesystem))->process();

        if ($process === true) {
            $file = $this->mediaPath . MediaPaths::IMAGE_DEFINITION_PATH . $imageDefinition->directory() . '/' . $this->media->basePath() . $this->media->filename();

            $imageData = \getimagesizefromstring($filesystem->read($file));
            $fileSize = $filesystem->getSize($file);

            /** @var mediaDefinitionInfo $mediaDefinitionInfo */
            $mediaDefinitionInfo = $this->mediaDefinitionInfoRepository->find(['mediaId' => $this->media->id(), 'imageDefinition' => $imageDefinition::serviceName()]);

            // In Case there are no existing Entries, create new one
            if ($mediaDefinitionInfo === null) {
                $mediaDefinitionInfo = new MediaDefinitionInfo([
                    'mediaId' => $this->media->id(),
                    'imageDefinition' => $imageDefinition::serviceName(),
                    'width' => $imageData[0],
                    'height' => $imageData[1],
                    'fileSize' => $fileSize,
                    'createdAt' => new \DateTimeImmutable(),
                    'updatedAt' => new \DateTimeImmutable(),
                ]);
                $this->mediaDefinitionInfoRepository->save($mediaDefinitionInfo);
            } else {
                $mediaDefinitionInfo = $mediaDefinitionInfo->with('updatedAt', new \DateTimeImmutable());
                $this->mediaDefinitionInfoRepository->save($mediaDefinitionInfo);
            }
        }
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

        try {
            $imageData = \getimagesizefromstring($filesystem->read($file));
        } catch (FileNotFoundException $exception) {
            return;
        }

        $metaData = [
            'width' => $imageData[0],
            'height' => $imageData[1],
        ];

        $media = $this->media->with('metaData', $metaData);

        $this->mediaRepository->save($media);
    }
}
