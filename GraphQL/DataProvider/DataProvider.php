<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\DataProvider;

abstract class DataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    protected $resourceCollectionFieldName = 'hydra:member';

    /**
     * @var string
     */
    protected $totalItemsFieldName = 'hydra:totalItems';

    /**
     * @param string $resourceCollectionFieldName
     *
     * @return DataProvider
     */
    public function setResourceCollectionFieldName(string $resourceCollectionFieldName): DataProvider
    {
        $this->resourceCollectionFieldName = $resourceCollectionFieldName;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceCollectionFieldName(): string
    {
        return $this->resourceCollectionFieldName;
    }

    /**
     * @return string
     */
    public function getTotalItemsFieldName(): string
    {
        return $this->totalItemsFieldName;
    }

    /**
     * @param string $totalItemsFieldName
     *
     * @return DataProvider
     */
    public function setTotalItemsFieldName(string $totalItemsFieldName): DataProvider
    {
        $this->totalItemsFieldName = $totalItemsFieldName;

        return $this;
    }

    /**
     * @{inheritdoc]
     */
    abstract public function getRawData(string $url, array $queryParams = []): array;

    /**
     * @{inheritdoc]
     */
    public function getResourceCollection(string $url, array $queryParams = []): array
    {
        $rawData = $this->getRawData($url, $queryParams);

        return $rawData[$this->resourceCollectionFieldName] ?? [];
    }
}
