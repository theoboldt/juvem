<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add fields to store price formula
 */
final class Version20181227150000 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Add fields to store price formula';
    }
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE acquisition_attribute_choice_option ADD price_formula VARCHAR(255) DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute ADD is_price_formula_enabled SMALLINT UNSIGNED DEFAULT 0 NOT NULL, ADD price_formula VARCHAR(255) DEFAULT NULL'
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_choice_option DROP price_formula');
        $this->addSql('ALTER TABLE acquisition_attribute DROP is_price_formula_enabled, DROP price_formula');
        
    }
}
