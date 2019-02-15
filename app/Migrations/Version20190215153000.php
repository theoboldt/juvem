<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add invoice table
 */
final class Version20190215153000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE participation_invoice (id INT AUTO_INCREMENT NOT NULL, pid INT DEFAULT NULL, created_by INT DEFAULT NULL, invoice_sum INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_DB1BA6855550C4ED (pid), INDEX IDX_DB1BA685DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE participation_invoice ADD CONSTRAINT FK_DB1BA6855550C4ED FOREIGN KEY (pid) REFERENCES participation (pid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE participation_invoice ADD CONSTRAINT FK_DB1BA685DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE participation_invoice');
    }
}
