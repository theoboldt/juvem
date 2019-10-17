<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add fields to store recipe feedback
 */
final class Version20191017100000 extends AbstractMigration
{
    
    /**
     * Determine if json support is available
     *
     * @param Connection $connection
     * @return bool
     */
    public static function supportsJson(Connection $connection): bool {
        try {
            $connection->executeQuery('SELECT JSON_ARRAY("a","b")');
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    
    public function up(Schema $schema): void
    {
        $type = self::supportsJson($this->connection) ? 'JSON' : 'LONGTEXT';
        
        $this->addSql(
            'CREATE TABLE recipe_feedback (id INT UNSIGNED AUTO_INCREMENT NOT NULL, rid INT UNSIGNED DEFAULT NULL, eid INT DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, people_count INT UNSIGNED NOT NULL, occurrence_date DATE NOT NULL, weight SMALLINT UNSIGNED NOT NULL, comment LONGTEXT NOT NULL, feedback_global SMALLINT NOT NULL, feedback '.$type.' NOT NULL COMMENT \'(DC2Type:json_array)\', modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_A4B9E1C656D41083 (rid), INDEX IDX_A4B9E1C64FBDA576 (eid), INDEX IDX_A4B9E1C625F94802 (modified_by), INDEX IDX_A4B9E1C6DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE recipe_feedback ADD CONSTRAINT FK_A4B9E1C656D41083 FOREIGN KEY (rid) REFERENCES recipe (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE recipe_feedback ADD CONSTRAINT FK_A4B9E1C64FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE recipe_feedback ADD CONSTRAINT FK_A4B9E1C625F94802 FOREIGN KEY (modified_by) REFERENCES user (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE recipe_feedback ADD CONSTRAINT FK_A4B9E1C6DE12AB56 FOREIGN KEY (created_by) REFERENCES user (uid) ON DELETE SET NULL;'
        );
        
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE recipe_feedback');
    }
}
