<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add acquisition field public status
 */
final class Version20180715220515 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute ADD is_public TINYINT(1) NOT NULL');
        $this->addSql('UPDATE acquisition_attribute SET is_public = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute DROP is_public');
    }
}
