<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to configure position for custom field
 */
final class Version20230325000000 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add possibility to configure position for custom field';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute ADD sort INT UNSIGNED DEFAULT 99999 NOT NULL');
        $this->addSql('UPDATE acquisition_attribute SET sort = bid*10');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute DROP sort');
    }
}
