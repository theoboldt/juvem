<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Prepare built-in system options. Add field to store custom field choice option description
 */
final class Version20230221200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prepare structure for built-in system options';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        
        $this->addSql("ALTER TABLE acquisition_attribute ADD is_system TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE acquisition_attribute_choice_option ADD form_description TEXT NOT NULL, ADD is_archived TINYINT(1) DEFAULT '0' NOT NULL, ADD is_system TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("UPDATE acquisition_attribute_choice_option SET form_description =''");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE acquisition_attribute SET management_title = CONCAT(management_title, ' (SYSTEM)') WHERE is_system = 1");
        $this->addSql("UPDATE acquisition_attribute_choice_option SET management_title = CONCAT(management_title, ' (SYSTEM)') WHERE is_system = 1");
        
        $this->addSql('ALTER TABLE acquisition_attribute DROP is_system');
        $this->addSql('ALTER TABLE acquisition_attribute_choice_option DROP form_description, DROP is_archived, DROP is_system');
    }
}
