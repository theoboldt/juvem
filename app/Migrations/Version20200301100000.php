<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Now able to store if invoice was sent or not
 */
final class Version20200301100000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Now able to store if invoice was sent';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE participation_invoice ADD is_sent TINYINT(1) DEFAULT '0' NOT NULL"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_invoice DROP is_sent');
    }
}
