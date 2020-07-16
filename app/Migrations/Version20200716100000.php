<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enable keeping comments when reverting participant attendance list fillout
 */
final class Version20200716100000 extends AbstractMigration
{
    
    public function getDescription(): string
    {
        return 'Enable keeping comments when reverting participant attendance list fillout';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE attendance_list_participant_fillout CHANGE choice_id choice_id INT UNSIGNED DEFAULT NULL;'
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE attendance_list_participant_fillout CHANGE choice_id choice_id INT UNSIGNED NOT NULL'
        );
    }
}
