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


use AppBundle\Controller\Event\Participation\AdminMultipleExportController;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\ChoiceFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\CommentBase;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Manager\CommentManager;
use AppBundle\Manager\Payment\PaymentManager;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Frame;
use PhpOffice\PhpWord\Style\Image;
use PhpOffice\PhpWord\Style\Language;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipantProfile
{
    const STYLE_FONT_DESCRIPTION      = 'DescriptionF';
    const STYLE_PARAGRAPH_DESCRIPTION = 'DescriptionP';

    const STYLE_FONT_LABEL = 'LabelF';

    const STYLE_FONT_NONE = 'NoneF';

    const STYLE_PARAGRAPH_COMMENT = 'CommentP';

    const STYLE_LIST               = 'ListL';
    const STYLE_PARAGRAPH_LIST     = 'ListP';
    const STYLE_PARAGRAPH_LIST_END = 'ListEndP';
    const STYLE_FONT_LIST_END      = 'ListEndF';

    const STYLE_FONT_NEGATIVE_LABEL = 'LabelNegativeF';
    const STYLE_LIST_NEGATIVE       = 'ListNegativeL';
    const STYLE_FONT_NEGATIVE       = 'NegativeP';

    /**
     * Participants
     *
     * @var array|Participant[]
     */
    private $participants;

    /**
     * Array containing configuration options defined in {@see Configuration}
     *
     * @var array
     */
    private $configuration;

    /**
     * Path to logo if exists
     *
     * @var string|null
     */
    private $logoPath;

    /**
     * First section
     *
     * @var bool
     */
    private $firstSection = true;

    /**
     * Amount of columns for detail rows
     *
     * @var int
     */
    private $columns = 3;

    /**
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * phoneUtil
     *
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    /**
     * Comment provider
     *
     * @var CommentManager
     */
    private $commentManager;

    /**
     * PaymentManager
     *
     * @var PaymentManager
     */
    private $paymentManager;

    /**
     * Code generator
     *
     * @var TemporaryBarCodeGenerator
     */
    private $temporaryBarCodeGenerator;

    /**
     * ParticipantProfile constructor.
     *
     * @param Participant[]|array $participants                    List of participants for export
     * @param array $configuration                                 Array containing configuration options defined in {@see Configuration}
     * @param UrlGeneratorInterface $urlGenerator                  Providing URLS
     * @param PhoneNumberUtil $phoneUtil                           Util to format phone numbers
     * @param CommentManager $commentManager                       Comment manager to fetch comments
     * @param PaymentManager $paymentManager
     * @param TemporaryBarCodeGenerator $temporaryBarCodeGenerator Bar code image generator
     * @param string|null $logoPath                                Logo path for doc
     */
    public function __construct(
        array $participants,
        array $configuration,
        UrlGeneratorInterface $urlGenerator,
        PhoneNumberUtil $phoneUtil,
        CommentManager $commentManager,
        PaymentManager $paymentManager,
        TemporaryBarCodeGenerator $temporaryBarCodeGenerator,
        ?string $logoPath = null
    )
    {
        $this->participants              = $participants;
        $this->configuration             = $configuration;
        $this->urlGenerator              = $urlGenerator;
        $this->phoneUtil                 = $phoneUtil;
        $this->commentManager            = $commentManager;
        $this->logoPath                  = $logoPath;
        $this->temporaryBarCodeGenerator = $temporaryBarCodeGenerator;
        $this->paymentManager            = $paymentManager;
    }

    /**
     * Prepare php document, add style definitions etc
     *
     * @return PhpWord
     */
    private function prepareDocument(): PhpWord
    {
        $document = new PhpWord();
        $language = new Language(Language::DE_DE);

        if ($this->isConfigurationEnabled('general', 'includeComments')) {
            $this->commentManager->ensureFetchedForEvent($this->getEvent());
        }

        $settings = $document->getSettings();
        $settings->setHideSpellingErrors(true);
        $settings->setDecimalSymbol(',');
        $settings->setAutoHyphenation(true);
        $settings->setThemeFontLang($language);

        $information = $document->getDocInfo();
        $information->setTitle('Teilnahmeprofile');
        $information->setSubject($this->getEvent()->getTitle());
        $information->setDescription('Profile der Teilnehmer:innen der Veranstaltung ' . $this->getEvent()->getTitle());

        //title styles
        $document->addTitleStyle(
            2,
            ['size' => 13],
            ['spaceBefore' => 0]
        );
        $document->addTitleStyle(
            3,
            ['size' => 9, 'smallCaps' => true, 'color' => '222222'],
            ['spaceBefore' => 150, 'spaceAfter' => 0, 'keepNext' => true]
        );
        $document->addTitleStyle(
            4,
            ['size' => 8, 'bold' => true],
            ['spaceBefore' => 100, 'spaceAfter' => 0, 'keepNext' => true]
        );

        //default styles
        $document->setDefaultFontSize(8);
        $defaultParagraphStyle = [
            'keepLines'    => true,
            'spaceBefore'  => 0,
            'spaceAfter'   => 60,
            'marginLeft'   => 100,
            'marginRight'  => 600,
            'widowControl' => false,
        ];
        $document->setDefaultParagraphStyle($defaultParagraphStyle);

        //comment style
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_COMMENT,
            array_merge($defaultParagraphStyle, ['keepNext' => false])
        );

        //list styles
        $document->addNumberingStyle(
            self::STYLE_LIST,
            [
                'type'   => 'multilevel',
                'levels' => [
                    [
                        'restart' => true,
                        'format'  => 'bullet',
                        'text'    => " %1\u{25cf}",
                        'indent'  => 100,
                        'left'    => 160,
                        'hanging' => 160,
                        'tabPos'  => 160,
                    ],
                ],
            ]
        );
        $document->addNumberingStyle(
            self::STYLE_LIST_NEGATIVE,
            [
                'type'   => 'multilevel',
                'levels' => [
                    [
                        'restart' => true,
                        'format'  => 'bullet',
                        'text'    => " %1\u{25cb}",
                        'indent'  => 100,
                        'left'    => 160,
                        'hanging' => 160,
                        'tabPos'  => 160,
                    ],
                ],
            ]
        );
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_LIST,
            array_merge($defaultParagraphStyle, ['spaceAfter' => 10, 'cantSplit' => true, 'keepNext' => true])
        );
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_LIST_END, array_merge($defaultParagraphStyle, ['keepNext' => true])
        );
        $document->addFontStyle(
            self::STYLE_FONT_LIST_END, ['size' => 2, 'spaceAfter' => 0]
        );

        //fillout attribute description style
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_DESCRIPTION,
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'keepNext' => true, 'marginLeft' => 400, 'marginRight' => 600]
        );
        $document->addFontStyle(self::STYLE_FONT_DESCRIPTION, ['size' => 7, 'color' => '333333']);

        //none fillout value style
        $document->addFontStyle(self::STYLE_FONT_NONE, ['size' => 8, 'color' => '666666', 'italic' => true]);

        //label style
        $document->addFontStyle(
            self::STYLE_FONT_LABEL,
            ['size' => 7, 'color' => 'FFFFFF', 'bgColor' => '000000', 'boldt' => true]
        );
        $document->addFontStyle(
            self::STYLE_FONT_NEGATIVE_LABEL,
            [
                'size'          => 7,
                'color'         => '333333',
                'bgColor'       => 'CCCCCC',
                'boldt'         => true,
                'strikethrough' => true,
                'italic'        => true,
            ]
        );
        $document->addFontStyle(
            self::STYLE_FONT_NEGATIVE,
            [
                'color'         => 'AAAAAA',
                'italic'        => true,
                'strikethrough' => true
            ]
        );

        return $document;
    }

    /**
     * Add a section
     *
     * @param PhpWord $document
     * @param array $config
     * @return Section
     */
    private function addSection(PhpWord $document, array $config = []): Section
    {
        $default = ['marginLeft' => 1134, 'marginRight' => 1134, 'breakType' => 'continuous'];
        $section = $document->addSection(array_merge($default, $config));
        if ($this->firstSection) {
            $footer = $section->addFooter();

            $table = $footer->addTable(
                [
                    'unit'  => TblWidth::PERCENT,
                    'width' => 100 * 50,
                ]
            );
            $table->addRow();
            if ($this->logoPath) {
                $cell = $table->addCell();
                $cell->addImage(
                    $this->logoPath,
                    [
                        'width'         => 12,
                        'height'        => 12,
                        'marginTop'     => -1,
                        'marginLeft'    => -1,
                        'wrappingStyle' => 'square'
                    ]
                );
            }

            $cell = $table->addCell();
            $cell->addPreserveText('{PAGE}/{NUMPAGES}', [], ['alignment' => Jc::END]);
        }

        $this->firstSection = false;
        return $section;
    }

    /**
     * Get related event
     *
     * @return Event
     */
    private function getEvent(): Event
    {
        /** @var Participant $participant */
        $participant = reset($this->participants);
        if (!$participant) {
            throw new \InvalidArgumentException();
        }
        return $participant->getEvent();
    }

    /**
     * Check if configuration option is set and true
     *
     * @param string $group  Configuration group
     * @param string $option Configuration option
     * @return bool
     */
    private function isConfigurationEnabled(string $group, string $option): bool
    {
        if (isset($this->configuration[$group]) && isset($this->configuration[$group][$option])) {
            return (bool)$this->configuration[$group][$option];
        }
        return false;
    }

    /**
     * Add transmitted fillouts to transmitted section
     *
     * @param array|Fillout[] $fillouts List of fillouts
     * @param Section $section          Document
     */
    private function addFilloutsToSection(array $fillouts, Section $section): void
    {
        /** @var Fillout $fillout */
        foreach ($fillouts as $fillout) {
            $attribute = $fillout->getAttribute();

            if (!$this->isConfigurationEnabled('general', 'includePrivate') && $attribute->isPublic()) {
                continue;
            }
            $title       = $attribute->getManagementTitle();
            $description = $attribute->getManagementDescription();

            if (!$attribute->isPublic()) {
                $title .= "\xc2\xa0\u{1f512}";
            }

            $this->addDatumTitle($section, $title, $description);

            $value = $fillout->getValue();
            if ($value instanceof GroupFilloutValue) {
                $choices = $value->getSelectedChoices();
                if (count($choices)) {
                    /** @var AttributeChoiceOption $choice */
                    $choice    = reset($choices);
                    $groupLink = $this->urlGenerator->generate(
                        'admin_event_group_detail',
                        [
                            'bid' => $attribute->getBid(),
                            'eid' => $this->getEvent()->getEid(),
                            'cid' => $choice->getId(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    $table     = $section->addTable(
                        [
                            'unit'  => TblWidth::PERCENT,
                            'width' => 100 * 50,
                        ]
                    );
                    $row       = $table->addRow();
                    $cell      = $row->addCell();
                    $cell->addLink($groupLink, $choice->getManagementTitle(true));

                    $cell = $row->addCell();
                    $run  = $cell->addTextRun();
                    $run->addImage(
                        $this->temporaryBarCodeGenerator->createCode('url:' . $groupLink, 100),
                        [
                            'width'         => 42,
                            'height'        => 42,
                            'positioning'   => 'relative',
                            'marginTop'     => 0,
                            'marginLeft'    => 1,
                            'wrappingStyle' => 'tight'
                        ]
                    );
                } else {
                    $section->addText('(Keine Auswahl)', self::STYLE_FONT_NONE);
                }
            } elseif ($value instanceof ChoiceFilloutValue) {
                $choices  = $attribute->getChoiceOptions();
                $selected = $value->getSelectedChoices();

                if (!count($selected) && !$this->isConfigurationEnabled('choices', 'includeNotSelected')) {
                    $section->addText('(Keine Auswahl)', self::STYLE_FONT_NONE);
                } else {
                    if ($attribute->isMultipleChoiceType() || $this->isConfigurationEnabled('choices', 'includeNotSelected')) {
                        foreach ($choices as $choice) {
                            if (isset($selected[$choice->getId()])) {
                                $fontStyleLabel     = self::STYLE_FONT_LABEL;
                                $listStyle          = self::STYLE_LIST;
                                $listParagraphStyle = self::STYLE_PARAGRAPH_LIST;
                                $fontStyle          = null;

                            } else {
                                if (!$this->isConfigurationEnabled('choices', 'includeNotSelected')) {
                                    continue;
                                }
                                $fontStyleLabel     = self::STYLE_FONT_NEGATIVE_LABEL;
                                $listStyle          = self::STYLE_LIST_NEGATIVE;
                                $listParagraphStyle = self::STYLE_PARAGRAPH_LIST;
                                $fontStyle          = self::STYLE_FONT_NEGATIVE;
                            }

                            $listItemRun     = $section->addListItemRun(0, $listStyle, $listParagraphStyle);
                            $listItemTextRun = $listItemRun->addTextRun($listParagraphStyle);

                            if ($this->isConfigurationEnabled('choices', 'includeShortTitle') && $choice->getShortTitle(false)) {
                                $listItemTextRun->addText(
                                    " " . $choice->getShortTitle(false) . " ", $fontStyleLabel, $listParagraphStyle
                                );
                                $listItemTextRun->addText(' ', null, $listParagraphStyle);
                            }
                            if ($this->isConfigurationEnabled('choices', 'includeManagementTitle')) {
                                $listItemTextRun->addText(
                                    $choice->getManagementTitle(true), $fontStyle, $listParagraphStyle
                                );
                            }
                        }
                        $section->addText("\xc2\xa0", self::STYLE_FONT_LIST_END, self::STYLE_PARAGRAPH_LIST_END);
                    } else {
                        $choice = reset($selected);
                        $section->addText($choice->getManagementTitle(true));
                    }
                }
            } else {
                $textrun = $section->addTextRun();
                $textrun->addText($fillout->getValue()->getTextualValue());
            }
        }
    }

    /**
     * Add comment section
     *
     * @param array|CommentBase[] $comments Comments to add
     * @param Section $section              Section to add comments to
     * @param string $commentTarget         Target  description text
     */
    private function addCommentsToSection(array $comments, Section $section, string $commentTarget): void
    {
        /** @var CommentBase $comment */
        foreach ($comments as $comment) {
            if ($comment->getDeletedAt()) {
                continue;
            }
            $metaText = ($comment->getCreatedBy() ? $comment->getCreatedBy()->fullname() : 'SYSTEM') .
                        ' am ' . $comment->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME);
            if ($comment->getModifiedAt()) {
                $metaText .= ', geändert von ' .
                             ($comment->getCreatedBy() ? $comment->getCreatedBy()->fullname() : 'SYSTEM') .
                             ' am  ' . $comment->getModifiedAt()->format(Event::DATE_FORMAT_DATE_TIME);
            }
            $metaText .= $commentTarget;
            $section->addText($metaText, self::STYLE_FONT_DESCRIPTION, self::STYLE_PARAGRAPH_DESCRIPTION);
            $section->addText($comment->getContent(), [], self::STYLE_PARAGRAPH_COMMENT);
        }
    }

    /**
     * Add Datum to document
     *
     * @param Section $section         Section to add datum to
     * @param string $label            Title of datum
     * @param array|string $data       Data rows, will be separated by line breaks
     * @param string|null $description Description text, will not be displayed if equal to label
     * @return void
     */
    private function addDatum(Section $section, string $label, $data, ?string $description = null): void
    {
        $this->addDatumTitle($section, $label, $description);
        if (empty($data) || (is_string($data) && trim($data) === '')) {
            $section->addText('(Keine Angabe)', self::STYLE_FONT_NONE);
        } else {
            if (!is_array($data)) {
                $data = [$data];
            }
            if (count($data)) {
                $textrun = $section->addTextRun();
                $first   = true;
                foreach ($data as $datum) {
                    if (!$first) {
                        $textrun->addTextBreak();
                    }
                    $textrun->addText($datum);
                    $first = false;
                }
            }
        }
    }

    /**
     * Add title block to document
     *
     * @param Section $section         Section to add datum to
     * @param string $label            Title of datum
     * @param string|null $description Description text, will not be displayed if equal to label
     * @return void
     */
    private function addDatumTitle(Section $section, string $label, ?string $description = null): void
    {
        $section->addTitle($label, 4);
        if ($description && $description !== $label && $this->isConfigurationEnabled('general', 'includeDescription')) {
            $section->addText($description, self::STYLE_FONT_DESCRIPTION, self::STYLE_PARAGRAPH_DESCRIPTION);
        }
    }

    /**
     * Generate document, provide export file path
     */
    public function generate(): PhpWord
    {
        $participants = $this->participants;
        $document     = $this->prepareDocument();
        $event        = $this->getEvent();

        $accessor = AdminMultipleExportController::provideTextualValueAccessor();
        $grouping = isset($this->configuration['grouping_sorting']['grouping'])
                    && isset($this->configuration['grouping_sorting']['grouping']['enabled'])
                    && $this->configuration['grouping_sorting']['grouping']['enabled'];

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $groupValue = null;
            if ($grouping) {
                $groupField = $this->configuration['grouping_sorting']['grouping']['field'];
                $groupValue = $accessor($participant, $groupField);
            }

            $section = $this->addSection($document, ['breakType' => 'nextPage']);
            $section->addTitle($participant->fullname(), 2);

            //header
            $header = $section->addHeader();
            $table  = $header->addTable(
                [
                    'unit'  => TblWidth::PERCENT,
                    'width' => 100 * 50,
                ]
            );
            $table->addRow();
            $cell    = $table->addCell();
            $textrun = $cell->addTextRun();
            $textrun->addText($participant->fullname());

            if ($grouping) {
                if ($groupValue) {
                    $textrun->addText(sprintf(' [%s]', $groupValue));
                } else {
                    $textrun->addText(' [keine Angabe]', self::STYLE_FONT_NONE);
                }
            }

            $cell    = $table->addCell();
            $textrun = $cell->addTextRun(['alignment' => Jc::END]);
            $textrun->addText($event->getTitle() . ' (' . $event->getStartDate()->format('Y') . ')');

            //participants data
            $section = $this->addSection(
                $document,
                [
                    'colsNum'   => 5,
                    'colsSpace' => 100,
                ]
            );

            $this->addDatum($section, 'Vorname', $participant->getNameFirst());
            $this->addDatum($section, 'Nachname', $participant->getNameLast());
            
            $birthday = $participant->getBirthday()->format(Event::DATE_FORMAT_DATE);
            if ($participant->hasBirthdayAtEvent()) {
                $birthday .= " \u{1F381}";
            }
            $this->addDatum($section, 'Geburtsdatum', $birthday);
            $age = sprintf(
                "%s (~%s) Jahre",
                $participant->getYearsOfLifeAtEvent(),
                number_format($participant->getAgeAtEvent(1), 1, ',', "'")
            );
            $this->addDatum($section, 'Alter', $age);

            $this->addDatum($section, 'Geschlecht', $participant->getGender());

            $linkPath = $this->temporaryBarCodeGenerator->createCode(
                'url:' . $this->urlGenerator->generate(
                    'admin_participant_detail',
                    [
                        'aid' => $participant->getAid(),
                        'eid' => $this->getEvent()->getEid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                32
            );
            $section->addImage(
                $linkPath,
                [
                    'width'            => 40,
                    'height'           => 40,
                    'marginTop'        => 0,
                    'marginLeft'       => -1,
                    'wrappingStyle'    => Frame::WRAP_SQUARE,
                    'positioning'      => Image::POSITION_ABSOLUTE,
                    'posHorizontal'    => Image::POSITION_HORIZONTAL_RIGHT,
                    'posVertical'      => Image::POSITION_VERTICAL_TOP,
                    'posHorizontalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
                    'posVerticalRel'   => Image::POSITION_RELATIVE_TO_MARGIN,
                ]
            );

            $section = $this->addSection(
                $document,
                [
                    'colsNum'   => $this->columns,
                    'colsSpace' => 100,
                ]
            );
            if ($this->paymentManager && $this->isConfigurationEnabled('general', 'includePrice')) {
                $this->addDatum(
                    $section, 'Preis',
                    number_format(
                        $this->paymentManager->getEntityPriceTag($participant)->getPrice(true),
                        2,
                        ',',
                        '.'
                    ) . ' €'
                );
            }
            if ($this->paymentManager && $this->isConfigurationEnabled('general', 'includePrice')) {
                $this->addDatum(
                    $section,
                    'Offener Zahlungsbetrag',
                    number_format(
                        $this->paymentManager->getToPayValueForParticipant($participant, true),
                        2,
                        ',',
                        '.'
                    ) . ' €'

                );
            }
            $this->addDatum($section, 'Medizinische Hinweise', $participant->getInfoMedical());
            $this->addDatum($section, 'Allgemeine Hinweise', $participant->getInfoGeneral());
            $this->addDatum($section, 'Ernährung', implode(', ', $participant->getFood(true)->getActiveList(true)));

            $this->addFilloutsToSection($participant->getAcquisitionAttributeFillouts()->toArray(), $section);

            //participations data
            $section = $this->addSection($document);
            $section->addTitle('Anmeldung', 3);
            $participation = $participant->getParticipation();
            $section       = $this->addSection(
                $document,
                [
                    'colsNum'   => $this->columns,
                    'colsSpace' => 100,
                ]
            );

            $participationAddress = [
                $participation->getSalutation() . ' ' . $participation->fullname(),
                $participation->getAddressStreet(),
                $participation->getAddressZip().' '.$participation->getAddressCity(),
            ];
            if ($participation->getAddressCountry() !== Event::DEFAULT_COUNTRY) {
                $participationAddress[] = $participation->getAddressCountry();
            }
            $this->addDatum($section, 'Anschrift', $participationAddress);

            $section->addTitle('E-Mail Adresse', 4);
            $section->addLink('mailto:' . $participation->getEmail(), $participation->getEmail());

            $this->addDatum($section, 'Eingang', $participant->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME));

            $this->addFilloutsToSection($participation->getAcquisitionAttributeFillouts()->toArray(), $section);

            $section = $this->addSection($document);
            $section->addTitle('Telefonnummern', 3);

            $phoneNumbers = $participation->getPhoneNumbers()->toArray();
            $columns      = 0;
            if (count($phoneNumbers)) {
                $table = $section->addTable(
                    [
                        'unit'  => TblWidth::PERCENT,
                        'width' => 100 * 50,
                    ]
                );
                $row   = $table->addRow();

                /** @var PhoneNumber $phoneNumber */
                foreach ($phoneNumbers as $phoneNumber) {
                    if ($columns === 3) {
                        if (isset($cellImage)) {
                            //add additional vertical space to be make qr code usable
                            $textrun = $cellImage->addTextRun();
                            $textrun->addTextBreak();
                            $textrun->addTextBreak();
                            $textrun->addTextBreak();
                        }
                        $columns = 0;
                        $row     = $table->addRow();
                    }
            
                    $cellImage = $row->addCell(12);
                    $codePath  = $this->temporaryBarCodeGenerator->createCode(
                        'tel:' .
                        str_replace(' ', '', $this->phoneUtil->format($phoneNumber->getNumber(), 'INTERNATIONAL')),
                        80
                    );
                    $cellImage->addImage(
                        $codePath,
                        [
                            'width'         => 38,
                            'height'        => 38,
                            'marginTop'     => -1,
                            'marginLeft'    => -1,
                            'wrappingStyle' => 'square',
                        ]
                    );
            
                    $cellText = $row->addCell(null, ['rightFromText' => 12]);
                    $textrun  = $cellText->addTextRun();
                    $textrun->addText($this->phoneUtil->format($phoneNumber->getNumber(), 'INTERNATIONAL'));
                    if ($phoneNumber->getDescription()) {
                        $textrun->addTextBreak();
                        $textrun->addText($phoneNumber->getDescription());
                    }
        
                    ++$columns;
                }


            } else {
                $section->addText(
                    'Keine Telefonnummern gespeichert', self::STYLE_FONT_DESCRIPTION, self::STYLE_PARAGRAPH_DESCRIPTION
                );
            }

            if ($this->isConfigurationEnabled('general', 'includeComments')) {
                $section = $this->addSection($document);
                $section->addTitle('Anmerkungen', 3);
                $section = $this->addSection(
                    $document,
                    [
                        'colsNum'   => $this->columns,
                        'colsSpace' => 100,
                    ]
                );
                if (!$this->commentManager->countForParticipation($participation)
                    && !$this->commentManager->countForParticipant($participant)) {
                    $section->addText(
                        'Keine Anmerkungen gespeichert.', self::STYLE_FONT_DESCRIPTION,
                        self::STYLE_PARAGRAPH_DESCRIPTION
                    );
                } else {
                    $this->addCommentsToSection(
                        $this->commentManager->forParticipation($participation), $section, ' zur Anmeldung'
                    );

                    switch ($participant->getGender()) {
                        case Participant::LABEL_GENDER_FEMALE:
                        case Participant::LABEL_GENDER_FEMALE_ALIKE:
                            $commentTarget = ' zur Teilnehmerin';
                            break;
                        case Participant::LABEL_GENDER_MALE:
                        case Participant::LABEL_GENDER_MALE_ALIKE:
                            $commentTarget = ' zum Teilnehmer';
                            break;
                        default:
                            $commentTarget = ' zur teilnehmende Person ';
                            break;
                    }
                    $this->addCommentsToSection(
                        $this->commentManager->forParticipant($participant),
                        $section,
                        $commentTarget
                    );
                }
            }

        }
        return $document;
    }

    /**
     * Cleanup
     *
     * @return void
     */
    public function cleanup(): void
    {
        $this->temporaryBarCodeGenerator->cleanup();
    }
}
