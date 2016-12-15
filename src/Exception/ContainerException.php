<?php declare(strict_types = 1);

namespace Venta\Container\Exception;

use Exception;
use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;
use RuntimeException;

/**
 * Class ContainerException
 *
 * @package Venta\Container\Exception
 */
abstract class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
    /**
     * List of entries referred to cause a circular reference.
     *
     * @var array
     */
    private $referenceChain;

    /**
     * Container entry identifier.
     *
     * @var string
     */
    private $serviceId;

    /**
     * CircularReferenceException constructor.
     *
     * @param string $entryId
     * @param array $referenceChain
     * @param Exception|null $previous
     * @internal param string $message
     */
    public function __construct(string $entryId, array $referenceChain = [], Exception $previous = null)
    {
        $this->serviceId = $entryId;
        $this->referenceChain = $referenceChain;
        parent::__construct($this->createMessage($previous), 0, $previous);
    }

    /**
     * Get reference chain.
     *
     * @return array
     */
    public function referenceChain(): array
    {
        return $this->referenceChain;
    }

    /**
     * Get container entry identifier which caused a circular reference error.
     *
     * @return string
     */
    public function serviceId(): string
    {
        return $this->serviceId;
    }

    /**
     * Adds service Ids to the reference chain.
     *
     * @param string[] ...$serviceId
     */
    protected function addToReferenceChain(string ...$serviceId)
    {
        $this->referenceChain = array_merge($this->referenceChain, $serviceId);
    }

    /**
     * Returns exception message
     *
     * @param Exception $previous
     * @return string
     */
    abstract protected function createMessage(Exception $previous = null): string;

}