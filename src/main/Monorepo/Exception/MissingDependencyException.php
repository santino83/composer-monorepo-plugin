<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 19/07/19
 * Time: 15.42
 */

namespace Monorepo\Exception;


use RuntimeException;
use Throwable;

class MissingDependencyException extends RuntimeException
{
    const DEFAULT_MESSAGE = 'Unresolved dependencies found';

    /**
     * @var array $orphanName => $missing dependencies
     */
    private $orphaned;

    /**
     * @inheritDoc
     */
    public function __construct( array $orphaned, $message = '', $code = null, Throwable $previous = null)
    {
        parent::__construct($message ? $message : self::DEFAULT_MESSAGE, $code, $previous);

        $this->orphaned = $orphaned;
    }

    /**
     * @return array $orphanName => $missing dependencies
     */
    public function getOrphaned()
    {
        return $this->orphaned;
    }

}