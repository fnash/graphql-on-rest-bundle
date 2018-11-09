<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\DataProvider;

abstract class DataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    protected $resourceCollectionFieldName = 'hydra:member';

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
     * {@inheritdoc}
     */
    abstract public function getRawData(string $url, array $queryParams = []): array;

    /**
     * {@inheritdoc}
     */
    public function getResourceCollection(string $url, array $queryParams = []): array
    {
        $rawData = $this->getRawData($url, $queryParams);

        return $rawData[$this->resourceCollectionFieldName] ?? [];
    }
}
