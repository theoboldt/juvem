<?php
namespace AppBundle\BitMask;


class LabelFormatter
{
    /**
     * Contains the list of types the current formatter is aware of
     *
     * @var array
     */
    protected $availableTypeList
        = array('default',
                'primary',
                'success',
                'info',
                'warning',
                'danger'
        );

    /**
     * Contains the sprintf label template
     *
     * @var string
     */
    protected $labelTemplate = '<span class="label label-%s option-%d">%s</span>';

    /**
     * Contains the default type of formatted labels
     *
     * @var string
     */
    protected $typeDefault = 'primary';

    /**
     * Contains the default type of formatted absence labels
     *
     * @var string
     */
    protected $typeAbsenceDefault = 'default';

    /**
     * May contain a list of special attributes formatter
     *
     * @var array
     */
    protected $typeCustomList = array();

    /**
     * May contain a list of special attributes formatter for absence labels
     *
     * @var array
     */
    protected $typeAbsenceCustomList = array();

    /**
     * May contain a list of labels which should be used on absence of configured bitmask values
     *
     * @var array
     */
    protected $absenceLabelList = array();

    /**
     * Format all options of a complete bitmask
     *
     * @param BitMaskAbstract $bitMask Bitmask to iterate
     * @param string|null     $glue Glue for concatenation of every single (absence) label; Set to null
     *                              to get the labels separated as array
     * @return array|string         All labels as one string or all labels as array separate values
     */
    public function formatMask(BitMaskAbstract $bitMask, $glue = ' ')
    {
        $labelList = array();
        foreach ($bitMask->options() as $option) {
            $value = $this->formatOption($bitMask, $option);
            if ($value) {
                $labelList[] = $value;
            }
        }

        if ($glue === null) {
            return $labelList;
        }
        return implode($glue, $labelList);
    }

    /**
     * Format an option
     *
     * @param BitMaskAbstract $bitMask Bitmask where the option belongs to
     * @param integer         $option Option
     * @return string                   Label html
     */
    public function formatOption(BitMaskAbstract $bitMask, $option)
    {
        if ($bitMask->has($option)) {
            return sprintf($this->labelTemplate, $this->typeForOption($option), $option, $bitMask->label($option));
        } elseif ($this->hasAbsenceLabel($option)) {
            return sprintf($this->labelTemplate, $this->typeForAbsenceOption($option), $option, $this->absenceLabel($option));
        }
        return '';
    }


    /**
     * Get the type for the transmitted option
     *
     * @param integer $option
     * @return string
     */
    protected function typeForOption($option)
    {
        if (isset($this->typeCustomList[$option])) {
            return $this->typeCustomList[$option];
        }
        return $this->typeDefault;
    }

    /**
     * Get the type for the transmitted absence option
     *
     * @param integer $option
     * @return string
     */
    protected function typeForAbsenceOption($option)
    {
        if (isset($this->typeAbsenceCustomList[$option])) {
            return $this->typeAbsenceCustomList[$option];
        }
        return $this->typeAbsenceDefault;
    }

    /**
     * Check if there is an absence label for transmitted option available
     *
     * @param integer $option
     * @return bool
     */
    protected function hasAbsenceLabel($option)
    {
        return isset($this->absenceLabelList[$option]);
    }

    /**
     * Get the absence label for transmitted option
     *
     * @param integer $option
     * @return bool
     */
    protected function absenceLabel($option)
    {
        return $this->absenceLabelList[$option];
    }

    /**
     * Add a custom type formatter definition
     *
     * @param integer $option
     * @param string  $type
     * @return self
     */
    public function addCustomType($option, $type)
    {
        $this->isTypeAvailable($type, true);
        if (isset($this->typeCustomList[$option])) {
            throw new \InvalidArgumentException('There is already a custom type for this option configured');
        }
        $this->typeCustomList[$option] = $type;
        return $this;
    }

    /**
     * Add an absence label formatter configuration
     *
     * @param integer $option
     * @param string  $label
     * @param string  $type
     * @return self
     */
    public function addAbsenceLabel($option, $label, $type = null)
    {
        if (isset($this->absenceLabelList[$option])) {
            throw new \InvalidArgumentException('There is already a absence label for transmitted option configured');
        }
        $this->absenceLabelList[$option] = $label;

        if ($type) {
            $this->isTypeAvailable($type, true);
            if (isset($this->typeCustomList[$option])) {
                throw new \InvalidArgumentException(
                    'There is already a custom absence label type for transmitted option configured'
                );
            }
            $this->typeAbsenceCustomList = $type;
        }
        return $this;
    }

    /**
     * Check if transmitted type is available
     *
     * @param  string     $type Type to check
     * @param  bool|false $throw If enabled throws exception if transmitted type is unavailable
     * @return bool
     * @throws \InvalidArgumentException   If Transmitted type is not available
     */
    public function isTypeAvailable($type, $throw = false)
    {
        $isAvailable = in_array($type, $this->availableTypeList);

        if ($throw && !$isAvailable) {
            throw new \InvalidArgumentException('Transmitted type is not available');
        }
        return $isAvailable;
    }
}