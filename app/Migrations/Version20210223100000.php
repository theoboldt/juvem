<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cloud role for all admin users
 */
final class Version20210223100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cloud role for all admin users';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_user_assignment ADD allowed_to_read TINYINT(1) NOT NULL');
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM user',
            []
        );
        
        foreach ($rows as $row) {
            $roles = unserialize($row['roles']);
            if (in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_CLOUD';
                $this->addSql(
                    'UPDATE user SET roles = ? WHERE uid = ?',
                    [serialize($roles), $row['uid']]
                );
            }
        }
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event_user_assignment DROP allowed_to_read'
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM user',
            []
        );
        
        foreach ($rows as $row) {
            $roles = unserialize($row['roles']);
            if (($key = array_search('ROLE_CLOUD', $roles)) !== false) {
                unset($roles[$key]);
                $this->addSql(
                    'UPDATE user SET roles = ? WHERE uid = ?',
                    [serialize($roles), $row['uid']]
                );
            }
        }
        
    }
}
