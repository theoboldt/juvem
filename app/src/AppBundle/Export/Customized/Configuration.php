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
                ->append(
                    self::creatFilterNodes()
                )
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
                            ->defaultValue('comma')
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
                        ->append($this->addCustomFieldNode(true, false))
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
