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


use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\ChoiceFilloutValue;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\CommentBase;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Manager\CommentManager;
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
    
    /**
     * Document
     *
     * @var PhpWord
     */
    private $document;
    
    /**
     * Participants
     *
     * @var array|Participant[]
     */
    private $participants;
    
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
     * Code generator
     *
     * @var TemporaryBarCodeGenerator
     */
    private $temporaryBarCodeGenerator;
    
    /**
     * ParticipantProfile constructor.
     *
     * @param Participant[]|array $participants                    List of participants for export
     * @param UrlGeneratorInterface $urlGenerator                  Providing URLS
     * @param PhoneNumberUtil $phoneUtil                           Util to format phone numbers
     * @param CommentManager $commentManager                       Comment manager to fetch comments
     * @param TemporaryBarCodeGenerator $temporaryBarCodeGenerator Bar code image generator
     * @param string|null $logoPath                                Logo path for doc
     */
    public function __construct(
        array $participants,
        UrlGeneratorInterface $urlGenerator,
        PhoneNumberUtil $phoneUtil,
        CommentManager $commentManager,
        TemporaryBarCodeGenerator $temporaryBarCodeGenerator,
        ?string $logoPath = null
    )
    {
        $this->participants              = $participants;
        $this->urlGenerator              = $urlGenerator;
        $this->phoneUtil                 = $phoneUtil;
        $this->commentManager            = $commentManager;
        $this->logoPath                  = $logoPath;
        $this->temporaryBarCodeGenerator = $temporaryBarCodeGenerator;
        $this->document                  = $this->prepareDocument();
    }
    
    /**
     * Prepare php document, add style definitions etc
     *
     * @return PhpWord
     */
    private function prepareDocument(): PhpWord
    {
        Settings::setOutputEscapingEnabled(true);
        $document    = new PhpWord();
        $language    = new Language(Language::DE_DE);

        $settings    = $document->getSettings();
        $settings->setHideSpellingErrors(true);
        $settings->setDecimalSymbol(',');
        $settings->setAutoHyphenation(true);
        $settings->setThemeFontLang($language);

        $information = $document->getDocInfo();
        $information->setTitle('Teilnehmerprofile');
        $information->setSubject($this->getEvent()->getTitle());
        $information->setDescription('Profile der Teilnehmer der Veranstaltung ' . $this->getEvent()->getTitle());
        
        $document->addTitleStyle(2, ['size' => 13], ['spaceBefore' => 0]);
        $document->addTitleStyle(
            3, ['size' => 9, 'smallCaps' => true, 'color' => '222222'],
            ['spaceBefore' => 150, 'spaceAfter' => 0, 'keepNext' => true]
        );
        
        $document->addTitleStyle(
            4, ['size' => 8, 'bold' => true], ['spaceBefore' => 100, 'spaceAfter' => 0, 'keepNext' => true]
        );
        
        $document->setDefaultFontSize(8);
        $defaultParagraphStyle = [
            'keepLines'    => true, 'spaceBefore' => 0, 'spaceAfter' => 100, 'marginLeft' => 100, 'marginRight' => 600,
            'widowControl' => false,
        ];
        $document->setDefaultParagraphStyle($defaultParagraphStyle);
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_COMMENT, array_merge($defaultParagraphStyle, ['keepNext' => true])
        );
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_LIST,
            array_merge($defaultParagraphStyle, ['spaceAfter' => 40, 'cantSplit' => true, 'keepNext' => true])
        );
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_LIST_END, array_merge($defaultParagraphStyle, ['keepNext' => false])
        );
        $document->addFontStyle(self::STYLE_FONT_LIST_END, ['size' => 2, 'spaceAfter' => 0,]);
        
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_DESCRIPTION,
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'keepNext' => true, 'marginLeft' => 400, 'marginRight' => 600]
        );
        $document->addFontStyle(self::STYLE_FONT_DESCRIPTION, ['size' => 7, 'color' => '333333']);
        
        $document->addFontStyle(self::STYLE_FONT_NONE, ['size' => 8, 'color' => '666666', 'italic' => true]);
        
        $document->addFontStyle(self::STYLE_FONT_LABEL, ['size' => 7, 'color' => 'FFFFFF', 'bgColor' => '000000', 'boldt' => true]);
        
        $document->addNumberingStyle(
            self::STYLE_LIST,
            [
                'type'   => 'multilevel',
                'levels' => [
                    [
                        'restart' => true,
                        'format'  => 'bullet',
                        'text'    => ' %1•',
                        'indent'  => 100,
                        'left'    => 160,
                        'hanging' => 160,
                        'tabPos'  => 160,
                    ],
                ],
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
            $cell->addPreserveText('{PAGE}/{NUMPAGES}', [], ['alignment' => Jc::RIGHT]);
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
     * Add transmitted fillouts to transmitted section
     *
     * @param array|Fillout[] $fillouts List of fillouts
     * @param Section $section          Document
     */
    private function addFilloutsToSection(array $fillouts, Section $section): void
    {
        /** @var Fillout $fillout */
        foreach ($fillouts as $fillout) {
            $attribute   = $fillout->getAttribute();
            $title       = $attribute->getManagementTitle();
            $description = $attribute->getManagementDescription();
            
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
                        $this->temporaryBarCodeGenerator->createCode('url:' . $groupLink, 32),
                        [
                            'width'         => 30,
                            'height'        => 30,
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
                $choices = $value->getSelectedChoices();
                if (!count($choices)) {
                    $section->addText('(Keine Auswahl)', self::STYLE_FONT_NONE);
                } else {
                    if ($attribute->isMultipleChoiceType()) {
                        foreach ($choices as $choice) {
                            $listItemRun     = $section->addListItemRun(0, self::STYLE_LIST);
                            $listItemTextRun = $listItemRun->addTextRun(self::STYLE_PARAGRAPH_LIST);
                            if ($choice->getShortTitle(false)) {
                                $listItemTextRun->addText(" ".$choice->getShortTitle(false)." ", self::STYLE_FONT_LABEL);
                                $listItemTextRun->addText(' ');
                            }
                            $listItemTextRun->addText($choice->getManagementTitle(true));
                        }
                        $section->addText("\xc2\xa0", self::STYLE_FONT_LIST_END, self::STYLE_PARAGRAPH_LIST_END);
                    } else {
                        $choice = reset($choices);
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
        if ($description && $description !== $label) {
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
        
        /** @var Participant $participant */
        foreach ($participants as $participant) {
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
            
            $cell    = $table->addCell();
            $textrun = $cell->addTextRun(['alignment' => Jc::RIGHT]);
            $textrun->addText($event->getTitle() . ' (' . $event->getStartDate()->format('Y') . ')');
            
            //participants data
            $section = $this->addSection(
                $document,
                [
                    'colsNum'   => 4,
                    'colsSpace' => 100,
                ]
            );
            
            $this->addDatum($section, 'Vorname', $participant->getNameFirst());
            $this->addDatum($section, 'Nachname', $participant->getNameLast());
            $this->addDatum($section, 'Geschlecht', $participant->getGender(true));
            $this->addDatum($section, 'Geburtsdatum', $participant->getBirthday()->format(Event::DATE_FORMAT_DATE));
            
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
                        $columns = 0;
                        $row     = $table->addRow();
                    }
                    
                    $cell     = $row->addCell(10);
                    $codePath = $this->temporaryBarCodeGenerator->createCode(
                        'tel:' .
                        str_replace(' ', '', $this->phoneUtil->format($phoneNumber->getNumber(), 'INTERNATIONAL'))
                    );
                    $cell->addImage(
                        $codePath,
                        [
                            'width'         => 30,
                            'height'        => 30,
                            'marginTop'     => -1,
                            'marginLeft'    => -1,
                            'wrappingStyle' => 'square',
                        ]
                    );
                    $cell    = $row->addCell(null, ['rightFromText' => 10]);
                    $textrun = $cell->addTextRun();
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
                    'Keine Anmerkungen gespeichert.', self::STYLE_FONT_DESCRIPTION, self::STYLE_PARAGRAPH_DESCRIPTION
                );
            } else {
                $this->addCommentsToSection(
                    $this->commentManager->forParticipation($participation), $section, ' zur Anmeldung'
                );
                $this->addCommentsToSection(
                    $this->commentManager->forParticipant($participant),
                    $section,
                    $participant->getGender() ===
                    Participant::TYPE_GENDER_FEMALE ? ' zur Teilnehmerin' : 'zum Teilnehmer'
                );
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