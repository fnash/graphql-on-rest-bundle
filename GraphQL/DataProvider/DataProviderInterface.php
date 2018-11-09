<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\DataProvider;

interface DataProviderInterface
{
    /**
     * Should return the decoded JSON-LD response in an array.
     *
     * @param string $urlPath
     * @param array  $queryParams
     *
     * @return array
     */
    public function getRawData(string $urlPath, array $queryParams = []): array;

    /**
     * Should return hydra:member data.
     *
     * @param string $urlPath
     * @param array  $queryParams
     *
     * @return array
     */
    public function getResourceCollection(string $urlPath, array $queryParams = []): array;

    /**
     * @return string
     */
    public function getResourceCollectionFieldName(): string;

    /**
     * @return string
     */
    public function getTotalItemsFieldName(): string;

    /**
     * @param string $iri
     *
     * @return array
     */
    public function getIri(string $iri, $context = null): array;

    /**
     * @param array $iris
     *
     * @return array[]
     */
    public function getIris(array $iris, $context = null): array;
}
