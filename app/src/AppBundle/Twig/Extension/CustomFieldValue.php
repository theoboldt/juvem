<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig\Extension;

use AppBundle\Entity\AcquisitionAttribute\AcquisitionAttributeManager;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\CustomField\BankAccountCustomFieldValue;
use AppBundle\Entity\CustomField\ChoiceCustomFieldValue;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\CustomField\DateCustomFieldValue;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\CustomField\NumberCustomFieldValue;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\CustomField\TextualCustomFieldValue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CustomFieldValue extends AbstractExtension
{

    const CUSTOM_FIELD_VALUE_FILTER = 'customFieldValue';

    /**
     * Router used to create links
     *
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $router;

    /**
     * @var AuthorizationChecker
     */
    private AuthorizationChecker $authorizationChecker;

    /**
     * @var AcquisitionAttributeManager
     */
    private AcquisitionAttributeManager $acquisitionAttributeManager;

    /**
     * @param UrlGeneratorInterface       $router
     * @param AcquisitionAttributeManager $acquisitionAttributeManager
     */
    public function __construct(
        UrlGeneratorInterface       $router,
        AuthorizationChecker        $authorizationChecker,
        AcquisitionAttributeManager $acquisitionAttributeManager
    ) {
        $this->router                      = $router;
        $this->authorizationChecker        = $authorizationChecker;
        $this->acquisitionAttributeManager = $acquisitionAttributeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                self::CUSTOM_FIELD_VALUE_FILTER,
                [
                    $this,
                    self::CUSTOM_FIELD_VALUE_FILTER,
                ],
                [
                    'pre_escape'        => 'html',
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * Get custom field by id
     *
     * @param int $bid
     * @return Attribute
     */
    private function getCustomField(int $bid): Attribute
    {
        $customFields = $this->acquisitionAttributeManager->getAttributes();
        if (!isset($customFields[$bid])) {
            throw new \InvalidArgumentException('Unknown attribute with bid ' . $bid . ' requested');
        }
        return $customFields[$bid];
    }

    /**
     * Render choice option
     *
     * @param Environment $env
     * @param Attribute   $customField
     * @param int         $choiceId
     * @return string
     */
    private static function renderChoiceOption(Environment $env, Attribute $customField, int $choiceId): string
    {
        $choice = $customField->getChoiceOption($choiceId);
        if ($choice === null) {
            return BootstrapGlyph::bootstrapGlyph('exclamation-sign')
                   . ' <i>Unbekannte Option <code>'
                   . $choiceId . '</code> ausgewählt';
        }
        $managementTitle = $choice->getManagementTitle(true);
        $shortTitle      = $choice->getShortTitle(true);

        $html = '';

        if ($managementTitle !== $shortTitle) {
            $html = '<span class="label label-primary">'
                    . twig_escape_filter($env, $shortTitle)
                    . '</span> ';
        }
        if ($choice->getManagementTitle(false)) {
            $html .= '<span title="' .
                     twig_escape_filter($env, $choice->getManagementTitle(false), 'html_attr')
                     . '"  data-toggle="tooltip" data-placement="top">';
        }
        $html .= $managementTitle;
        if ($choice->getManagementTitle(false)) {
            $html .= '</span>';
        }

        if ($choice->isDeleted()) {
            $html = '<span class="deleted">' . $html . '</span>';
        }

        return $html;
    }

    /**
     * Render custom field value
     *
     * @param Environment                           $env            Twig environment in order to escape names @see
     *                                                              twig_escape_filter()
     * @param CustomFieldValueContainer|null        $valueContainer Custom field value (container)
     * @param EntityHavingCustomFieldValueInterface $entity         $entity Entity related to custom field
     * @param bool                                  $editable       If set to true modifying buttons will be included
     * @return string
     */
    public function customFieldValue(
        Environment                           $env,
        ?CustomFieldValueContainer            $valueContainer,
        EntityHavingCustomFieldValueInterface $entity,
        bool                                  $editable = false
    ): string {
        if (!$valueContainer) {
            return '<i class="empty value-not-specified"><span></span></i>';
        }
        
        $blocks = [];

        $event       = $entity->getEvent();
        $customField = $this->getCustomField($valueContainer->getCustomFieldId());
        $value       = $valueContainer->getValue();

        if ($value instanceof ChoiceCustomFieldValue) {
            if (!$value->hasSomethingSelected()) {
                $blocks[] = '<i class="empty no-selection"><span></span></i>';
            } else {
                $choiceIds = $value->getSelectedChoices();
                if (!$customField->isMultipleChoiceType() && !$value->hasMultipleSelectedChoices()) {
                    $choiceId = reset($choiceIds);
                    $blocks[] = self::renderChoiceOption($env, $customField, $choiceId);
                } else {
                    $html = '<ul>';
                    foreach ($choiceIds as $choiceId) {
                        $html .= '<li>' . self::renderChoiceOption($env, $customField, $choiceId) . '</li>';
                    }
                    $html     .= '</ul>';
                    $blocks[] = $html;
                }
            }
        } elseif ($value instanceof DateCustomFieldValue) {
            if ($value->getValue() === null) {
                $blocks[] = '<i class="empty value-null"><span></span></i>';
            } else {
                $blocks[] = $value->getValue()->format('d.m.Y');
            }
        } elseif ($value instanceof TextualCustomFieldValue
                  || $value instanceof NumberCustomFieldValue) {
            if ($value->getValue() === null) {
                $blocks[] = '<i class="empty value-null"><span></span></i>';
            } else {
                $blocks[] = twig_escape_filter($env, $value->getValue());
            }
        } elseif ($value instanceof GroupCustomFieldValue) {
            $choiceId = $value->getValue();
            if ($choiceId === null) {
                $blocks[] = '<i class="empty no-selection"><span></span></i>';
            } else {
                $choice = $customField->getChoiceOption($choiceId);
                if ($choice) {
                    $url   = $this->router->generate(
                        'admin_event_group_detail',
                        ['eid' => $event->getEid(), 'bid' => $customField->getBid(), 'cid' => $choiceId]
                    );
                    $title = twig_escape_filter($env, $choice->getManagementTitle(true));

                    if ($choice->getManagementTitle() !== $choice->getFormTitle()) {
                        $html = sprintf(
                            '<a href="%s" title="%s" data-toggle="tooltip" data-placement="top">%s</a>',
                            $url,
                            twig_escape_filter($env, $choice->getFormTitle(), 'html_attr'),
                            $title
                        );
                    } else {
                        $html = sprintf('<a href="%s">%s</a>', $url, $title);
                    }
                    $blocks[] = $html;
                } else {
                    $blocks[] = BootstrapGlyph::bootstrapGlyph('exclamation-sign')
                                . ' <i>Unbekannte Option <code>'
                                . $choiceId . '</code> ausgewählt';
                }
            }
        } elseif ($value instanceof ParticipantDetectingCustomFieldValue) {
            $html = '<span class="custom-field-participant-detecting">';

            if (empty($value->getRelatedFirstName()) && empty($value->getRelatedLastName())) {
                $html .= '<i class="empty value-null"><span></span></i>';
            } elseif ($value->getParticipantAid() && $event) {
                $url  = $this->router->generate(
                    'admin_participant_detail',
                    ['eid' => $event->getEid(), 'aid' => $value->getParticipantAid()]
                );
                $html .= sprintf(
                    '<a href="%s" data-toggle="tooltip" title="Original: %s">%s %s</a> ',
                    $url,
                    twig_escape_filter(
                        $env, $value->getParticipantFirstName() . ' ' . $value->getParticipantLastName(), 'html_attr'
                    ),
                    twig_escape_filter($env, $value->getRelatedFirstName()),
                    twig_escape_filter($env, $value->getRelatedLastName())
                );
                if ($value->isSystemSelection()) {
                    $html .= BootstrapGlyph::bootstrapGlyph('flash', 'automatisch');
                }
                $html .= BootstrapGlyph::bootstrapGlyph('link', 'verknüpft');
            } else {
                $html .= '<span data-toggle="tooltip" title="Vorname">';
                $html .= twig_escape_filter($env, $value->getRelatedFirstName());
                $html .= '</span> ';
                $html .= '<span data-toggle="tooltip" title="Nachname">';
                $html .= twig_escape_filter($env, $value->getRelatedLastName());
                $html .= '</span>';
            }

            if ($editable && $event && $this->authorizationChecker->isGranted('participants_edit', $event)) {
                $html .= sprintf(
                    ' <button data-toggle="modal" data-target="#dialogModalRelateParticipant"
                            data-title="%s"
                            data-description="%s"
                            data-first-name="%s"
                            data-last-name="%s"
                            data-related-aid="%s"
                            data-bid="%s"
                            data-entity-class="%s"
                            data-entity-id="%d"
                            class="btn btn-default btn-xs"
                            title="Verknüpfung zu Teilnehmer:in verwalten">%s</button>',
                    $customField->getFormTitle(),
                    $customField->getFormDescription(),
                    $value->getRelatedFirstName(),
                    $value->getRelatedLastName(),
                    $value->getParticipantAid(),
                    $customField->getId(),
                    get_class($entity),
                    $entity->getId(),
                    BootstrapGlyph::bootstrapGlyph('pencil')
                );
            }
            $html     .= '</span>';
            $blocks[] = $html;
        } elseif ($value instanceof BankAccountCustomFieldValue) {
            $html = '';
            if ($value->getIban(false) === null || $value->getBic() === null) {
                $html .= '<i class="empty value-null"><span></span></i>';
            } else {
                $html .= '<label>IBAN:</label> ' . $value->getIban(true) . '<br>';
                $html .= '<label>BIC:</label> ' . $value->getBic() . '<br>';
                $html .= '<label>Kontoinhaber:in:</label> ' . $value->getOwner();
            }
            $blocks[] = $html;
        } else {
            $blocks[] = twig_escape_filter($env, $valueContainer->getValue()->getTextualValue());
            $blocks[] = '<div class="alert alert-warning" role="alert">' .
                        BootstrapGlyph::bootstrapGlyph('exclamation-sign')
                        . ' <i>Unbekannter Feldtyp <code>' . $valueContainer->getType()
                        . '</code></i>'
                        . '</div>';
        }

        $result = '';
        if (count($blocks) > 1) {
            $result = '<p>' . implode("</p>\n<p>", $blocks) . "</p>\n";
        } else {
            $result = reset($blocks);
        }

        if ($valueContainer->hasComment()) {
            $result .= '<div class="well well-sm" title="Ergänzung" data-toggle="tooltip" data-placement="top">'
                       . twig_escape_filter($env, $valueContainer->getComment())
                       . '</div>';
        }
        return $result;
    }

}
