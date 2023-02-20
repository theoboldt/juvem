<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert acquisition fillout data to custom field values
 */
final class Version20221110000000 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Convert acquisition fillout data to custom field values';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $this->connection->beginTransaction();
        foreach (['participation', 'participant', 'employee'] as $table) {
            $this->connection->executeStatement(
                'UPDATE ' . $table . ' SET custom_field_values = NULL',
                []
            );
        }
        
        $acquisitionAttributeQuery = $this->connection->executeQuery(
            'SELECT acquisition_attribute.bid,
                    acquisition_attribute.field_type
               FROM acquisition_attribute',
            []
        );
        $acquisitionAttributes     = $acquisitionAttributeQuery->fetchAllKeyValue();

        $updateQueue = [];

        $filloutQuery = $this->connection->executeQuery(
            'SELECT acquisition_attribute_fillout.bid,
                    acquisition_attribute_fillout.pid,
                    acquisition_attribute_fillout.aid,
                    acquisition_attribute_fillout.value,
                    acquisition_attribute_fillout.gid
               FROM acquisition_attribute_fillout',
            []
        );
        while ($row = $filloutQuery->fetchAssociative()) {
            $bid       = (int)$row['bid'];
            $typeClass = $acquisitionAttributes[$bid];

            $comment = null;
            switch ($typeClass) {
                case 'AppBundle\Form\BankAccountType':
                    $type = 'bank_account';
                    if (empty($row['value'])) {
                        $value = null;
                    } else {
                        $value = json_decode($row['value'], true);
                        if (json_last_error()) {
                            $comment = 'JSON ERROR: ' . json_last_error_msg() . $row['value'];
                            $value   = null;
                        } else {
                            $value = [
                                'bic'   => $value['bankAccountBic'],
                                'iban'  => $value['bankAccountIban'],
                                'owner' => $value['bankAccountOwner'],
                            ];
                        }
                    }
                    break;
                case 'AppBundle\Form\GroupType':
                    if (empty($row['value'])) {
                        $value = null;
                    } else {
                        if (is_numeric($row['value'])) {
                            $value = (int)$row['value'];
                        } else {
                            $value   = null;
                            $comment = 'ERROR: Non numeric value occurred: ' . $row['value'];
                        }
                    }
                    $type = 'group';
                    break;
                case 'AppBundle\Form\ParticipantDetectingType':
                    if (empty($row['value'])) {
                        $value = null;
                    } else {
                        $value = json_decode($row['value'], true);
                        if (json_last_error()) {
                            $comment = 'JSON ERROR: ' . json_last_error_msg() . $row['value'];
                            $value   = null;
                        }
                    }
                    $type = 'participant_detecting';
                    break;
                case 'Symfony\Component\Form\Extension\Core\Type\ChoiceType':
                    $type = 'choice';
                    if (empty($row['value'])) {
                        $value = null;
                    } elseif (is_numeric($row['value'])) {
                        $value = [(int)$row['value']];
                    } else {
                        $valueRaw = json_decode($row['value'], true);
                        if (json_last_error()) {
                            $comment = 'JSON ERROR: ' . json_last_error_msg() . $row['value'];
                            $value   = null;
                        } else {
                            $value = [];
                            foreach ($valueRaw as $item) {
                                $value[] = (int)$item;
                            }
                        }
                    }
                    break;
                case 'Symfony\Component\Form\Extension\Core\Type\NumberType':
                    $type = 'number';
                    if (empty($row['value'])) {
                        $value = null;
                    } elseif (is_numeric($row['value'])) {
                        $value = (int)$row['value'];
                    } else {
                        $value   = null;
                        $comment = 'ERROR: Non numeric value occurred: ' . $row['value'];
                    }
                    break;
                case 'Symfony\Component\Form\Extension\Core\Type\TextType':
                case 'Symfony\Component\Form\Extension\Core\Type\TextareaType':
                    $type  = 'text';
                    $value = $row['value'];
                    break;
                case 'Symfony\Component\Form\Extension\Core\Type\DateType':
                    $type  = 'date';
                    $value = $row['value'];
                    break;
                default:
                    throw new \RuntimeException('Unknown type '.$typeClass.' occurred for row:' . json_encode($row));
            }

            $table = null;
            $id    = null;
            if ($row['pid']) {
                $table = 'participation';
                $id    = (int)$row['pid'];
            } elseif ($row['aid']) {
                $table = 'participant';
                $id    = (int)$row['aid'];
            } elseif ($row['gid']) {
                $table = 'employee';
                $id    = (int)$row['gid'];
            }
            if ($table === null) {
                throw new \RuntimeException('Unknown datum occurred :' . json_encode($row));
            }

            $updateQueue[$table][$id][$bid] = [
                'bid'     => $bid,
                'type'    => $type,
                'value'   => $value,
                'comment' => $comment,
            ];
        }

        foreach ($updateQueue as $table => $entries) {
            foreach ($entries as $id => $entry) {
                switch ($table) {
                    case 'participation':
                        $idColumn = 'pid';
                        break;
                    case 'participant':
                        $idColumn = 'aid';
                        break;
                    case 'employee':
                        $idColumn = 'gid';
                        break;
                    default:
                        throw new \RuntimeException('Unknown table occurred: ' . $table);
                }

                $this->connection->executeStatement(
                    'UPDATE ' . $table . ' SET custom_field_values = ? WHERE ' . $idColumn . ' = ?',
                    [json_encode($entry), $id]
                );
            }
        }
        $this->connection->executeStatement(
            'DELETE FROM acquisition_attribute_fillout',
            []
        );

        $this->connection->commit();
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $this->connection->beginTransaction();
        $this->connection->executeStatement(
            'DELETE FROM acquisition_attribute_fillout',
            []
        );

        foreach (['participation', 'participant', 'employee'] as $table) {
            switch ($table) {
                case 'participation':
                    $idColumn = 'pid';
                    break;
                case 'participant':
                    $idColumn = 'aid';
                    break;
                case 'employee':
                    $idColumn = 'gid';
                    break;
                default:
                    throw new \RuntimeException('Unknown table occurred: ' . $table);
            }

            $filloutQuery = $this->connection->executeQuery(
                'SELECT ' . $table . '.' . $idColumn . ',
                        ' . $table . '.custom_field_values
               FROM ' . $table . '
               WHERE custom_field_values IS NOT NULL',
                []
            );

            while ($row = $filloutQuery->fetchAssociative()) {
                if ($row['custom_field_values'] === null) {
                    continue;
                }
                $customFieldValues  = json_decode($row['custom_field_values'], true);
                $commentInsertQuery = 'INSERT INTO ' . $table . '_comment SET content = ?, ' . $idColumn .
                                      ' = ?, created_at = NOW()';
                if (json_last_error()) {
                    $comment = 'JSON ERROR while decoding custom field value: ' . json_last_error_msg() .
                               $row['custom_field_values'];
                    $this->connection->executeStatement(
                        $commentInsertQuery,
                        [$comment, $row[$idColumn]]
                    );
                    continue;
                }
                foreach ($customFieldValues as $bid => $customFieldValueOld) {
                    $customFieldValueNew = null;

                    switch ($customFieldValueOld['type']) {
                        case 'bank_account';
                            if (isset($customFieldValueOld['value']['bic'])) {
                                $customFieldValueNew = [
                                    'bankAccountBic'   => $customFieldValueOld['value']['bic'],
                                    'bankAccountIban'  => $customFieldValueOld['value']['iban'],
                                    'bankAccountOwner' => $customFieldValueOld['value']['owner'],
                                ];
                            }
                            break;
                        case 'group':
                        case 'number':
                            if (is_numeric($customFieldValueOld['value'])) {
                                $customFieldValueNew = (int)$customFieldValueOld['value'];
                            }
                            break;
                        case 'choice';
                        case 'participant_detecting':
                        case 'text';
                        case 'date';
                            if (!empty($customFieldValueOld['value'])) {
                                $customFieldValueNew = $customFieldValueOld['value'];
                            }
                            break;
                        default:
                            $this->connection->executeStatement(
                                $commentInsertQuery,
                                ['Unknown custom field type occurred: ' . json_encode($customFieldValueOld),
                                 $row[$idColumn],
                                ]
                            );
                    } //switch type

                    if ($customFieldValueNew) {
                        $this->connection->executeStatement(
                            'INSERT INTO acquisition_attribute_fillout (bid, value, ' . $idColumn .
                            ') VALUES (?, ?, ?)',
                            [$bid, json_encode($customFieldValueNew), $row[$idColumn]]
                        );
                    }
                    if ($customFieldValueOld['comment']) {
                        $this->connection->executeStatement(
                            $commentInsertQuery,
                            ['Custom Field ' . $bid . ':' . $customFieldValueOld['comment'], $row[$idColumn]]
                        );
                    }
                }
            }
            $this->connection->executeStatement(
                'UPDATE ' . $table . ' SET custom_field_values = NULL',
                []
            );
        } // foreach table

        $this->connection->commit();
    }
}
