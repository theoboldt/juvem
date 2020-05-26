<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Formula at choice option is no longer supported
 */
final class Version20190207190000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_choice_option DROP price_formula');

    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE acquisition_attribute_choice_option ADD price_formula VARCHAR(255) DEFAULT NULL'
        );
    }
}
