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


class PaymentSuggestionList implements \Countable, \IteratorAggregate
{

    /**
     * Inner value
     *
     * @var array|PaymentSuggestion[]
     */
    private $suggestions = [];

    /**
     * Add a suggestion to list
     *
     * @param PaymentSuggestion $suggestion New suggestion
     */
    public function add(PaymentSuggestion $suggestion)
    {
        foreach ($this->suggestions as $givenSuggestion) {
            if ($givenSuggestion->isSame($suggestion)) {
                $givenSuggestion->merge($suggestion);
                return;
            }
        }
        $this->suggestions[] = $suggestion;
    }

    /**
     * Sort data
     *
     * @return void
     */
    public function sort()
    {
        usort(
            $this->suggestions, function (PaymentSuggestion $a, PaymentSuggestion $b) {
            if ($a->getCount() == $b->getCount()) {
                return 0;
            }
            return ($a->getCount() < $b->getCount()) ? -1 : 1;
        }
        );
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->suggestions);
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): \Traversable
    {
        $this->sort();
        return new \ArrayIterator($this->suggestions);
    }
}
