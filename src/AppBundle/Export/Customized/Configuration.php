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
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Configuration implements ConfigurationInterface
{

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

    protected function booleanNodeCreator($name, $info) {
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
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('export');
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

                ->arrayNode('participant')
                    ->addDefaultsIfNotSet()
                    ->info('Teilnehmerdaten')
                    ->children()
                        ->append($this->booleanNodeCreator('aid', 'AID (Eindeutige Teilnehmernummer)'))
                        ->append($this->booleanNodeCreator('nameFirst', 'Vorname'))
                        ->append($this->booleanNodeCreator('nameLast', 'Nachname'))
                        ->append($this->booleanNodeCreator('birthday', 'Geburtsdatum'))
                        ->enumNode('ageAtEvent')
                            ->info('Alter (bei Beginn der Veranstaltung)')
                            ->values([
                                         'Nicht exportieren'         => 'none',
                                         'Vollendete Lebensjahre'    => 'completed',
                                         'Auf Jahre gerundet'        => 'round',
                                         'Mit einer Nachkommastelle' => 'decimalplace'
                            ])
                        ->end()
                        ->append($this->booleanNodeCreator('gender', 'Geschlecht'))
                        ->append($this->booleanNodeCreator('foodVegetarian', 'Vegetarisch (Essgewohnheiten)'))
                        ->append($this->booleanNodeCreator('foodLactoseFree', 'Laktosefrei (Essgewohnheiten)'))
                        ->append($this->booleanNodeCreator('foodLactoseNoPork', 'Ohne Schwein (Essgewohnheiten)'))
                        ->append($this->booleanNodeCreator('infoMedical', 'Medizinische Hinweise'))
                        ->append($this->booleanNodeCreator('infoGeneral', 'Allgemeine Hinweise'))
                        ->append($this->booleanNodeCreator('basePrice', 'Grundpreis'))
                        ->append($this->booleanNodeCreator('price', 'Preis (inkl. Formeln)'))
                        ->append($this->booleanNodeCreator('toPay', 'Zu zahlen (offener Zahlungsbetrag)'))
                        ->append($this->addAcquisitionAttributesNode(false, true))
                        //food
                    ->end()
                ->end()
                ->arrayNode('participation')
                    ->addDefaultsIfNotSet()
                    ->info('Anmeldungsdaten')
                    ->children()
                        ->append($this->booleanNodeCreator('pid', 'PID (Eindeutige Anmeldungsnummer)'))
                        ->append($this->booleanNodeCreator('salutation', 'Anrede'))
                        ->append($this->booleanNodeCreator('nameFirst', 'Vorname (Eltern)'))
                        ->append($this->booleanNodeCreator('nameLast', 'Nachname (Eltern)'))
                        ->append($this->booleanNodeCreator('email', 'E-Mail Adresse'))
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
                        ->append($this->booleanNodeCreator('addressStreet', 'Straße (Anschrift)'))
                        ->append($this->booleanNodeCreator('addressCity', 'Stadt (Anschrift)'))
                        ->append($this->booleanNodeCreator('addressZip', 'PLZ (Anschrift'))
                        ->append($this->addAcquisitionAttributesNode(true, false))
                    ->end()
                ->end()
                ->arrayNode('additional_sheet')
                    ->addDefaultsIfNotSet()
                    ->info('Zusätzliche Mappen')
                    ->children()
                        ->append($this->booleanNodeCreator('participation', 'Anmeldungen aller enthaltener Teilnehmer'))
                        ->append($this->booleanNodeCreator('subvention_request', 'Teilnehmerliste für Zuschussantrag (Geburtsdatum und Anschrift)'))
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
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    public function addAcquisitionAttributesNode($participation, $participant)
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
                        ->append($this->booleanNodeCreator('enabled', 'Feld anzeigen'))
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
                        ->append($this->booleanNodeCreator('enabled', 'Feld anzeigen'))
                    ->end()
                ->end();
            }
        }

        return $node;
    }
}
