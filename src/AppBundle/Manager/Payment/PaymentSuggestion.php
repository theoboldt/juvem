<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Payment;


class PaymentSuggestion
{

    /**
     * Monetary value
     *
     * @var int
     */
    private $value;

    /**
     * Payment description
     *
     * @var string
     */
    private $description;

    /**
     * Commonness of value
     *
     * @var int
     */
    private $count = 0;

    /**
     * Groups this payment is assigned to
     *
     * @var array|string[]
     */
    private $groups = [];

    /**
     * PaymentSuggestion constructor.
     *
     * @param int    $value
     * @param string $description
     * @param int    $count
     * @param array  $groups
     */
    public function __construct(int $value, string $description, int $count = 1, array $groups = [])
    {
        $this->value       = $value;
        $this->description = $description;
        $this->count       = $count;
        $this->groups      = $groups;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return array|string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Increase count by transmitted value
     *
     * @param int $value
     */
    public function increaseCount(int $value = 1)
    {
        $this->count += $value;
    }

    /**
     * Merge this suggestion with transmitted, increase count and merge groups
     *
     * @param PaymentSuggestion $suggestion
     */
    public function merge(PaymentSuggestion $suggestion) {
        $this->increaseCount($suggestion->getCount());
        $this->groups = array_merge($this->groups, $suggestion->getGroups());
        array_unique($this->groups);
    }

    /**
     * Determine if this @see PaymentSuggestion is same as transmitted @see PaymentSuggestion
     *
     * @param PaymentSuggestion $suggestion Suggestion to check
     * @return bool
     */
    public function isSame(PaymentSuggestion $suggestion)
    {
        return $this->value === $suggestion->getValue() && $this->description === $suggestion->getDescription();
    }

}