<?php

namespace AppBundle\BitMask;

abstract class BitMaskAbstract
{

    /**
     * Contains the options the bitmask is able to store as array
     *
     * @var array
     */
    protected $options;

    /**
     * Contains the labels of every bitmask option
     *
     * @var array
     */
    protected $labels;

    /**
     * Contains the value stored as sum
     *
     * @var integer
     */
    protected $value;

    /**
     * Constructor
     *
     * @param int $value Initial value of bitmask
     */
    public function __construct($value = 0)
    {
        $this->value = (int)$value;
    }

    /**
     * Check if the bitmasks value has the transmitted option set
     *
     * @param integer $option
     * @return bool
     */
    public function has($option)
    {
        $this->ensureConstantsParsed();
        if (!in_array($option, $this->options)) {
            throw new \InvalidArgumentException('Unknown option identifier transmitted');
        }
        return ($this->value & $option);
    }

    /**
     * Get the label for an option
     *
     * @param $option
     * @return mixed
     */
    public function label($option)
    {
        $this->ensureConstantsParsed();
        if (!isset($this->labels[$option])) {
            throw new \InvalidArgumentException('Unknown option identifier transmitted');
        }
        return $this->labels[$option];
    }

    /**
     * Enable a bitmask option (fluent)
     *
     * @param integer $option
     * @return self
     */
    public function enable($option)
    {
        if (!$this->has($option)) {
            $this->value += $option;
        }
        return $this;
    }

    /**
     * Disable a bitmask option (fluent)
     *
     * @param integer $option
     * @return self
     */
    public function disable($option)
    {
        if ($this->has($option)) {
            $this->value -= $option;
        }
        return $this;
    }

    /**
     * Toggle a bitmask option (fluent)
     *
     * @param string $option
     * @return self
     */
    public function toggle($option)
    {
        if ($this->has($option)) {
            $this->disable($option);
        } else {
            $this->enable($option);
        }
        return $this;
    }

    /**
     * Directly get bitmask sum as number
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Directly set bitmask sum as number
     *
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Raw value
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * Get all options this bitmask provides
     *
     * @see parseConstants()
     * @return array
     */
    public function options()
    {
        $this->ensureConstantsParsed();
        return $this->options;
    }

    /**
     * Get labels of all options this bitmask provides
     *
     * @see parseConstants()
     * @return array
     */
    public function labels()
    {
        $this->ensureConstantsParsed();
        return $this->labels;
    }

    /**
     * Ensure that the constants are parsed and parse dem if not
     */
    protected function ensureConstantsParsed()
    {
        if ($this->labels !== null) {
            return;
        }
        $this->options = array();
        $this->labels  = array();

        $mask      = new \ReflectionClass(get_called_class());
        $constants = $mask->getConstants();

        $typeList  = array();
        $labelList = array();

        $regex = '/(TYPE|LABEL)_(.*)/';

        foreach ($constants as $constantName => $constantValue) {
            if (preg_match($regex, $constantName, $constantDetails)) {
                if ($constantDetails[1] == 'TYPE') {
                    $typeList[$constantDetails[2]] = $constantValue;
                } else {
                    $labelList[$constantDetails[2]] = $constantValue;
                }
            }
        }
        foreach ($typeList as $type => $value) {
            $this->options[] = $value;
            if (isset($labelList[$type])) {
                $this->labels[$value] = $labelList[$type];
            }
        }
    }
}