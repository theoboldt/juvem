<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add storage for formula event specific variables
 */
final class Version20191206140000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Add storage for formula event specific variables';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE acquisition_attribute_variable_event_value (eid INT NOT NULL, vid INT NOT NULL, variable_value DOUBLE PRECISION NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_33188C774FBDA576 (eid), INDEX IDX_33188C7751DDB85F (vid), INDEX deleted_at_idx (deleted_at), PRIMARY KEY(eid, vid)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE acquisition_attribute_variable_event (id INT AUTO_INCREMENT NOT NULL, bid INT DEFAULT NULL, description VARCHAR(255) NOT NULL, variable_value DOUBLE PRECISION DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_5643EE364AF2B3F3 (bid), INDEX deleted_at_idx (deleted_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event_value ADD CONSTRAINT FK_33188C774FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event_value ADD CONSTRAINT FK_33188C7751DDB85F FOREIGN KEY (vid) REFERENCES acquisition_attribute_variable_event (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute_variable_event ADD CONSTRAINT FK_5643EE364AF2B3F3 FOREIGN KEY (bid) REFERENCES acquisition_attribute (bid) ON DELETE CASCADE'
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE acquisition_attribute_variable_event_value');
        $this->addSql('DROP TABLE acquisition_attribute_variable_event');
    }
}
