<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add read event role to all assigned users, because this permission was implied previously
 */
final class Version20210223100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add read event role to all assigned users, because this permission was implied previously';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE event_user_assignment SET allowed_to_read=1');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE event_user_assignment SET allowed_to_read=0');
    }
}
