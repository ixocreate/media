<?php
/**
 * kiwi-suite/admin (https://github.com/kiwi-suite/media)
 *
 * @package   kiwi-suite/media
 * @see       https://github.com/kiwi-suite/media
 * @copyright Copyright (c) 2010 - 2018 kiwi suite GmbH
 * @license   MIT License
 */

declare(strict_types=1);

namespace KiwiSuite\Media\Type;

use Doctrine\DBAL\Types\StringType;
use KiwiSuite\Contract\Schema\ElementInterface;
use KiwiSuite\Contract\Type\DatabaseTypeInterface;
use KiwiSuite\Contract\Type\SchemaElementInterface;
use KiwiSuite\Entity\Type\AbstractType;
use KiwiSuite\Media\Entity\Media;
use KiwiSuite\Media\Config\MediaConfig;
use KiwiSuite\Schema\Elements\ImageElement;
use KiwiSuite\Schema\ElementSubManager;

final class ImageType extends AbstractType implements DatabaseTypeInterface, SchemaElementInterface
{
    /**
     * @var array
     */
    private $imageWhitelist;

    /**
     * ImageType constructor.
     * @param MediaRepository $mediaRepository
     * @param Uri $uri
     */
    public function __construct(MediaConfig $mediaConfig)
    {
        $this->imageWhitelist = $mediaConfig->imageWhitelist();
    }

    /**
     * @param $value
     * @return mixed|null|object
     */
    protected function transform($value)
    {
        $mimeType = mime_content_type($value);
        $pathInfo = pathinfo($value);
        $extension = $pathInfo['extension'];

        if (!\array_key_exists($extension, $this->imageWhitelist) && !\in_array($mimeType, $this->imageWhitelist)) {
            return new \Exception('invalid image format');
        }
        return $value;
    }

    public function __toString()
    {
        return (string) $this->value();
    }

    public function convertToDatabaseValue()
    {
        return (string) $this->value();
    }

    public static function baseDatabaseType(): string
    {
        return StringType::class;
    }

    /**
     * @param ElementSubManager $elementSubManager
     * @return ElementInterface
     */
    public function schemaElement(ElementSubManager $elementSubManager): ElementInterface
    {
        return $elementSubManager->get(ImageElement::class);
    }

    public static function serviceName(): string
    {
        return 'image';
    }
}
