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

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionLanguageProvider
{

    /**
     * Cache dir path for @see ExpressionLanguage cache
     *
     * @var string
     */
    private $expressionLanguageCachePath;

    /**
     * Lazy initialized @see ExpressionLanguage
     *
     * @var null|ExpressionLanguage
     */
    protected $expressionLanguage = null;

    /**
     * CommentManager constructor.
     *
     * @param string $expressionLanguageCachePath
     */
    public function __construct(
        string $expressionLanguageCachePath
    ) {
        $this->expressionLanguageCachePath = $expressionLanguageCachePath;
    }

    /**
     * Get cached @see ExpressionLanguage
     *
     * @return ExpressionLanguage
     */
    public function provide(): ExpressionLanguage
    {
        if (!$this->expressionLanguage) {
            $cache                    = new FilesystemAdapter('', 0, $this->expressionLanguageCachePath);
            $this->expressionLanguage = new ExpressionLanguage($cache);
        }
        return $this->expressionLanguage;
    }

}
