<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add column for storing image caption
 */
final class Version20190827113444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add column for storing image caption';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gallery_image ADD caption VARCHAR(2048) NOT NULL DEFAULT \'\'');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gallery_image DROP caption');
    }
}
