<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\ParticipantProfile;

use AppBundle\Export\Customized\ParticipantRelatedConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration extends ParticipantRelatedConfiguration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'profile';
    
    const LAYOUT_SMALL = 'layout_small';
    const LAYOUT_LARGE = 'layout_large';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $participantNodes = $this->participantNodesCreator();

        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(self::ROOT_NODE_NAME);
        $rootNode->children()
                    ->scalarNode('title')
                        ->info('Titel')
                        ->defaultValue('Profile')
                    ->end()
                    ->append(
                        self::creatFilterNodes()
                    )
                    ->append(
                        self::createGroupSortNodes(
                            $participantNodes, 'Gruppieren (erscheint in der Kopfzeile, zunächst sortiert nach diesem Feld)'
                        )
                    )
                    ->arrayNode('general')
                        ->addDefaultsIfNotSet()
                        ->info('Allgemein')
                        ->children()
                            ->enumNode('layout')
                                ->info('Layout')
                                ->values(
                                    [
                                        'Normale Rand, kleinere Schrift, kompakter'      => self::LAYOUT_SMALL,
                                        'Kein Rand, größere Schrift, eher seitenfüllend' => self::LAYOUT_LARGE,
                                    ]
                                )
                            ->end()
                            ->append(self::booleanNodeCreator('includePrivate', 'Interne Felder mit ausgeben'))
                            ->append(self::booleanNodeCreator('includeDescription', 'Internen Erklärungstext der Felder mit ausgeben (wenn vorhanden)'))
                            ->append(self::booleanNodeCreator('includeComments', 'Anmerkungen mit ausgeben'))
                            ->append(self::booleanNodeCreator('includePrice', 'Preis mit ausgeben'))
                            ->append(self::booleanNodeCreator('includeToPay', 'Offener Zahlungsbetrag mit ausgeben'))
                        ->end()
                    ->end()
                    ->arrayNode('choices')
                        ->addDefaultsIfNotSet()
                        ->info('Auswahlfelder')
                        ->children()
                            ->append(self::booleanNodeCreator('includeShortTitle', 'Kürzel mit ausgeben (wenn vorhanden)'))
                            ->append(self::booleanNodeCreator('includeManagementTitle', 'Internen Titel mit ausgeben'))
                            ->append(self::booleanNodeCreator('includeNotSelected', 'Nicht zutreffende Optionen mit ausgeben (und als nicht zutreffend markieren)'))
                        ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
