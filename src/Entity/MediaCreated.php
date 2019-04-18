<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Package\Media\Entity;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Ixocreate\Entity\DatabaseEntityInterface;
use Ixocreate\Package\Entity\DefinitionCollection;
use Ixocreate\Package\Entity\EntityInterface;
use Ixocreate\Package\Entity\EntityTrait;
use Ixocreate\Package\Entity\Entity\Definition;
use Ixocreate\Package\Type\Entity\UuidType;

final class MediaCreated implements EntityInterface, DatabaseEntityInterface
{
    use EntityTrait;

    private $mediaId;

    private $createdBy;

    public function mediaId(): UuidType
    {
        return $this->mediaId;
    }

    public function createdBy(): UuidType
    {
        return $this->createdBy;
    }

    /**
     * @return DefinitionCollection
     */
    protected static function createDefinitions(): DefinitionCollection
    {
        return new DefinitionCollection([
            new Definition('mediaId', UuidType::class, false, true),
            new Definition('createdBy', UuidType::class, false, true),
        ]);
    }

    public static function loadMetadata(ClassMetadataBuilder $builder)
    {
        $builder->setTable('media_media_created');

        $builder->createField('mediaId', UuidType::serviceName())->makePrimaryKey()->build();
        $builder->createField('createdBy', UuidType::serviceName())->makePrimaryKey()->build();
    }
}
