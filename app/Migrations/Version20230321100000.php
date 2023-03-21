<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adding marker for events when they have cleared participants and employees
 */
final class Version20230321100000 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'Adding marker for events when they have cleared participants and employees';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE event ADD is_cleared TINYINT(1) DEFAULT '0' NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE event DROP is_cleared');
    }
}
