<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Added possibility to have unconfirmed employees
 */
final class Version20211028000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added possibility to have unconfirmed employees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee ADD is_confirmed TINYINT(1) NOT NULL;');
        $this->addSql('UPDATE employee SET is_confirmed = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee DROP is_confirmed');
    }
}
