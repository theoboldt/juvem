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
        $rootNode->children()
                    ->scalarNode('title')
                        ->info('Titel')
                        ->defaultValue('Profile')
                    ->end()
                    ->arrayNode('general')
                        ->addDefaultsIfNotSet()
                        ->info('Allgemein')
                        ->children()
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
            ->append(
                self::createGroupSortNodes(
                    $participantNodes, 'Gruppieren (erscheint in der Kopfzeile, zunächst sortiert nach diesem Feld)'
                )
            )
            ->end()
        ;
        return $treeBuilder;
    }
}
