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

use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const OPTION_CONFIRMED_ALL         = 'all';
    const OPTION_CONFIRMED_CONFIRMED   = 'confirmed';
    const OPTION_CONFIRMED_UNCONFIRMED = 'unconfirmed';
    
    const OPTION_PAID_ALL     = 'all';
    const OPTION_PAID_PAID    = 'paid';
    const OPTION_PAID_NOTPAID = 'notpaid';
    
    const OPTION_REJECTED_WITHDRAWN_ALL                    = 'all';
    const OPTION_REJECTED_WITHDRAWN_NOT_REJECTED_WITHDRAWN = 'notrejectedwithdrawn';
    const OPTION_REJECTED_WITHDRAWN_REJECTED_WITHDRAWN     = 'rejectedwithdrawn';


    protected function booleanNodeCreator($name, $info) {
        $node = new BooleanNodeDefinition($name);
        $node->beforeNormalization()
             ->ifString()
                ->then(function ($v) { return (bool)$v; })
                ->end()
             ->info($info)
             ->defaultFalse();
        
        return $node;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('profile');
        $rootNode->children()
                    ->arrayNode('general')
                        ->addDefaultsIfNotSet()
                        ->info('Allgemein')
                        ->children()
                            ->append($this->booleanNodeCreator('includePrivate', 'Interne Felder mit ausgeben'))
                            ->append($this->booleanNodeCreator('includeDescription', 'Internen Erklärungstext der Felder mit ausgeben (wenn vorhanden)'))
                            ->append($this->booleanNodeCreator('includeComments', 'Anmerkungen mit ausgeben'))
                            ->append($this->booleanNodeCreator('includePrice', 'Preis mit ausgeben'))
                            ->append($this->booleanNodeCreator('includeToPay', 'Offener Zahlungsbetrag mit ausgeben'))
                        ->end()
                    ->end()
                    ->arrayNode('choices')
                        ->addDefaultsIfNotSet()
                        ->info('Auswahlfelder')
                        ->children()
                            ->append($this->booleanNodeCreator('includeShortTitle', 'Kürzel mit ausgeben (wenn vorhanden)'))
                            ->append($this->booleanNodeCreator('includeManagementTitle', 'Internen Titel mit ausgeben'))
                            ->append($this->booleanNodeCreator('includeNotSelected', 'Nicht zutreffende Optionen mit ausgeben (und als nicht zutreffend markieren)'))
                        ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
