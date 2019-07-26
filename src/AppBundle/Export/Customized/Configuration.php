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
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\PrototypeNodeInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'export';

    const OPTION_DEFAULT = '___default';

    const OPTION_SEPARATE_COLUMNS = 'separateColumns';

    const OPTION_VALUE_FORM = 'formTitle';
    const OPTION_VALUE_MANAGEMENT = 'managementTitle';
    const OPTION_VALUE_SHORT = 'shortTitle';

    const OPTION_CONFIRMED_ALL         = 'all';
    const OPTION_CONFIRMED_CONFIRMED   = 'confirmed';
    const OPTION_CONFIRMED_UNCONFIRMED = 'unconfirmed';

    const OPTION_PAID_ALL     = 'all';
    const OPTION_PAID_PAID    = 'paid';
    const OPTION_PAID_NOTPAID = 'notpaid';

    const OPTION_REJECTED_WITHDRAWN_ALL                    = 'all';
    const OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN = 'notrejectedwithdrawn';
    const OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN     = 'rejectedwithdrawn';

    const OPTION_GROUP_NONE = '___group_by_none';
    const OPTION_SORT_NONE = '___sort_by_none';

    /**
     * Event this export is configurated for
     *
     * @var Event|null
     */
    private $event;

    /**
     * Configuration constructor.
     *
     * @param Event|null $event
     */
    public function __construct(Event $event = null)
    {
        $this->event = $event;
    }

    public static function booleanNodeCreator($name, $info) {
        $node = new BooleanNodeDefinition($name);
        $node->beforeNormalization()
             ->ifString()
                ->then(function ($v) { return (bool)$v; })
                ->end()
             ->info($info);
        return $node;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $participantNodes = $this->participantNodesCreator();

        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(self::ROOT_NODE_NAME);
        $rootNode
            ->children()
                ->scalarNode('title')
                    ->info('Titel')
                    ->defaultValue('Teilnehmer')
                ->end()
                ->arrayNode('filter')
                    ->addDefaultsIfNotSet()
                    ->info('Filter')
                    ->children()
                    ->enumNode('confirmed')
                        ->info('Bestätigt/Unbestätigt')
                        ->values(
                            [
                                'Bestätigte und unbestätigt Teilnehmer mit aufnehmen' => self::OPTION_CONFIRMED_ALL,
                                'Nur bestätigte Teilnehmer mit aufnehmen'             => self::OPTION_CONFIRMED_CONFIRMED,
                                'Nur unbestätigte mit aufnehmen'                      => self::OPTION_CONFIRMED_UNCONFIRMED,
                            ]
                        )
                        ->end()
                    ->enumNode('paid')
                        ->info('Bezahlungsstatus')
                        ->values(
                            [
                                'Teilnehmer unabhängig vom Bezahlungsstatus aufnehmen'               => self::OPTION_PAID_ALL,
                                'Nur Teilnehmer deren Rechnung bezahlt ist mit aufnehmen'            => self::OPTION_PAID_PAID,
                                'Nur Teilnehmer deren Rechnung noch nicht bezahlt ist mit aufnehmen' => self::OPTION_PAID_NOTPAID,
                            ]
                        )
                        ->end()
                    ->enumNode('rejectedwithdrawn')
                        ->info('Zurückgezogen und abgelehnt')
                        ->values(
                            [
                                'Zurückgezogene/abgelehnte mit aufnehmen'                     => self::OPTION_REJECTED_WITHDRAWN_ALL,
                                'Nur Teilnehmer die weder zurückgzogen noch abgelehnt wurden' => self::OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN,
                                'Nur Teilnehmer die zurückgzogen oder abgelehnt wurden'       => self::OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN,
                            ]
                        )
                        ->end()
                    ->end()
                ->end()
                ->append($this->participantNodeCreator($participantNodes))
                ->arrayNode('participation')
                    ->addDefaultsIfNotSet()
                    ->info('Anmeldungsdaten')
                    ->children()
                        ->append(Configuration::booleanNodeCreator('pid', 'PID (Eindeutige Anmeldungsnummer)'))
                        ->append(Configuration::booleanNodeCreator('salutation', 'Anrede'))
                        ->append(Configuration::booleanNodeCreator('nameFirst', 'Vorname (Eltern)'))
                        ->append(Configuration::booleanNodeCreator('nameLast', 'Nachname (Eltern)'))
                        ->append(Configuration::booleanNodeCreator('email', 'E-Mail Adresse'))
                        ->enumNode('phoneNumber')
                            ->info('Telefonnummern')
                            ->values([
                                         'Nicht exportieren'                 => 'none',
                                         'Kommasepariert, ohne Beschreibung' => 'comma',
                                         'Kommasepariert, mit Beschreibung'  => 'comma_description',
                                         'Kommasepariert, ohne Beschreibung, umbrechend' => 'comma_wrap',
                                         'Kommasepariert, mit Beschreibung, umbrechend'  => 'comma_description_wrap',
                            ])
                        ->end()
                        ->append(Configuration::booleanNodeCreator('addressStreet', 'Straße (Anschrift)'))
                        ->append(Configuration::booleanNodeCreator('addressCity', 'Stadt (Anschrift)'))
                        ->append(Configuration::booleanNodeCreator('addressZip', 'PLZ (Anschrift'))
                        ->append($this->addAcquisitionAttributesNode(true, false))
                    ->end()
                ->end()
                ->arrayNode('additional_sheet')
                    ->addDefaultsIfNotSet()
                    ->info('Zusätzliche Mappen')
                    ->children()
                        ->append(
                            Configuration::booleanNodeCreator(
                                'participation', 'Anmeldungen aller enthaltener Teilnehmer'
                            )
                        )
                        ->append(
                            Configuration::booleanNodeCreator(
                                'subvention_request', 'Teilnehmerliste für Zuschussantrag (Geburtsdatum und Anschrift)'
                            )
                        )
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }


    /**
     * Add fields for acquisition attributes
     *
     * @param bool $participation Set to true to include participation fields
     * @param bool $participant   Set to true to include participant fields
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|NodeDefinition
     */
    private function addAcquisitionAttributesNode($participation, $participant)
    {
        $attributes = $this->event->getAcquisitionAttributes($participation, $participant, false, true, true);
        $builder    = new TreeBuilder();
        $node       = $builder->root('acquisitionFields')
                              ->addDefaultsIfNotSet()
                              ->info('Felder');
        $children = $node->children();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $attributeChildren = $children
                ->arrayNode('acq_field_' . $attribute->getBid())
                    ->addDefaultsIfNotSet()
                    ->info($attribute->getManagementTitle())
                    ->children();

            if ($attribute->isChoiceType()) {
                if ($attribute->isMultipleChoiceType()) {
                    $displayList = [
                        'Antworten kommasepariert auflisten' => 'commaSeparated'
                    ];
                } else {
                    $displayList = [
                        'Gewählte Antwort anzeigen' => 'selectedAnswer'
                    ];
                }
                $displayList['Antwortmöglichkeiten in Spalten aufteilen, gewählte ankreuzen'] = self::OPTION_SEPARATE_COLUMNS;

                $optionValueList = [
                    'Kürzel'            => self::OPTION_VALUE_SHORT,
                    'Titel im Formular' => self::OPTION_VALUE_FORM,
                    'Interner Titel'    => self::OPTION_VALUE_MANAGEMENT,
                ];

                $attributeChildren
                        ->append(Configuration::booleanNodeCreator('enabled', 'Feld anzeigen'))
                        ->enumNode('display')
                            ->values($displayList)
                        ->end()
                        ->enumNode('optionValue')
                            ->values($optionValueList)
                        ->end()
                    ->end()
                ->end();
            } else {
                $attributeChildren
                        ->append(Configuration::booleanNodeCreator('enabled', 'Feld anzeigen'))
                    ->end()
                ->end();
            }
        }

        return $node;
    }

    /**
     * Create participant node definition
     *
     * @param array|NodeDefinition[] $participantNodes Sub-nodes to add
     * @return NodeDefinition
     */
    private function participantNodeCreator(array $participantNodes): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('participant')
                           ->addDefaultsIfNotSet()
                           ->info('Teilnehmerdaten');

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
    private function participantNodesCreator(): array
    {
        $nodes   = [];
        $nodes[] = Configuration::booleanNodeCreator('aid', 'AID (Eindeutige Teilnehmernummer)');
        $nodes[] = Configuration::booleanNodeCreator('nameFirst', 'Vorname');
        $nodes[] = Configuration::booleanNodeCreator('nameLast', 'Nachname');
        $nodes[] = Configuration::booleanNodeCreator('birthday', 'Geburtsdatum');

        $node = new EnumNodeDefinition('ageAtEvent');
        $node->info('Alter (bei Beginn der Veranstaltung)')
             ->values(
                 [
                     'Nicht exportieren'         => 'none',
                     'Vollendete Lebensjahre'    => 'completed',
                     'Auf Jahre gerundet'        => 'round',
                     'Mit einer Nachkommastelle' => 'decimalplace'
                 ]
             );
        $nodes[] = $node;

        $nodes[] = Configuration::booleanNodeCreator('gender', 'Geschlecht');
        $nodes[] = Configuration::booleanNodeCreator('foodVegetarian', 'Vegetarisch (Essgewohnheiten)');
        $nodes[] = Configuration::booleanNodeCreator('foodLactoseFree', 'Laktosefrei (Essgewohnheiten)');
        $nodes[] = Configuration::booleanNodeCreator('foodLactoseNoPork', 'Ohne Schwein (Essgewohnheiten)');
        $nodes[] = Configuration::booleanNodeCreator('infoMedical', 'Medizinische Hinweise');
        $nodes[] = Configuration::booleanNodeCreator('infoGeneral', 'Allgemeine Hinweise');
        $nodes[] = Configuration::booleanNodeCreator('basePrice', 'Grundpreis');
        $nodes[] = Configuration::booleanNodeCreator('price', 'Preis (inkl. Formeln)');
        $nodes[] = Configuration::booleanNodeCreator('toPay', 'Zu zahlen (offener Zahlungsbetrag)');
        $nodes[] = $this->addAcquisitionAttributesNode(false, true);

        return $nodes;
    }

    /**
     * Get flattened nodes
     *
     * @param array $nodes
     * @return array
     */
    public static function flattenOptions(array $nodes): array
    {
        $result = [];
        $unsupported = [
            'infoMedical',
            'infoGeneral',
            'price',
            'toPay',
        ];

        /** @var NodeDefinition|PrototypeNodeInterface[] $participantNodeDefinition */
        foreach ($nodes as $participantNodeDefinition) {
            if ($participantNodeDefinition instanceof ArrayNode) {
                $participantNode = $participantNodeDefinition;
            } else {
                $participantNode = $participantNodeDefinition->getNode(true);
            }
            if ($participantNode !== $participantNodeDefinition && $participantNode instanceof ArrayNode) {
                $result = array_merge($result, Configuration::flattenOptions($participantNode->getChildren()));
            } else {
                $label = $participantNode->getInfo();
                $name  = $participantNode->getName();
                if (in_array($name, $unsupported)) {
                    continue;
                }
                $result[$label] = $name;
            }
        }
        return $result;
    }

    /**
     * Grouping/sorting configuration nodes depending on participant nodes
     *
     * @param array|NodeDefinition[] $participantNodes Nodes for participant
     * @return EnumNodeDefinition Result
     */
    public static function createGroupSortNodes(array $participantNodes): NodeDefinition
    {
        $node = new ArrayNodeDefinition('grouping_sorting');
        $node->info('Gruppierung & Sortierung');
        $values = Configuration::flattenOptions($participantNodes);

        //grouping
        $groupingNode = new ArrayNodeDefinition('grouping');
        $groupingNode->info('Gruppieren (Fügt einen Seitenumbruch zwischen alle verfügbaren Werte)');
        $groupingNode->append(
            Configuration::booleanNodeCreator(
                'enabled', 'Teilnehmer gruppieren'
            )
        );
        $enum = new EnumNodeDefinition('field');
        $enum->values($values)
             ->info('Feld');
        $enum->beforeNormalization()->ifNotInArray(array_values($values))->thenUnset();
        $groupingNode->append($enum);
        $node->append($groupingNode);

        //sorting
        $sortingNode = new ArrayNodeDefinition('sorting');
        $sortingNode->info('Sortieren (Nachdem die Gruppierung angewandt wurde)');
        $sortingNode->append(
            Configuration::booleanNodeCreator(
                'enabled', 'Teilnehmer sortieren'
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
