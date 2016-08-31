<?php declare(strict_types = 1);

namespace Abava\Container\Exception;

use Exception;

/**
 * Class CircularReferenceException
 *
 * @package Abava\Container\Exception
 */
class CircularReferenceException extends ContainerException
{
    /**
     * Container entry identifier.
     *
     * @var string
     */
    private $entryId;

    /**
     * List of entries referred to cause a circular reference.
     *
     * @var array
     */
    private $referenceChain;

    /**
     * CircularReferenceException constructor.
     *
     * @param string $entryId
     * @param array $resolutionChain
     * @param Exception|null $previous
     */
    public function __construct(string $entryId, array $resolutionChain, Exception $previous = null)
    {
        $resolutionChain[] = $entryId;
        parent::__construct(
            sprintf('Circular reference detected for "%s", path: "%s".',
                $entryId, implode(' -> ', $resolutionChain)
            ),
            0,
            $previous
        );

        $this->entryId = $entryId;
        $this->referenceChain = $resolutionChain;
    }

    /**
     * Get container entry identifier which caused a circular reference error.
     *
     * @return string
     */
    public function getEntryId(): string
    {
        return $this->entryId;
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
}