<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\DataLoader;

use Fnash\GraphqlOnRestBundle\GraphQL\DataProvider\DataProviderInterface;

class IriDataLoader
{
    /**
     * IDs grouped by endpoint.
     *
     * @var => [$ids]
     */
    private $ids = [];

    /**
     * $iri => $value.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    /**
     * @var string
     */
    private $itemsPerPageParameterName;

    /**
     * @var int
     */
    private $maximumItemsPerPage;

    /**
     * @var string
     */
    private $identifierRegex;

    public function __construct(DataProviderInterface $dataProvider, string $itemsPerPageParameterName, int $maximumItemsPerPage, string $identifierRegex)
    {
        $this->dataProvider = $dataProvider;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->maximumItemsPerPage = $maximumItemsPerPage;
        $this->identifierRegex = $identifierRegex;
    }

    /**
     * @param string $iri
     *
     * @return IriDataLoader
     */
    public function buffer(string $iri, int $depth)
    {
        $id = $this->getIdFromIri($iri);

        $urlPath = $this->getUrlPathFromIri($iri);

        if (!isset($this->ids[$depth]['isLoaded'])) {
            $this->ids[$depth]['isLoaded'] = false;
        }

        if (isset($id, $urlPath)) {
            $this->ids[$depth]['ids'][$urlPath][$id] = $id;
            $this->data[$iri] = null;
        }

        return $this;
    }

    /**
     * loadBuffered.
     */
    public function loadBuffered()
    {
        foreach ($this->ids as $depth => $endpointIds) {
            if (false === $endpointIds['isLoaded']) {
                foreach ($endpointIds['ids'] as $urlPath => $ids) {
                    $pagesCount = (int) ceil(count($ids) / $this->maximumItemsPerPage);

                    for($i = 0; $i < $pagesCount; $i++) {
                        $sliceIds = array_slice($ids, $i * $this->maximumItemsPerPage, $this->maximumItemsPerPage, true);
                        try {
                            //TODO endpoint must be an URL
                            $result = $this->dataProvider->getResourceCollection($urlPath, [
                                'id' => array_keys($sliceIds),
                                $this->itemsPerPageParameterName => count($sliceIds),
                            ]);

                            foreach ($result as $item) {
                                if (array_key_exists('@id', $item)) {
                                    $this->data[$item['@id']] = $item;
                                } else {
                                    throw new \Exception('No @id');
                                    // TODO log errror
                                }
                            }
                        } catch (\Exception $e) {
                            // TODO log error
                            //                        throw $e;
                            continue;
                        }
                    }
                }

                $this->ids[$depth]['isLoaded'] = true;
            }
        }
    }

    /**
     * @param string $iri
     *
     * @return mixed
     */
    public function get(string $iri)
    {
        return $this->data[$iri] ?? null;
    }

    /**
     * @param string $iri
     *
     * @return mixed
     */
    private function getIdFromIri(string $iri)
    {
        if (preg_match(sprintf('/%s/', $this->identifierRegex), $iri, $matches)) {
            if (count($matches)) {
                return $matches[0];
            }
        }
    }

    /**
     * @param string $iri
     *
     * @return mixed
     */
    private function getUrlPathFromIri(string $iri)
    {
        if (preg_match(sprintf('/(\w+)\/%s/', $this->identifierRegex), $iri, $matches)) {
            if (count($matches)) {
                return $matches[1];
            }
        }
    }
}
