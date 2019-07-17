<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Increase fillout value size
 */
final class Version20190717171821 extends AbstractMigration
{
    
    public function getDescription()
    {
        return 'Increase fillout value size';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_fillout MODIFY COLUMN value VARCHAR(2048)');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_fillout MODIFY COLUMN value VARCHAR(255)');
    }
}
