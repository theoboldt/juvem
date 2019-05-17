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


use AppBundle\Entity\AcquisitionAttribute\Fillout;
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
use PhpOffice\PhpWord\Style\Language;

class ParticipantProfile
{
    const STYLE_FONT_DESCRIPTION      = 'DescriptionF';
    const STYLE_PARAGRAPH_DESCRIPTION = 'DescriptionP';
    
    const STYLE_FONT_NONE = 'NoneF';
    
    const STYLE_PARAGRAPH_COMMENT = 'CommentP';
    
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
     * ParticipantProfile constructor.
     *
     * @param Participant[]|array $participants
     * @param PhoneNumberUtil $phoneUtil
     * @param CommentManager $commentManager
     * @param string|null $logoPath
     */
    public function __construct(
        array $participants, PhoneNumberUtil $phoneUtil, CommentManager $commentManager, ?string $logoPath = null
    )
    {
        $this->participants   = $participants;
        $this->phoneUtil      = $phoneUtil;
        $this->commentManager = $commentManager;
        $this->logoPath       = $logoPath;
        $this->document       = $this->prepareDocument();
    }
    
    /**
     * Prepare php document, add style definitions etc
     *
     * @return PhpWord
     */
    private function prepareDocument(): PhpWord
    {
        Settings::setOutputEscapingEnabled(true);
        $document = new PhpWord();
        $language = new Language(Language::DE_DE);
        $settings = $document->getSettings();
        $document->getDocInfo();
        $settings->setThemeFontLang($language);
        
        $document->addTitleStyle(2, ['size' => 13], ['spaceBefore' => 0]);
        $document->addTitleStyle(
            3, ['size' => 9, 'smallCaps' => true, 'color' => '222222'],
            ['spaceBefore' => 150, 'spaceAfter' => 0, 'keepNext' => true]
        );
        
        $document->addTitleStyle(
            4, ['size' => 8, 'bold' => true], ['spaceBefore' => 100, 'spaceAfter' => 0, 'keepNext' => true]
        );
        
        $document->setDefaultFontSize(8);
        $defaultParagraphStyle = ['spaceBefore'  => 0, 'spaceAfter' => 100, 'marginLeft' => 100, 'marginRight' => 600,
                                  'widowControl' => false,
        ];
        $document->setDefaultParagraphStyle($defaultParagraphStyle);
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_COMMENT, array_merge($defaultParagraphStyle, ['keepNext' => true])
        );
        
        $document->addParagraphStyle(
            self::STYLE_PARAGRAPH_DESCRIPTION,
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'keepNext' => true, 'marginLeft' => 400, 'marginRight' => 600]
        );
        $document->addFontStyle(self::STYLE_FONT_DESCRIPTION, ['size' => 7, 'color' => '333333']);
        
        $document->addFontStyle(self::STYLE_FONT_NONE, ['size' => 8, 'color' => '666666', 'italic' => true]);
        
        
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
     * @param array $participants
     * @return Event
     */
    private function getEvent(array $participants)
    {
        /** @var Participant $participant */
        $participant = reset($participants);
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
            $title       = $fillout->getAttribute()->getManagementTitle();
            $description = $fillout->getAttribute()->getManagementDescription();
            
            $section->addTitle($title, 4);
            if ($description !== $title) {
                $section->addText($description, self::STYLE_FONT_DESCRIPTION, self::STYLE_PARAGRAPH_DESCRIPTION);
            }
            $section->addText($fillout->getValue()->getTextualValue());
        }
    }
    
    /**
     * Generate document, provide export file path
     */
    public function generate()
    {
        $participants = $this->participants;
        $document     = $this->prepareDocument();
        $event        = $this->getEvent($participants);
        
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
            $section->addTitle('Vorname', 4);
            $section->addText($participant->getNameFirst());
            
            $section->addTitle('Nachname', 4);
            $section->addText($participant->getNameLast());
            
            $section->addTitle('Geschlecht', 4);
            $section->addText($participant->getGender(true));
            
            $section->addTitle('Geburtsdatum', 4);
            $section->addText($participant->getBirthday()->format(Event::DATE_FORMAT_DATE));
            
            $section = $this->addSection(
                $document,
                [
                    'colsNum'   => $this->columns,
                    'colsSpace' => 100,
                ]
            );
            $section->addTitle('Medizinische Hinweise', 4);
            $section->addText($participant->getInfoMedical());
            
            $section->addTitle('Allgemeine Hinweise', 4);
            $section->addText($participant->getInfoGeneral());
            
            $section->addTitle('Ernährung', 4);
            $section->addText(implode(', ', $participant->getFood(true)->getActiveList(true)));
            
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
            $section->addTitle('Anschrift (Eltern)', 4);
            $addressCountry = ($participation->getAddressCountry() !== Event::DEFAULT_COUNTRY)
                ? "\n" . $participation->getAddressCountry() : '';
            $section->addText(
                $participation->getSalutation() . ' ' . $participation->fullname() . "\n"
                . $participation->getAddressStreet() . "\n"
                . $participation->getAddressCity() . ' ' . $participation->getAddressZip()
                . $addressCountry
            );
            $section->addTitle('E-Mail Adresse', 4);
            $section->addLink('mailto:' . $participation->getEmail(), $participation->getEmail());
            
            $section->addTitle('Eingang', 4);
            $section->addText($participant->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME));
            
            $this->addFilloutsToSection($participation->getAcquisitionAttributeFillouts()->toArray(), $section);
            
            $section = $this->addSection($document);
            $section->addTitle('Telefonnummern', 3);
            /** @var PhoneNumber $phoneNumber */
            foreach ($participation->getPhoneNumbers() as $phoneNumber) {
                $textrun = $section->addTextRun();
                $textrun->addText($this->phoneUtil->format($phoneNumber->getNumber(), 'INTERNATIONAL'));
                
                if ($phoneNumber->getDescription()) {
                    $textrun->addTextBreak();
                    $textrun->addText($phoneNumber->getDescription(), ['italic' => true]);
                }
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
    
    private function addCommentsToSection(array $comments, Section $section, string $commentTarget)
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
    
    
    public function cleanup()
    {
    
    }
}