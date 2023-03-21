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
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Export\Sheet\Column\CustomFieldColumn;
use AppBundle\Export\Sheet\Column\EntityAttributeColumn;
use AppBundle\Export\Sheet\Column\EntityPhoneNumberSheetAttributeColumn;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesSheet extends AbstractSheet
{
    
    /**
     * The event this participant export belongs to
     *
     * @var Event
     */
    protected $event;
    
    /**
     * Stores list of {@see Employee} entities
     *
     * @var array|Employee[]
     */
    protected $employees;
    
    
    /**
     * {@inheritdoc}
     */
    public function __construct(Worksheet $sheet, Event $event, array $employees)
    {
        $this->event     = $event;
        $this->employees = $employees;
        
        parent::__construct($sheet);
        
        $this->addColumn(new EntityAttributeColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntityAttributeColumn('nameLast', 'Nachname'));
        
        $column = new EntityAttributeColumn('addressStreet', 'Anschrift');
        $column->setConverter(
            function ($value, Employee $entity) {
                return sprintf(
                    '%s, %s %s',
                    $entity->getAddressStreet(),
                    $entity->getAddressZip(),
                    $entity->getAddressCity()
                );
            }
        );
        $this->addColumn($column);
        
        $column = EntityPhoneNumberSheetAttributeColumn::createCommaSeparated(
            'phoneNumbers',
            'Telefonnummern',
            null,
            false,
            false
        );
        $this->addColumn($column);
    
        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, false, true, true, true) as $attribute) {
            $column = new CustomFieldColumn(
                'custom_field_' . $attribute->getBid(), $attribute->getManagementTitle(), $attribute, [], null
            );
            $this->addColumn($column);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function setHeader(string $title = null, string $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Mitarbeitende');
        parent::setColumnHeaders();
    }
    
    /**
     * {@inheritdoc}
     */
    public function setBody()
    {
        /** @var Employee $employee */
        foreach ($this->employees as $employee) {
            $row = $this->row();
            
            /** @var EntityAttributeColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);
                
                $column->process($this->sheet, $row, $employee);
                
                $cellStyle->getAlignment()->setVertical(
                    Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            }
        }
    }
}
