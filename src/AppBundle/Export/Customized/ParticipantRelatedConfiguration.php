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

    const OPTION_VALUE_FORM       = 'formTitle';
    const OPTION_VALUE_MANAGEMENT = 'managementTitle';
    const OPTION_VALUE_SHORT      = 'shortTitle';

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

    public static function booleanNodeCreator($name, $info)
    {
        $node = new BooleanNodeDefinition($name);
        $node->beforeNormalization()
             ->ifString()
             ->then(function ($v) { return (bool)$v; })
             ->end()
             ->info($info);
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
    protected function participantNodesCreator(): array
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
                     'Mit einer Nachkommastelle' => 'decimalplace',
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
     * Add fields for acquisition attributes
     *
     * @param bool $participation Set to true to include participation fields
     * @param bool $participant   Set to true to include participant fields
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addAcquisitionAttributesNode($participation, $participant)
    {
        $attributes = $this->event->getAcquisitionAttributes($participation, $participant, false, true, true);
        $builder    = new TreeBuilder();
        $node       = $builder->root('acquisitionFields')
                              ->addDefaultsIfNotSet()
                              ->info('Felder');
        $children   = $node->children();

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
                        'Antworten kommasepariert auflisten' => 'commaSeparated',
                    ];
                } else {
                    $displayList = [
                        'Gewählte Antwort anzeigen' => 'selectedAnswer',
                    ];
                }
                $displayList['Antwortmöglichkeiten in Spalten aufteilen, gewählte ankreuzen']
                    = self::OPTION_SEPARATE_COLUMNS;

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
