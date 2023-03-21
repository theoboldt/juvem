<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Export\Customized;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\Event;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

abstract class ParticipantRelatedConfiguration
{
    const OPTION_SEPARATE_COLUMNS = 'separateColumns';
    
    const OPTION_SEPARATE_COLUMNS_SHORT = 'separateColumnsShort';

    const OPTION_VALUE_FORM       = 'formTitle';
    const OPTION_VALUE_MANAGEMENT = 'managementTitle';
    const OPTION_VALUE_SHORT      = 'shortTitle';

    const OPTION_COMMENT_NONE = 'commentNotUsed';
    const OPTION_COMMENT_NEWLINE = 'commentNewline';
    const OPTION_COMMENT_COLUMN  = 'commentColumn';
    const OPTION_COMMENT_COMMENT = 'commentExcelComment';

    const OPTION_GROUP_NONE = '___group_by_none';
    const OPTION_SORT_NONE = '___sort_by_none';

    const OPTION_CONFIRMED_ALL         = 'all';
    const OPTION_CONFIRMED_CONFIRMED   = 'confirmed';
    const OPTION_CONFIRMED_UNCONFIRMED = 'unconfirmed';

    const OPTION_PAID_ALL     = 'all';
    const OPTION_PAID_PAID    = 'paid';
    const OPTION_PAID_NOTPAID = 'notpaid';

    const OPTION_REJECTED_WITHDRAWN_ALL                    = 'all';
    const OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN = 'notrejectedwithdrawn';
    const OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN     = 'rejectedwithdrawn';
    
    /**
     * Event this export is configurated for
     *
     * @var Event|null
     */
    protected $event;

    /**
     * Configuration constructor.
     *
     * @param Event|null $event
     */
    public function __construct(Event $event = null)
    {
        $this->event = $event;
    }

    /**
     * @param string $name        Node identifier
     * @param string $info        Text used as info
     * @param bool   $defaultTrue If set to true, default value will be enabled
     * @return BooleanNodeDefinition
     */
    public static function booleanNodeCreator(string $name, string $info, bool $defaultTrue = false)
    {
        $node = new BooleanNodeDefinition($name);
        $node->beforeNormalization()
             ->ifString()
             ->then(function ($v) { return (bool)$v; })
             ->end()
             ->info($info);
        if ($defaultTrue) {
            $node->defaultTrue();
        }

        return $node;
    }


    /**
     * Create participant node definition
     *
     * @param array|NodeDefinition[] $participantNodes Sub-nodes to add
     * @return NodeDefinition
     */
    protected function participantNodeCreator(array $participantNodes): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('participant')
                           ->addDefaultsIfNotSet()
                           ->info('Daten der Teilnehmenden');

        $children = $node->children();
        foreach ($participantNodes as $childNode) {
            $children->append($childNode);
        }

        $children->append(Configuration::createGroupSortNodes($participantNodes));

