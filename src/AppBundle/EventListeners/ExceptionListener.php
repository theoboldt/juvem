<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;

use AppBundle\InvalidTokenHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param null|LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

	/**
	 * @param GetResponseForExceptionEvent $event
	 */
	public function onKernelException(GetResponseForExceptionEvent $event) {
		if ($this->logger === null) {
			return;
		}
		$exception = $event->getException();
		if ($exception instanceof InvalidTokenHttpException) {
			return; //do not log token exceptions
		}

		$flattenException = FlattenException::create($exception);
		$this->logger->error('Stack trace');
		foreach ($flattenException->getTrace() as $trace) {
			$traceMessage = sprintf('  at %s line %s', $trace['file'], $trace['line']);
			$this->logger->error($traceMessage);
		}
	}
}