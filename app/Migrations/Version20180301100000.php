<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Payment event value is now no longer nullable
 */
class Version20180301100000 extends AbstractMigration
{

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Payment event value is now no longer nullable';
    }

    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE participant_payment_event CHANGE price_value price_value INT NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE participant_payment_event CHANGE price_value price_value INT DEFAULT NULL');
    }
}
