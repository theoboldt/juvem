<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Provide field for parent employee
 */
final class Version20191024000000 extends AbstractMigration
{
    
    public function getDescription()
    {
        return 'Provide field for parent employee';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee ADD predecessor_gid INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A14A583460 FOREIGN KEY (predecessor_gid) REFERENCES employee (gid) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_5D9F75A14A583460 ON employee (predecessor_gid)');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A14A583460');
        $this->addSql('DROP INDEX IDX_5D9F75A14A583460 ON employee');
        $this->addSql('ALTER TABLE employee DROP predecessor_gid;');
    }
}
