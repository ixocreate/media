<?php declare(strict_types=1);

namespace KiwiMigration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use KiwiSuite\CommonTypes\Entity\DateTimeType;
use KiwiSuite\CommonTypes\Entity\UuidType;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180918092733 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('media_media_crop');
        $table->addColumn('id',UuidType::class);
        $table->addColumn('mediaId',UuidType::class);
        $table->addColumn('imageDefinition', Type::STRING);
        $table->addColumn('cropParameters', Type::JSON);
        $table->addColumn('createdAt', DateTimeType::class);
        $table->addColumn('updatedAt', DateTimeType::class);
        $table->setPrimaryKey(['id']);

    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable("media_media_crop");
    }
}
