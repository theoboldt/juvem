<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate to separated first/lastname for participant fillout
 */
final class Version20181226122000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $result = $this->connection->executeQuery(
            'SELECT acquisition_attribute_fillout.oid, acquisition_attribute_fillout.value AS fillout_value 
               FROM acquisition_attribute_fillout
         INNER JOIN acquisition_attribute ON (acquisition_attribute_fillout.bid = acquisition_attribute.bid)
              WHERE acquisition_attribute.field_type = ?',
            [\AppBundle\Form\ParticipantDetectingType::class]
        );

        $this->connection->beginTransaction();
        while ($row = $result->fetch()) {
            $filloutValue = $row['fillout_value'];
            if ($filloutValue !== null) {
                $filloutDecoded = json_decode($filloutValue, true);
                if (is_array($filloutDecoded) && isset($filloutDecoded['value'])) {
                    $filloutValue                                    = explode(' ', $filloutDecoded['value']);
                    $filloutDecoded['participantDetectingFirstName'] = array_shift($filloutValue);
                    $filloutDecoded['participantDetectingLastName']  = implode(' ', $filloutValue);
                    unset($filloutDecoded['value']);
                } else {
                    $filloutDecoded                                  = [];
                    $filloutValue                                    = explode(' ', $filloutValue);
                    $filloutDecoded['participantDetectingFirstName'] = array_shift($filloutValue);
                    $filloutDecoded['participantDetectingLastName']  = implode(' ', $filloutValue);
                }
                $this->connection->executeUpdate(
                    'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                    [json_encode($filloutDecoded), $row['oid']]
                );
            }
        }
        $this->connection->commit();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $result = $this->connection->executeQuery(
            'SELECT acquisition_attribute_fillout.oid, acquisition_attribute_fillout.value AS fillout_value 
               FROM acquisition_attribute_fillout
         INNER JOIN acquisition_attribute ON (acquisition_attribute_fillout.bid = acquisition_attribute.bid)
              WHERE acquisition_attribute.field_type = ?',
            [\AppBundle\Form\ParticipantDetectingType::class]
        );

        $this->connection->beginTransaction();
        while ($row = $result->fetch()) {
            $filloutValue = $row['fillout_value'];
            if ($filloutValue !== null) {
                $filloutDecoded = json_decode($filloutValue, true);
                if (is_array($filloutDecoded) && !isset($filloutDecoded['value'])) {
                    $value = [];
                    if (isset($filloutDecoded['participantDetectingFirstName'])) {
                        $value[] = $filloutDecoded['participantDetectingFirstName'];
                        unset($filloutDecoded['participantDetectingFirstName']);
                    }
                    if (isset($filloutDecoded['participantDetectingLastName'])) {
                        $value[] = $filloutDecoded['participantDetectingLastName'];
                        unset($filloutDecoded['participantDetectingLastName']);
                    }
                    $filloutDecoded['value'] = implode(' ', $value);
                    $this->connection->executeUpdate(
                        'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                        [json_encode($filloutDecoded), $row['oid']]
                    );
                }
            }
        }
        $this->connection->commit();
    }
}