        return $node;
    }

    /**
     * Create participant nodes
     *
     * @return array|NodeDefinition[]
     */
    protected function participantNodesCreator(): array
    {
        $nodes   = [];
        $nodes[] = Configuration::booleanNodeCreator('aid', 'AID (Eindeutige Teilnahme-Nummer)');
        $nodes[] = Configuration::booleanNodeCreator('nameFirst', 'Vorname', true);
        $nodes[] = Configuration::booleanNodeCreator('nameLast', 'Nachname', true);
        $nodes[] = Configuration::booleanNodeCreator('birthday', 'Geburtsdatum', true);

        $node = new EnumNodeDefinition('ageAtEvent');
        $node->info('Alter (bei Beginn der Veranstaltung)')
             ->defaultValue('none')
             ->values(
                 [
                     'Nicht exportieren'         => 'none',
                     'Vollendete Lebensjahre'    => 'completed',
                     'Auf Jahre gerundet'        => 'round',
                     'Mit einer Nachkommastelle' => 'decimalplace',
                 ]
             );
        $nodes[] = $node;

        $nodes[] = Configuration::booleanNodeCreator('gender', 'Geschlecht');
        $nodes[] = Configuration::booleanNodeCreator('infoMedical', 'Medizinische Hinweise');
        $nodes[] = Configuration::booleanNodeCreator('infoGeneral', 'Allgemeine Hinweise');
        $nodes[] = Configuration::booleanNodeCreator('basePrice', 'Grundpreis');
        $nodes[] = Configuration::booleanNodeCreator('price', 'Preis (inkl. Formeln)');
        $nodes[] = Configuration::booleanNodeCreator('toPay', 'Zu zahlen (offener Zahlungsbetrag)');
        if ($this->event) {
            $nodes[] = $this->addCustomFieldNode(false, true);
        }

        return $nodes;
    }

    /**
     * Add fields for custom fields attributes
     *
     * @param bool $participation Set to true to include participation fields
     * @param bool $participant   Set to true to include participant fields
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addCustomFieldNode(bool $participation, bool $participant)
    {
        $attributes = $this->event->getAcquisitionAttributes($participation, $participant, false, true, true);
        $builder    = new TreeBuilder();
        $node       = $builder->root('customFieldValues')
                              ->addDefaultsIfNotSet()
                              ->info('Felder');
        $children   = $node->children();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $attributeChildren = $children
                ->arrayNode('custom_field_' . $attribute->getBid())
                ->addDefaultsIfNotSet()
                ->info($attribute->getManagementTitle())
                ->children();

            $attributeChildren
                ->append(Configuration::booleanNodeCreator('enabled', 'Feld anzeigen', false));
            
            if ($attribute->isChoiceType()) {
                if ($attribute->isMultipleChoiceType()) {
                    $displayList = [
                        'Antworten kommasepariert auflisten' => 'commaSeparated',
                    ];
                } else {
                    $displayList = [
                        'Gewählte Antwort anzeigen' => 'selectedAnswer',
                    ];
                }
                $displayDefault = reset($displayList);
                
                $displayList['Antwortmöglichkeiten in Spalten aufteilen, für gewählte deren Kürzel anzeigen']
                    = self::OPTION_SEPARATE_COLUMNS_SHORT;

                $displayList['Antwortmöglichkeiten in Spalten aufteilen, gewählte mit \'x\' anzeigen']
                    = self::OPTION_SEPARATE_COLUMNS;

                $optionValueList = [
                    'Kürzel'            => self::OPTION_VALUE_SHORT,
                    'Titel im Formular' => self::OPTION_VALUE_FORM,
                    'Interner Titel'    => self::OPTION_VALUE_MANAGEMENT,
                ];

                $attributeChildren
                    ->enumNode('display')
                    ->values($displayList)
                    ->defaultValue($displayDefault)
                    ->end()
                    ->enumNode('optionValue')
                    ->values($optionValueList)
                    ->defaultValue(self::OPTION_VALUE_MANAGEMENT)
                    ->end();
            }
            if ($attribute->isCommentEnabled()) {
                $attributeChildren
                    ->enumNode('optionComment')
                    ->defaultValue(self::OPTION_COMMENT_COMMENT)
                    ->values(
                        [
                            'Ergänzungen in Wertspalte in neuer Zeile in derselben Zelle wie die Werte hinzufügen' =>
                                self::OPTION_COMMENT_NEWLINE,
                            'Ergänzungen in eigener Spalte einfügen'                                               =>
                                self::OPTION_COMMENT_COLUMN,
                            'Ergänzungen als Excel-Kommentare zur Zelle der Werte hinzufügen'                      =>
                                self::OPTION_COMMENT_COMMENT,
                            'Ergänzungen ignorieren'                                                               =>
                                self::OPTION_COMMENT_NONE,
                        ]
                    )
                    ->end();
            }
        }

        return $node;
    }
    
    /**
     * Provide participant filter options
     *
     * @return NodeDefinition
     */
    public static function creatFilterNodes(): NodeDefinition
    {
        $node = new ArrayNodeDefinition('filter');
        $node->addDefaultsIfNotSet()
             ->info('Filter')
             ->children()
             ->enumNode('confirmed')
                 ->info('Bestätigt/Unbestätigt')
                 ->values(
                     [
                         'Bestätigte und unbestätigt Teilnehmende mit aufnehmen' => self::OPTION_CONFIRMED_ALL,
                         'Nur bestätigte Teilnehmende mit aufnehmen'             => self::OPTION_CONFIRMED_CONFIRMED,
                         'Nur unbestätigte mit aufnehmen'                        => self::OPTION_CONFIRMED_UNCONFIRMED,
                     ]
                 )
                 ->end()
             ->enumNode('paid')
                 ->info('Bezahlungsstatus')
                 ->values(
                     [
                         'Teilnehmende unabhängig vom Bezahlungsstatus aufnehmen'               => self::OPTION_PAID_ALL,
                         'Nur Teilnehmende deren Rechnung bezahlt ist mit aufnehmen'            => self::OPTION_PAID_PAID,
                         'Nur Teilnehmende deren Rechnung noch nicht bezahlt ist mit aufnehmen' => self::OPTION_PAID_NOTPAID,
                     ]
                 )
                 ->end()
             ->enumNode('rejectedwithdrawn')
                 ->info('Zurückgezogen und abgelehnt')
                 ->values(
                     [
                         'Zurückgezogene/abgelehnte mit aufnehmen'                       => self::OPTION_REJECTED_WITHDRAWN_ALL,
                         'Nur Teilnehmende die weder zurückgzogen noch abgelehnt wurden' => self::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN,
                         'Nur Teilnehmende die zurückgzogen oder abgelehnt wurden'       => self::OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN,
                     ]
                 )
                 ->end()
             ->end()
        ->end();
        
        return $node;
    }

    /**
     * Grouping/sorting configuration nodes depending on participant nodes
     *
     * @param array|NodeDefinition[] $participantNodes Nodes for participant
     * @param string                 $groupInfo        Label for group field
     * @param string                 $sortingInfo      Label for sorting field
     * @return EnumNodeDefinition Result
     */
    public static function createGroupSortNodes(
        array $participantNodes,
        string $groupInfo = 'Gruppieren (Fügt einen Seitenumbruch zwischen alle verfügbaren Werte)',
        string $sortingInfo = 'Sortieren (Nachdem die Gruppierung angewandt wurde)'
    ): NodeDefinition {
        $node = new ArrayNodeDefinition('grouping_sorting');
        $node->info('Gruppierung & Sortierung');
        $values = Configuration::flattenOptions($participantNodes);

        $valuesGrouping = array_filter(
            $values, function ($v) {
            return $v !== 'aid';
        });

        //grouping
        $groupingNode = new ArrayNodeDefinition('grouping');
        $groupingNode->info($groupInfo);
        $groupingNode->append(
            Configuration::booleanNodeCreator(
                'enabled', 'Teilnehmer:innen gruppieren'
            )
        );
        $enum = new EnumNodeDefinition('field');
        $enum->values($valuesGrouping)
             ->info('Feld');
        $enum->beforeNormalization()->ifNotInArray(array_values($valuesGrouping))->thenUnset();
        $groupingNode->append($enum);
        $node->append($groupingNode);

        //sorting
        $sortingNode = new ArrayNodeDefinition('sorting');
        $sortingNode->info($sortingInfo);
        $sortingNode->append(
            Configuration::booleanNodeCreator(
                'enabled', 'Teilnehmer:innen sortieren'
            )
        );
        $enum = new EnumNodeDefinition('field');
        $enum->values($values)
             ->info('Feld');
        $sortingNode->append($enum);
        $node->append($sortingNode);

        return $node;
    }
}
