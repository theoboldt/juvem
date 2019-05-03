<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to override invoice template per event
 */
final class Version20190503070000 extends AbstractMigration
{
    
    public function getDescription()
    {
        return 'Add possibility to override invoice template per event';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD invoice_template_filename VARCHAR(255) DEFAULT NULL');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP invoice_template_filename');
    }
}
