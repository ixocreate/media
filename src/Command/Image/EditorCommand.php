<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Command\Image;

use Ixocreate\CommandBus\Command\AbstractCommand;
use Ixocreate\Media\ImageDefinitionInterface;
use Ixocreate\Filesystem\Storage\StorageSubManager;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Entity\Media;
use Ixocreate\Media\Entity\MediaCrop;
use Ixocreate\Media\Exception\InvalidConfigException;
use Ixocreate\Media\ImageDefinition\ImageDefinitionSubManager;
use Ixocreate\Media\Processor\EditorProcessor;
use Ixocreate\Media\Repository\MediaCropRepository;
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
     * @var MediaCropRepository
     */
    private $mediaCropRepository;

    /**
     * @var StorageSubManager
     */
    private $storageSubManager;

    public function __construct(
        MediaRepository $mediaRepository,
        MediaConfig $mediaConfig,
        ImageDefinitionSubManager $imageDefinitionSubManager,
        MediaCropRepository $mediaCropRepository,
        StorageSubManager $storageSubManager
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->mediaConfig = $mediaConfig;
        $this->imageDefinitionSubManager = $imageDefinitionSubManager;
        $this->mediaCropRepository = $mediaCropRepository;
        $this->storageSubManager = $storageSubManager;
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function execute(): bool
    {
        if (!$this->storageSubManager->has('media')) {
            throw new InvalidConfigException('Storage Config not set');
        }

        $storage = $this->storageSubManager->get('media');

        /** @var Media $media */
        $media = $this->dataValue('media');
        /** @var ImageDefinitionInterface $imageDefinition */
        $imageDefinition = $this->dataValue('imageDefinition');

        $requestData = $this->dataValue('requestData');

        $mediaCrop = null;

        if (!empty($this->mediaCropRepository->findOneBy(['mediaId' => $media->id(), 'imageDefinition' => $imageDefinition::serviceName()]))) {
            /** @var MediaCrop $mediaCrop */
            $mediaCrop = $this->mediaCropRepository->findOneBy(['mediaId' => $media->id(), 'imageDefinition' => $imageDefinition::serviceName()]);

            if ($mediaCrop::getDefinitions()->has('updatedAt')) {
                $mediaCrop = $mediaCrop->with('updatedAt', new \DateTime());
            }

            if ($mediaCrop::getDefinitions()->has('cropParameters')) {
                $mediaCrop = $mediaCrop->with('cropParameters', $requestData['crop']);
            }
        }

        if (empty($this->mediaCropRepository->findOneBy(['mediaId' => $media->id(), 'imageDefinition' => $imageDefinition::serviceName()]))) {
            $mediaCrop = new MediaCrop([
                'id' => Uuid::uuid4(),
                'mediaId' => $media->id(),
                'imageDefinition' => $imageDefinition::serviceName(),
                'cropParameters' => $requestData['crop'],
                'createdAt' => new \DateTimeImmutable(),
                'updatedAt' => new \DateTimeImmutable(),
            ]);
        }

        (new EditorProcessor($requestData['crop'], $imageDefinition, $media, $this->mediaConfig, $storage))->process();

        $this->mediaCropRepository->save($mediaCrop);

        return true;
    }

    public static function serviceName(): string
    {
        return 'media-image-editor';
    }
}
