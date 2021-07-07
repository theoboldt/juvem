<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to archive acquisition attributes
 */
final class Version20210707000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add possibility to archive acquisition attributes';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE acquisition_attribute ADD is_archived TINYINT(1) DEFAULT '0' NOT NULL");
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute DROP is_archived');
    }
}
