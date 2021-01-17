<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210117220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert gender field into dynamic field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant CHANGE gender gender VARCHAR(128) NOT NULL');
        $this->addReplaceGenderValue('1', 'männlich');
        $this->addReplaceGenderValue('2', 'weiblich');
    }

    public function down(Schema $schema): void
    {
        $this->addReplaceGenderValue('weiblich', '2');
        $this->addReplaceGenderValue('divers, eher weiblich', '2');
        $this->addSql(
            'UPDATE participant SET gender = :gender_new WHERE gender <> :gender_old_a AND gender <> :gender_old_b',
            ['gender_new' => '1', 'gender_old_a' => 'weiblich', 'gender_old_b' => 'divers, eher weiblich']
        );
        $this->addSql('ALTER TABLE participant CHANGE gender gender VARCHAR(128) NOT NULL');
    }

    /**
     * Replace gender value
     *
     * @param string $old
     * @param string $new
     */
    private function addReplaceGenderValue(string $old, string $new)
    {
        $this->addSql(
            'UPDATE participant SET gender = :gender_new WHERE gender = :gender_old',
            ['gender_new' => $new, 'gender_old' => $old]
        );
    }
}
