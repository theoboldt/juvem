<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Export\Sheet;


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Export\AttributeOptionExplanation;
use AppBundle\Export\Sheet\Column\CallableAccessingColumn;
use AppBundle\Export\Sheet\Column\EntityColumn;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExplanationSheet extends AbstractSheet
{

    /**
     * Registered explanations
     *
     * @var array|AttributeOptionExplanation[]
     */
    private $explanations = [];

    /**
     * Related sheet's title
     *
     * @var string
     */
    private $title;

    /**
     * ExplanationSheet constructor.
     *
     * @param Worksheet $sheet
     * @param string    $title
     * @param array     $explanations
     */
    public function __construct(Worksheet $sheet, string $title, array $explanations)
    {
        $this->title        = $title;
        $this->explanations = $explanations;

        $sheet->setTitle('Abkuerzungen');
        parent::__construct($sheet);

        $this->addColumn(
            new CallableAccessingColumn(
                'attribute', 'Feld', function (AttributeChoiceOption $option) {
                return $option->getAttribute()->getManagementTitle();
            }
            )
        );

        $this->addColumn(
            new CallableAccessingColumn(
                'abbreviation', 'Abkürzung', function (AttributeChoiceOption $option) {
                return $option->getShortTitle(true);
            }
            )
        );

        $this->addColumn(
            new CallableAccessingColumn(
                'description', 'Beschreibung', function (AttributeChoiceOption $option) {
                return $option->getManagementTitle(true);
            }
            )
        );

    }

    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {
        parent::setHeader($this->title, 'Abkürzungsverzeichnis');
        parent::setColumnHeaders();
    }

    /**
     * Set the main content of this sheet
     *
     * @return void
     */
    public function setBody()
    {
        /** @var int $previousBid */
        $previousBid = null;

        foreach ($this->explanations as $explanation) {
            /** @var AttributeChoiceOption $choice */
            foreach ($explanation as $choice) {
                $row = $this->row();

                /** @var EntityColumn $column */
                foreach ($this->columnList as $column) {
                    $columnIndex = $column->getColumnIndex();
                    $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                    $column->process($this->sheet, $row, $choice);

                    $columnStyles = $column->getDataStyleCallbacks();
                    if (count($columnStyles)) {
                        foreach ($columnStyles as $columnStyle) {
                            if (!is_callable($columnStyle)) {
                                throw new \InvalidArgumentException('Defined column style callback is not callable');
                            }
                            $columnStyle($cellStyle);
                        }
                    }
                    if ($previousBid !== null && $choice->getAttribute()->getBid() !== $previousBid) {
                        $cellStyle->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    }
                }

                $previousBid = $choice->getAttribute()->getBid();

            }
        }

    }
}
