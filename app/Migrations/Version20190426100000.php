<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to store country of an participation/employee/event
 */
final class Version20190426100000 extends AbstractMigration
{
    
    public function getDescription()
    {
        return 'Add possibility to store country of an participation/employee/event';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD address_country VARCHAR(128) NOT NULL');
        $this->addSql("UPDATE event SET address_country = 'Deutschland'");
        $this->addSql('ALTER TABLE employee ADD address_country VARCHAR(128) NOT NULL');
        $this->addSql("UPDATE employee SET address_country = 'Deutschland'");
        $this->addSql('ALTER TABLE participation ADD address_country VARCHAR(128) NOT NULL');
        $this->addSql("UPDATE participation SET address_country = 'Deutschland'");
        
    }
    
    public function down(Schema $schema): void
    {
        
        $this->addSql('ALTER TABLE event DROP address_country');
        $this->addSql('ALTER TABLE employee DROP address_country');
        $this->addSql('ALTER TABLE participation DROP address_country');
    }
}
