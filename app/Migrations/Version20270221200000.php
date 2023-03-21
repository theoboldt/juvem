<?php

declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\BitMask\ParticipantFood;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert participant food field to system custom food field
 */
final class Version20270221200000 extends AbstractMigration
{

    const TYPE_FOOD_VEGAN        = 1;
    const TYPE_FOOD_VEGETARIAN   = 2;
    const TYPE_FOOD_NO_PORK      = 4;
    const TYPE_FOOD_LACTOSE_FREE = 8;

    public function getDescription(): string
    {
        return 'Convert participant food field to system custom food field';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $this->connection->beginTransaction();

        //old fields
        $oldTextFoodFieldQuery = $this->connection->executeQuery(
            'SELECT bid           
               FROM acquisition_attribute
              WHERE management_title = ?
                AND form_title = ?',
            ['Ernährung', 'Ernährung Besonderheiten']
        );
        $oldTextFoodFieldBid   = $oldTextFoodFieldQuery->fetchOne();
        if ($oldTextFoodFieldBid) {
            $this->connection->executeQuery(
                "UPDATE acquisition_attribute 
                    SET deleted_at = NOW(), 
                        management_title = CONCAT(management_title, ' (alt)'),
                        form_title = CONCAT(form_title, ' (alt)') 
                  WHERE bid = ?",
                [$oldTextFoodFieldBid]
            );
        }
        $oldChoiceFoodFieldQuery = $this->connection->executeQuery(
            'SELECT bid           
               FROM acquisition_attribute
              WHERE management_title = ?
                AND management_description = ?',
            ['Ernährung', 'Ergänzende Abfrage zur Ernährung']
        );
        $oldChoiceFoodFieldBid   = $oldChoiceFoodFieldQuery->fetchOne();

        if ($oldChoiceFoodFieldBid) {
            $this->connection->executeQuery(
                "UPDATE acquisition_attribute 
                    SET deleted_at = NOW(), 
                        management_title = CONCAT(management_title, ' (alt)')
                  WHERE bid = ?",
                [$oldChoiceFoodFieldBid]
            );
            $oldChoiceVeganFoodFieldQuery = $this->connection->executeQuery(
                'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE bid = ?
                AND management_title = ?',
                [$oldChoiceFoodFieldBid, 'vegan']
            );
            $oldChoiceVeganFieldId        = $oldChoiceVeganFoodFieldQuery->fetchOne();
        } else {
            $oldChoiceVeganFieldId = null;
        }


        $this->connection->executeQuery(
            "INSERT INTO acquisition_attribute (management_title, management_description, form_title, form_description, field_type, field_options, use_at_participation, use_at_participant, is_required, created_at, modified_at, deleted_at, is_public, use_at_employee, is_price_formula_enabled, price_formula, is_archived, is_comment_enabled, is_system)
VALUES
	('Ernährung', 'Angaben zur Ernährung wie vegetarisch, laktosefrei...', 'Ernährung', 'Bitte geben Sie hier an, falls bei der Ernährung dieses Kindes etwas besonders zu beachten ist. Falls weitere Allergien oder Unverträglichkeiten vorhanden sind, geben Sie dies bitte als Ergänzung zu diesem Feld an.', 'Symfony\\\Component\\\Form\\\Extension\\\Core\\\Type\\\ChoiceType', '[]', 0, 1, 0, '2000-01-01 10:00:00', '2000-01-01 10:00:00', NULL, 1, 0, 0, NULL, 0, 1, 1)",
            []
        );
        $bid = (int)$this->connection->lastInsertId();

        $this->connection->insert(
            'acquisition_attribute_choice_option',
            [
                'bid'              => $bid,
                'form_title'       => 'vegetarisch',
                'management_title' => 'vegetarisch',
                'short_title'      => 'vg',
                'deleted_at'       => null,
                'form_description' => '',
                'is_archived'      => 0,
                'is_system'        => 1,
            ]
        );
        $choiceOptionVegetarianId = (int)$this->connection->lastInsertId();

        if ($oldChoiceFoodFieldBid) {
            $this->connection->insert(
                'acquisition_attribute_choice_option',
                [
                    'bid'              => $bid,
                    'form_title'       => 'vegan',
                    'management_title' => 'vegan',
                    'short_title'      => 'vn',
                    'deleted_at'       => null,
                    'form_description' => 'Bitte beachten Sie, dass dieser Wunsch abhängig von der Veranstaltung nicht immer erfüllt werden kann.',
                    'is_archived'      => 0,
                    'is_system'        => 1,
                ]
            );
            $choiceOptionVeganId = (int)$this->connection->lastInsertId();
        } else {
            $choiceOptionVeganId = null;
        }

        $this->connection->insert(
            'acquisition_attribute_choice_option',
            [
                'bid'              => $bid,
                'form_title'       => 'laktosefrei',
                'management_title' => 'laktosefrei',
                'short_title'      => 'lf',
                'deleted_at'       => null,
                'form_description' => 'Bitte geben Sie im Ergänzungsfeld detaillierte Informationen zur Ausprägung der Unverträglichkeit an. Müssen konsequent laktosefreie Produkte verwendet werden (bspw. auch bei Schokoriegeln) oder ist es ausreichend auf laktosearme Produkte zu achten? Verwendet die angemeldete Person Laktase-Tabletten?',
                'is_archived'      => 0,
                'is_system'        => 1,
            ]
        );
        $choiceOptionLactoseFreeId = (int)$this->connection->lastInsertId();

        $this->connection->insert(
            'acquisition_attribute_choice_option',
            [
                'bid'              => $bid,
                'form_title'       => 'ohne Schweinefleisch',
                'management_title' => 'ohne Schwein',
                'short_title'      => 'oS',
                'deleted_at'       => null,
                'form_description' => '',
                'is_archived'      => 0,
                'is_system'        => 1,
            ]
        );
        $choiceOptionNoPorkId = (int)$this->connection->lastInsertId();

        $this->connection->insert(
            'acquisition_attribute_choice_option',
            [
                'bid'              => $bid,
                'form_title'       => 'glutenfrei',
                'management_title' => 'glutenfrei',
                'short_title'      => 'gf',
                'deleted_at'       => null,
                'form_description' => 'Bitte geben Sie im Ergänzungsfeld detaillierte Informationen zur Ausprägung der Unverträglichkeit an. Müssen auch Spuren von Gluten vermieden werden?',
                'is_archived'      => 0,
                'is_system'        => 1,
            ]
        );
        $choiceOptionGlutenFreeId = (int)$this->connection->lastInsertId();

        $choiceOptionsQuery = $this->connection->executeQuery(
            'SELECT form_title,
                    id           
               FROM acquisition_attribute_choice_option
               WHERE bid = ?',
            [$bid]
        );
        $choiceOptions      = $choiceOptionsQuery->fetchAllKeyValue();

        if (($choiceOptionVeganId === null && count($choiceOptions) !== 4)
            || ($choiceOptionVeganId !== null && count($choiceOptions) !== 5)
        ) {
            throw new \RuntimeException('Not expecting ' . count($choiceOptions) . ' options for food');
        }

        $fieldOptions = [
            'label'       => 'Ernährung',
            'mapped'      => true,
            'choices'     => $choiceOptions,
            'expanded'    => true,
            'multiple'    => true,
            'required'    => true,
            'placeholder' => 'keine Option gewählt',
        ];

        $this->connection->executeQuery(
            "UPDATE acquisition_attribute SET field_options = ? WHERE bid = ?",
            [
                json_encode($fieldOptions),
                $bid,
            ]
        );

        // assign field to all events
        $eventIdsQuery = $this->connection->executeQuery(
            'SELECT eid          
               FROM event',
            []
        );
        $eids          = $eventIdsQuery->fetchFirstColumn();
        foreach ($eids as $eid) {
            $this->connection->insert(
                'event_acquisition_attribute',
                [
                    'bid' => $bid,
                    'eid' => $eid,
                ]
            );
        }

        $participantsQuery = $this->connection->executeQuery(
            'SELECT aid, custom_field_values, food          
               FROM participant',
            []
        );

        while ($row = $participantsQuery->fetch()) {
            $rowFood              = new ParticipantFood($row['food']);
            $rowFoodIds           = [];
            $rowFoodComment       = null;
            $rowCustomFieldValues = $row['custom_field_values'] === null
                ? []
                : json_decode(
                    $row['custom_field_values'], true
                );

            if ($rowFood->has(self::TYPE_FOOD_VEGETARIAN)) {
                $rowFoodIds[] = $choiceOptionVegetarianId;
            }
            if ($rowFood->has(self::TYPE_FOOD_LACTOSE_FREE)) {
                $rowFoodIds[] = $choiceOptionLactoseFreeId;
            }
            if ($rowFood->has(self::TYPE_FOOD_NO_PORK)) {
                $rowFoodIds[] = $choiceOptionNoPorkId;
            }
            if ($rowFood->has(self::TYPE_FOOD_VEGAN)) {
                $rowFoodComment = 'vegan';
            }

            if ($oldTextFoodFieldBid
                && isset($rowCustomFieldValues[$oldTextFoodFieldBid])
                && isset($rowCustomFieldValues[$oldTextFoodFieldBid]['value'])
                && !empty($rowCustomFieldValues[$oldTextFoodFieldBid]['value'])
            ) {
                $oldFoodComment = $rowCustomFieldValues[$oldTextFoodFieldBid]['value'];

                if (mb_strtolower($oldFoodComment) !== 'keine'
                    && mb_strtolower($oldFoodComment) !== 'nein'
                    && mb_strtolower($oldFoodComment) !== 'keine bekannt'
                    && mb_strtolower($oldFoodComment) !== 'keine vorhanden'
                    && mb_strtolower($oldFoodComment) !== 'nichts bekannt'
                    && mb_strtolower($oldFoodComment) !== 'nicht bekannt'
                    && mb_strtolower($oldFoodComment) !== 'keine besonderheiten'
                    && mb_strtolower($oldFoodComment) !== 'nix'
                    && !(preg_match('/^(-)+$/i', $oldFoodComment) !== 0)
                ) {
                    if ($rowFoodComment === null) {
                        $rowFoodComment = $oldFoodComment;
                    } else {
                        $rowFoodComment = '; ' . $oldFoodComment;
                    }
                }
            }

            if ($oldChoiceVeganFieldId
                && isset($rowCustomFieldValues[$oldChoiceFoodFieldBid]['value'])
                && in_array($oldChoiceVeganFieldId, $rowCustomFieldValues[$oldChoiceFoodFieldBid]['value'])
            ) {
                $rowFoodIds[] = $choiceOptionVeganId;
            }

            if (isset($rowCustomFieldValues[$bid])) {
                throw new \RuntimeException(
                    'There are already custom field values present for participant ' . $row['aid'] . ' field ' . $bid
                );
            }
            $rowCustomFieldValues[$bid] = [
                'bid'     => $bid,
                'type'    => 'choice',
                'value'   => $rowFoodIds,
                'comment' => $rowFoodComment,
            ];
            $this->connection->update(
                'participant',
                [
                    'custom_field_values' => json_encode($rowCustomFieldValues),
                ],
                [
                    'aid' => $row['aid'],
                ]
            );
        }
        $this->connection->commit();

        //drop food column
        $this->connection->executeQuery("ALTER TABLE participant DROP food");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');

        $this->connection->executeQuery("ALTER TABLE participant ADD food smallint(5) unsigned NOT NULL");

        try {
            $this->connection->beginTransaction();

            //old fields
            $oldTextFoodFieldQuery = $this->connection->executeQuery(
                'SELECT bid           
               FROM acquisition_attribute
              WHERE management_title = ?
                AND form_title = ?',
                ['Ernährung (alt)', 'Ernährung Besonderheiten (alt)']
            );
            $oldTextFoodFieldBid   = $oldTextFoodFieldQuery->fetchOne();
            if ($oldTextFoodFieldBid) {
                $this->connection->executeQuery(
                    'UPDATE acquisition_attribute 
                        SET deleted_at = NULL,
                            management_title = \'Ernährung\',
                            form_title = \'Ernährung Besonderheiten\'
                      WHERE bid = ?',
                    [$oldTextFoodFieldBid]
                );
            }
            $oldChoiceFoodFieldQuery = $this->connection->executeQuery(
                'SELECT bid           
               FROM acquisition_attribute
              WHERE management_title = ?
                AND management_description = ?',
                ['Ernährung (alt)', 'Ergänzende Abfrage zur Ernährung (alt)']
            );
            $oldChoiceFoodFieldBid   = $oldChoiceFoodFieldQuery->fetchOne();

            if ($oldChoiceFoodFieldBid) {
                $this->connection->executeQuery(
                    'UPDATE acquisition_attribute 
                        SET deleted_at = NULL,
                            management_title = \'Ernährung\'
                      WHERE bid = ?',
                    [$oldTextFoodFieldBid]
                );
                $oldChoiceVeganFoodFieldQuery = $this->connection->executeQuery(
                    'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE bid = ?
                AND management_title = ?',
                    [$oldChoiceFoodFieldBid, 'vegan']
                );
                $oldChoiceVeganFieldId        = $oldChoiceVeganFoodFieldQuery->fetchOne();
            } else {
                $oldChoiceVeganFieldId = null;
            }


            $newFoodFieldQuery = $this->connection->executeQuery(
                'SELECT bid           
               FROM acquisition_attribute
              WHERE management_title = ?
                AND is_system = ?',
                ['Ernährung', 1]
            );
            $newFoodFieldBid   = $newFoodFieldQuery->fetchOne();
            if (!$newFoodFieldBid) {
                throw new \RuntimeException('Unable to identify system food custom field, musst be called "Ernährung"');
            }
            $choiceOptionVegetarianQuery = $this->connection->executeQuery(
                'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE form_title = ?
                AND bid = ?
                AND is_system = ?',
                ['vegetarisch', $newFoodFieldBid, 1]
            );
            $choiceOptionVegetarianId    = $choiceOptionVegetarianQuery->fetchOne();
            if (!$choiceOptionVegetarianId) {
                throw new \RuntimeException('Unable to identify vegetarian option id');
            }
            
            $choiceOptionVeganQuery = $this->connection->executeQuery(
                'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE form_title = ?
                AND bid = ?
                AND is_system = ?',
                ['vegan', $newFoodFieldBid, 1]
            );
            $choiceOptionVeganId    = $choiceOptionVeganQuery->fetchOne();

            $choiceOptionLactoseFreeQuery = $this->connection->executeQuery(
                'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE form_title = ?
                AND bid = ?
                AND is_system = ?',
                ['laktosefrei', $newFoodFieldBid, 1]
            );
            $choiceOptionLactoseFreeId    = $choiceOptionLactoseFreeQuery->fetchOne();
            if (!$choiceOptionLactoseFreeId) {
                throw new \RuntimeException('Unable to identify lactose free option id');
            }

            $choiceOptionNoPorkQuery = $this->connection->executeQuery(
                'SELECT id           
               FROM acquisition_attribute_choice_option
              WHERE form_title = ?
                AND bid = ?
                AND is_system = ?',
                ['ohne Schweinefleisch', $newFoodFieldBid, 1]
            );
            $choiceOptionNoPorkId    = $choiceOptionNoPorkQuery->fetchOne();
            if (!$choiceOptionNoPorkId) {
                throw new \RuntimeException('Unable to no pork option id');
            }

            $participantsQuery = $this->connection->executeQuery(
                'SELECT aid, custom_field_values  , food        
               FROM participant',
                []
            );

            while ($row = $participantsQuery->fetch()) {
                $rowCustomFieldValues = $row['custom_field_values'] === null
                    ? []
                    : json_decode(
                        $row['custom_field_values'], true
                    );

                $rowFoodMask = 0;
                if (isset($rowCustomFieldValues[$newFoodFieldBid]['value'])
                    && is_array($rowCustomFieldValues[$newFoodFieldBid]['value'])
                ) {
                    if (in_array($choiceOptionVegetarianId, $rowCustomFieldValues[$newFoodFieldBid]['value'])
                    ) {
                        $rowFoodMask += self::TYPE_FOOD_VEGETARIAN;
                    }
                    if (in_array($choiceOptionLactoseFreeId, $rowCustomFieldValues[$newFoodFieldBid]['value'])
                    ) {
                        $rowFoodMask += self::TYPE_FOOD_LACTOSE_FREE;
                    }
                    if (in_array($choiceOptionNoPorkId, $rowCustomFieldValues[$newFoodFieldBid]['value'])
                    ) {
                        $rowFoodMask += self::TYPE_FOOD_NO_PORK;
                    }

                    $rowUpdates = [
                        'food' => $rowFoodMask,
                    ];
                    if ($oldTextFoodFieldBid
                        && isset($rowCustomFieldValues[$newFoodFieldBid]['comment'])
                        && !empty($rowCustomFieldValues[$newFoodFieldBid]['comment'])
                    ) {
                        if (!empty($rowCustomFieldValues[$oldTextFoodFieldBid]['value'])
                            && $rowCustomFieldValues[$oldTextFoodFieldBid]['value'] !==
                               $rowCustomFieldValues[$newFoodFieldBid]['comment']
                        ) {
                            $rowCustomFieldValues[$oldTextFoodFieldBid]['comment']
                                = 'Backwards migration. Original: ' .
                                  $rowCustomFieldValues[$oldTextFoodFieldBid]['value'];
                        }
                        $rowCustomFieldValues[$oldTextFoodFieldBid]['value']
                            = $rowCustomFieldValues[$newFoodFieldBid]['comment'];

                        $rowUpdates['custom_field_values'] = json_encode($rowCustomFieldValues);
                    }
                    
                    $this->connection->update(
                        'participant',
                        $rowUpdates,
                        [
                            'aid' => $row['aid'],
                        ]
                    );
                }
            } //while

            $this->connection->delete(
                'acquisition_attribute',
                [
                    'bid' => $newFoodFieldBid,
                ]
            );
        } catch (\Exception $e) {
            $this->connection->executeQuery("ALTER TABLE participant DROP food");
            throw $e;
        }

        $this->connection->commit();
    }
}
