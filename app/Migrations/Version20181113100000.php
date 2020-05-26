<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add storage for employees
 */
final class Version20181113100000 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Add storage for employees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE employee_comment (cid INT AUTO_INCREMENT NOT NULL, gid INT DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, content LONGTEXT NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_143804634C397118 (gid), INDEX IDX_1438046325F94802 (modified_by), INDEX IDX_14380463DE12AB56 (created_by), PRIMARY KEY(cid)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE employee (gid INT AUTO_INCREMENT NOT NULL, eid INT DEFAULT NULL, salutation VARCHAR(64) NOT NULL, email VARCHAR(128) NOT NULL, name_first VARCHAR(128) NOT NULL, name_last VARCHAR(128) NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, address_street VARCHAR(128) NOT NULL, address_city VARCHAR(128) NOT NULL, address_zip VARCHAR(16) NOT NULL, uid INT DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_5D9F75A14FBDA576 (eid), PRIMARY KEY(gid)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE employee_comment ADD CONSTRAINT FK_143804634C397118 FOREIGN KEY (gid) REFERENCES employee (gid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE employee_comment ADD CONSTRAINT FK_1438046325F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE employee_comment ADD CONSTRAINT FK_14380463DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A14FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1539B0606 FOREIGN KEY (uid) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_5D9F75A1539B0606 ON employee (uid)');

        $this->addSql('ALTER TABLE phone_number ADD gid INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5B4C397118 FOREIGN KEY (gid) REFERENCES employee (gid) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_6B01BC5B4C397118 ON phone_number (gid)');

        $this->addSql('ALTER TABLE acquisition_attribute_fillout ADD gid INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE acquisition_attribute_fillout ADD CONSTRAINT FK_A1C5AC794C397118 FOREIGN KEY (gid) REFERENCES employee (gid) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_A1C5AC794C397118 ON acquisition_attribute_fillout (gid)');


    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE phone_number DROP FOREIGN KEY FK_6B01BC5B4C397118');
        $this->addSql('DROP INDEX IDX_6B01BC5B4C397118 ON phone_number');
        $this->addSql('ALTER TABLE phone_number DROP gid');

        $this->addSql('ALTER TABLE acquisition_attribute_fillout DROP FOREIGN KEY FK_A1C5AC794C397118');
        $this->addSql('DROP INDEX IDX_A1C5AC794C397118 ON acquisition_attribute_fillout');
        $this->addSql('ALTER TABLE acquisition_attribute_fillout DROP gid');

        $this->addSql('DROP TABLE employee_comment');
        $this->addSql('DROP TABLE employee');
    }
}
