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
    protected $referenceChain;

    /**
     * Container entry identifier.
     *
     * @var string
     */
    protected $serviceId;

    /**
     * CircularReferenceException constructor.
     *
     * @param string $entryId
     * @param array $referenceChain
     * @param Exception|null $previous
     */
    public function __construct(string $entryId, array $referenceChain = [], Exception $previous = null)
    {
        $referenceChain[] = $this->serviceId = $entryId;
        $this->referenceChain = $referenceChain;
        parent::__construct($this->createMessage(), 0, $previous);
    }

    /**
     * Get reference chain.
     *
     * @return array
     */
    public function getReferenceChain(): array
    {
        return $this->referenceChain;
    }

    /**
     * Get container entry identifier which caused a circular reference error.
     *
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    /**
     * Returns exception message
     *
     * @return string
     */
    abstract protected function createMessage(): string;

}