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

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\PrototypeNodeInterface;

class Configuration extends ParticipantRelatedConfiguration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'export';

    const OPTION_DEFAULT = '___default';

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
                    ->defaultValue('Teilnehmende')
                ->end()
                ->arrayNode('filter')
                    ->addDefaultsIfNotSet()
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
                ->end()
                ->append($this->participantNodeCreator($participantNodes))
                ->arrayNode('participation')
                    ->addDefaultsIfNotSet()
                    ->info('Daten der Anmeldung')
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
                                'participation', 'Anmeldungen aller enthaltener Teilnehmenden'
                            )
                        )
                        ->append(
                            Configuration::booleanNodeCreator(
                                'subvention_request', 'Liste der Teilnehmer:innen für Zuschussantrag (Geburtsdatum und Anschrift)'
                            )
                        )
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
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

}
