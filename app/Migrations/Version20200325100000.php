<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add property to store confirmation notification date of participants
 */
final class Version20200325100000 extends AbstractMigration
{
    
    public function getDescription(): string
    {
        return 'Add property to store confirmation notification date of participants';
    }
    
    public function up(Schema $schema): void
    {
        /** @see \AppBundle\BitMask\ParticipantStatus::TYPE_STATUS_CONFIRMED : 1 */
        $this->addSql('ALTER TABLE participant ADD confirmation_sent_at DATETIME DEFAULT NULL');
        $this->addSql("UPDATE participant SET confirmation_sent_at = '2000-01-01 10:00:00' WHERE (status & 1) = 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP confirmation_sent_at');
    }
}
