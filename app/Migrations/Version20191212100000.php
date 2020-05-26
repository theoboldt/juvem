<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Variables are now no longer related to attributes
 */
final class Version20191212100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Variables are now no longer related to attributes';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX deleted_at_idx ON acquisition_attribute_variable_event_value');
        $this->addSql('ALTER TABLE acquisition_attribute_variable_event_value DROP deleted_at');
        
        $this->addSql('ALTER TABLE acquisition_attribute_variable_event DROP FOREIGN KEY FK_5643EE364AF2B3F3');
        $this->addSql('DROP INDEX IDX_5643EE364AF2B3F3 ON acquisition_attribute_variable_event');
        $this->addSql('ALTER TABLE acquisition_attribute_variable_event DROP bid');
        $this->addSql(
            'RENAME TABLE acquisition_attribute_variable_event TO event_variable, acquisition_attribute_variable_event_value TO event_variable_value'
        );
        
        $this->addSql('ALTER TABLE event_variable_value RENAME INDEX idx_33188c774fbda576 TO IDX_1828869B4FBDA576');
        $this->addSql('ALTER TABLE event_variable_value RENAME INDEX idx_33188c7751ddb85f TO IDX_1828869B51DDB85F');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            'RENAME TABLE event_variable TO acquisition_attribute_variable_event, event_variable_value TO acquisition_attribute_variable_event_value'
        );
        $this->addSql('ALTER TABLE acquisition_attribute_variable_event ADD bid INT DEFAULT null');
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event ADD CONSTRAINT FK_5643EE364AF2B3F3 FOREIGN KEY(
        bid
    ) REFERENCES acquisition_attribute(bid) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_5643EE364AF2B3F3 ON acquisition_attribute_variable_event(bid)');
        
        $this->addSql('ALTER TABLE acquisition_attribute_variable_event_value ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX deleted_at_idx ON acquisition_attribute_variable_event_value (deleted_at)');
        
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event_value RENAME INDEX IDX_1828869B4FBDA576 TO idx_33188c774fbda576'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event_value RENAME INDEX IDX_1828869B51DDB85F TO idx_33188c7751ddb85f'
        );
    }
}
