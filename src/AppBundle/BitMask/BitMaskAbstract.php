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
     * Get all options this bitmask provides
     *
     * @param bool $withLabel Set to true to include label texts
     * @return array
     */
    public function options($withLabel = false)
    {
        $result = array();
        foreach ($this->options as $option) {
            $result[] = $option;
            if ($withLabel) {
                $result[$option] = $this->labels[$option];
            }
        }

        return $result;
    }

    /**
     * Check if the bitmasks value has the transmitted option set
     *
     * @param integer $option
     * @return bool
     */
    public function has($option)
    {
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
        if (!isset($this->labels[$option]) && !array_key_exists($option, $this->labels)) {
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

}