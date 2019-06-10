<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Command\Image;

use Ixocreate\CommandBus\Command\AbstractCommand;
use Ixocreate\Filesystem\FilesystemInterface;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Config\MediaPaths;
use Ixocreate\Media\Entity\Media;
use Ixocreate\Media\Entity\MediaDefinitionInfo;
use Ixocreate\Media\ImageDefinition\ImageDefinitionInterface;
use Ixocreate\Media\ImageDefinition\ImageDefinitionSubManager;
use Ixocreate\Media\MediaInterface;
use Ixocreate\Media\Processor\EditorProcessor;
use Ixocreate\Media\Repository\MediaDefinitionInfoRepository;
use Ixocreate\Media\Repository\MediaRepository;
use Ramsey\Uuid\Uuid;

final class EditorCommand extends AbstractCommand
{
    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * @var ImageDefinitionSubManager
     */
    private $imageDefinitionSubManager;

    /**
     * @var MediaDefinitionInfoRepository
     */
    private $mediaDefinitionInfoRepository;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var array
     */
    private $cropParameter = [];

    /**
     * @var ImageDefinitionInterface || null
     */
    private $imageDefinition;

    /**
     * @var MediaInterface || null
     */
    private $media;

    /**
     * EditorCommand constructor.
     * @param MediaRepository $mediaRepository
     * @param MediaConfig $mediaConfig
     * @param ImageDefinitionSubManager $imageDefinitionSubManager
     * @param MediaDefinitionInfoRepository $mediaDefinitionInfoRepository
     */
    public function __construct(
        MediaRepository $mediaRepository,
        MediaConfig $mediaConfig,
        ImageDefinitionSubManager $imageDefinitionSubManager,
        MediaDefinitionInfoRepository $mediaDefinitionInfoRepository
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaConfig = $mediaConfig;
        $this->imageDefinitionSubManager = $imageDefinitionSubManager;
        $this->mediaDefinitionInfoRepository = $mediaDefinitionInfoRepository;
    }

    /**
     * @param FilesystemInterface $filesystem
     * @return EditorCommand
     */
    public function withFilesystem(FilesystemInterface $filesystem): EditorCommand
    {
        $command = clone $this;
        $command->filesystem = $filesystem;
        return $command;
    }

    /**
     * @param ImageDefinitionInterface $imageDefinition
     * @return EditorCommand
     */
    public function withImageDefinition(ImageDefinitionInterface $imageDefinition): EditorCommand
    {
        $command = clone $this;
        $command->imageDefinition = $imageDefinition;
        return $command;
    }

    /**
     * @param array $cropParameter
     * @return EditorCommand
     */
    public function withCropParameter(array $cropParameter): EditorCommand
    {
        $command = clone $this;
        $command->cropParameter = $cropParameter;
        return $command;
    }

    /**
     * @param MediaInterface $media
     * @return EditorCommand
     */
    public function withMedia(MediaInterface $media): EditorCommand
    {
        $command = clone $this;
        $command->media = $media;
        return $command;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function execute(): bool
    {
        (new EditorProcessor($this->cropParameter, $this->imageDefinition, $this->media, $this->mediaConfig, $this->filesystem))->process();

        $mediaPath = $this->media->publicStatus() ? MediaPaths::PUBLIC_PATH : MediaPaths::PRIVATE_PATH;

        $file = $mediaPath . MediaPaths::IMAGE_DEFINITION_PATH . $this->imageDefinition->directory() . '/' . $this->media->basePath() . $this->media->filename();

        $imageData = \getimagesizefromstring($this->filesystem->read($file));

        $fileSize = $this->filesystem->getSize($file);


        $mediaDefinitionInfo = $this->mediaDefinitionInfoRepository->findOneBy([
            'mediaId' => $this->media->id(),
            'imageDefinition' => $this->imageDefinition::serviceName(),
        ]);

        if (!empty($mediaDefinitionInfo)) {
            $mediaDefinitionInfo = $mediaDefinitionInfo->with('updatedAt', new \DateTime());
            $mediaDefinitionInfo = $mediaDefinitionInfo->with('cropParameters', $this->cropParameter);
            $mediaDefinitionInfo = $mediaDefinitionInfo->with('width', $imageData[0]);
            $mediaDefinitionInfo = $mediaDefinitionInfo->with('height', $imageData[1]);
            $mediaDefinitionInfo = $mediaDefinitionInfo->with('fileSize', $fileSize);
        }

        if (empty($mediaDefinitionInfo)) {
            $mediaDefinitionInfo = new MediaDefinitionInfo([
                'id' => $this->uuid(),
                'mediaId' => $this->media->id(),
                'imageDefinition' => $this->imageDefinition::serviceName(),
                'cropParameters' => $this->cropParameter,
                'width' => $imageData[0],
                'height' => $imageData[1],
                'fileSize' => $fileSize,
                'createdAt' => new \DateTimeImmutable(),
                'updatedAt' => new \DateTimeImmutable(),
            ]);
        }

        $this->mediaDefinitionInfoRepository->save($mediaDefinitionInfo);

        return true;
    }

    public static function serviceName(): string
    {
        return 'media-image-editor';
    }
}
