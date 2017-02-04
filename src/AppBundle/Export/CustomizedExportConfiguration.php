<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export;

use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\Event;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CustomizedExportConfiguration implements ConfigurationInterface
{
    /**
     * Event this export is configurated for
     *
     * @var Event|null
     */
    private $event;

    /**
     * CustomizedExportConfiguration constructor.
     *
     * @param Event|null $event
     */
    public function __construct(Event $event = null)
    {
        $this->event = $event;
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
                ->arrayNode('participant')
                    ->info('Teilnehmerdaten')
                    ->children()
                        ->booleanNode('firstName')
                            ->info('Vorname')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('lastName')
                            ->info('Nachname')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('birthday')
                            ->info('Geburtsdatum')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('ageAtEvent')
                            ->info('Alter (bei Beginn der Veranstaltung)')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('gender')
                            ->info('Geschlecht')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('infoMedical')
                            ->info('Medizinische Hinweise')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('infoGeneral')
                            ->info('Allgemeine Hinweise')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('pid')
                            ->info('PID (Eindeutige Teilnehmernummer)')
                            ->defaultFalse()
                        ->end()
                        ->append($this->addAcquisitionAttributesNode(false, true))
                        //food
                    ->end()
                ->end()
                ->arrayNode('participation')
                    ->info('Anmeldungsdaten')
                    ->children()
                        ->booleanNode('aid')
                            ->info('AID (Eindeutige Anmeldungsnummer)')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('salution')
                            ->info('Anrede')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('firstName')
                            ->info('Vorname (Eltern)')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('lastName')
                            ->info('Nachname (Eltern)')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('email')
                            ->info('E-Mail Adresse')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('addressStreet')
                            ->info('Straße (Anschrift)')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('addressCity')
                            ->info('Stadt (Anschrift)')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('addressZip')
                            ->info('PLZ (Anschrift)')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('createdAt')
                            ->info('Eingang der Anmeldung')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('participantsCount')
                            ->info('Anzahl angemeldeter Teilnehmer')
                            ->defaultFalse()
                        ->end()
                        ->append($this->addAcquisitionAttributesNode(true, false))
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
        $attributes = $this->event->getAcquisitionAttributes($participation, $participant);
        $builder    = new TreeBuilder();
        $node       = $builder->root('acquisitionFields')
                              ->info('Felder');
        $children = $node->children();

        /** @var AcquisitionAttribute $attribute */
        foreach ($attributes as $attribute) {
            $children
                ->booleanNode('acq_field_' . $attribute->getBid())
                    ->info($attribute->getManagementTitle())
                    ->defaultFalse()
                ->end();
        }

        return $node;
    }
}