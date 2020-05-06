<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Ixocreate\Database\DatabaseEntityInterface;
use Ixocreate\Entity\Definition;
use Ixocreate\Entity\DefinitionCollection;
use Ixocreate\Entity\EntityInterface;
use Ixocreate\Entity\EntityTrait;
use Ixocreate\Media\MediaInterface;
use Ixocreate\Schema\Type\DateTimeType;
use Ixocreate\Schema\Type\TypeInterface;
use Ixocreate\Schema\Type\UuidType;

final class Media implements EntityInterface, DatabaseEntityInterface, MediaInterface
{
    use EntityTrait;

    private $id;

    private $basePath;

    private $filename;

    private $mimeType;

    private $fileSize;

    private $hash;

    private $publicStatus;

    private $metaData;

    private $createdBy;

    private $createdAt;

    private $updatedAt;

    private $deletedAt;

    public function id(): UuidType
    {
        return $this->id;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function fileSize(): int
    {
        return $this->fileSize;
    }

    public function publicStatus(): bool
    {
        return $this->publicStatus;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function metaData(): ?array
    {
        return $this->metaData;
    }

    public function createdBy(): ?UuidType
    {
        return $this->createdBy;
    }

    public function createdAt(): DateTimeType
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeType
    {
        return $this->updatedAt;
    }

    public function deletedAt(): DateTimeType
    {
        return $this->deletedAt;
    }

    protected static function createDefinitions(): DefinitionCollection
    {
        return new DefinitionCollection([
            new Definition('id', UuidType::class, false, true),
            new Definition('basePath', TypeInterface::TYPE_STRING, false, true),
            new Definition('filename', TypeInterface::TYPE_STRING, false, true),
            new Definition('mimeType', TypeInterface::TYPE_STRING, false, true),
            new Definition('fileSize', TypeInterface::TYPE_INT, false, true),
            new Definition('hash', TypeInterface::TYPE_STRING, false, false),
            new Definition('publicStatus', 'bool', false, true),
            new Definition('metaData', TypeInterface::TYPE_ARRAY, true, true),
            new Definition('createdBy', UuidType::class, true, true),
            new Definition('createdAt', DateTimeType::class, false, true),
            new Definition('updatedAt', DateTimeType::class, true, true),
            new Definition('deletedAt', DateTimeType::class, true, true),
        ]);
    }

    public static function loadMetadata(ClassMetadataBuilder $builder)
    {
        $builder->setTable('media_media');

        $builder->createField('id', UuidType::serviceName())->makePrimaryKey()->build();
        $builder->createField('basePath', 'string')->nullable(false)->build();
        $builder->createField('filename', 'string')->nullable(false)->build();
        $builder->createField('mimeType', Type::STRING)->nullable(false)->build();
        $builder->createField('fileSize', Type::INTEGER)->nullable(false)->build();
        $builder->createField('hash', Type::STRING)->nullable(false)->build();
        $builder->createField('publicStatus', 'boolean')->nullable(false)->build();
        $builder->createField('metaData', Type::JSON)->nullable(true)->build();
        $builder->createField('createdBy', UuidType::serviceName())->nullable(true)->build();
        $builder->createField('createdAt', DateTimeType::serviceName())->nullable(false)->build();
        $builder->createField('updatedAt', DateTimeType::serviceName())->nullable(false)->build();
        $builder->createField('deletedAt', DateTimeType::serviceName())->nullable(true)->build();
    }
}
