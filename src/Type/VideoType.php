<?php
/**
 * kiwi-suite/media (https://github.com/kiwi-suite/media)
 *
 * @package kiwi-suite/media
 * @see https://github.com/kiwi-suite/media
 * @copyright Copyright (c) 2010 - 2018 kiwi suite GmbH
 * @license MIT License
 */
declare(strict_types=1);
namespace KiwiSuite\Media\Type;


use KiwiSuite\Contract\Type\DatabaseTypeInterface;
use KiwiSuite\Contract\Type\SchemaElementInterface;
use KiwiSuite\Entity\Type\AbstractType;
use Doctrine\DBAL\Types\GuidType;
use KiwiSuite\Media\Entity\Media;
use KiwiSuite\Media\Config\MediaConfig;
use Doctrine\DBAL\Types\StringType;
use KiwiSuite\Media\Repository\MediaRepository;
use KiwiSuite\Schema\ElementSubManager;
use KiwiSuite\Contract\Schema\ElementInterface;

final class VideoType extends MediaType implements DatabaseTypeInterface, SchemaElementInterface
{
    /**
     * @var array
     */
    private $videoWhitelist;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * VideoType constructor.
     * @param MediaConfig $mediaConfig
     * @param MediaRepository $mediaRepository
     */
    public function __construct(MediaConfig $mediaConfig, MediaRepository $mediaRepository)
    {
        $this->videoWhitelist = $mediaConfig->videoWhitelist();
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @param Media $media
     * @throws  \Exception
     */
    protected function validateType(Media $media)
    {
        $extension = \pathinfo($media->filename(), PATHINFO_EXTENSION);

        if (!\in_array($media->mimeType(), $this->videoWhitelist) || !\array_key_exists($extension, $this->videoWhitelist)) {
            throw new \Exception('not a valid ImageType');
        }
        return $media;
    }

    /**
     * @param $value
     * @return mixed|null|object
     */
    protected function transform($value)
    {
        if (is_array($value)) {
            if (empty($value['id'])) {
                return null;
            }

            $value = $value['id'];
        }
        $value = $this->mediaRepository->find($value);

        if (!empty($value)) {
            return $this->validateType($value);
        }
        return null;
    }

    /**
     * @param ElementSubManager $elementSubManager
     * @return ElementInterface
     */
    public function schemaElement(ElementSubManager $elementSubManager): ElementInterface
    {
        return $elementSubManager->get(VideoElement::class);
    }

    public static function serviceName(): string
    {
        return 'video';
    }
}