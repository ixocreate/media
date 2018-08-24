<?php declare(strict_types=1);

namespace KiwiMigration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use KiwiSuite\CommonTypes\Entity\DateTimeType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180824071610 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('media_media');
        $table->addColumn('hash', Type::STRING);
        $table->addColumn('updatedAt', DateTimeType::class);
        $table->addColumn('deletedAt', DateTimeType::class)->setNotnull(false);
    }

    public function down(Schema $schema) : void
    {
    }
}
