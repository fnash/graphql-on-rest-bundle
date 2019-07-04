# GraphqlOnRestBundle
Main goal: Write a graphQL query, it will navigate your (Rest + JSON-LD) server and do the HTTP calls for you.

This Symfony bundle lets you build a GraphQL layer to automate HTTP calls to an API Platform based server (JSON-LD + REST).
It is built on top of webonyx/graphql-php.

Includes
============

- Command to validate graphql schema

```bash
$ bin/console graphql_on_rest:schema:validate
```

- Data profiler to dump GraphQL queries et HTTP requests


Installation
============

Download the bundle
----------------

```bash
    $ composer require fnash/graphql-on-rest-bundle
```

Register in the Kernel
----------------

```php

    <?php

    // app/AppKernel.php

    use Symfony\Component\HttpKernel\Kernel;

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = [
                // ...

                new Fnash\GraphqlOnRestBundle\FnashGraphqlOnRestBundle(),
            ];

            // ...
        }

        // ...
    }
```

Usage
============  
  
Create a REST data provider
-------------------------    

Add a server "my_rest_api" and tell the bundle how you make your http requests to fetch JSON-LD data.
See Configuration.php


```yaml
    
    # app/config/config.yml
    fnash_graphql_on_rest:
        servers:
            my_rest_api:
                data_provider_id: 'Acme\AppBundle\GraphQL\MyDataProvider'
```            
            
```php
<?php

namespace Acme\AppBundle\GraphQL;

use Fnash\GraphqlOnRestBundle\GraphQL\DataProvider\DataProvider;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class MyDataProvider extends DataProvider
{
    /**
     * @var DecoderInterface
     */
    private $serializer;
    
    private $guzzle;

    public function __construct(DecoderInterface $serializer, $guzzle)
    {
        $this->serializer = $serializer;
        $this->guzzle = $guzzle;
    }

    /**
     * @{inheritdoc}
     */
    public function getRawData(string $url, array $queryParams = []): array
    {
        try {
            $data = (string) $this->guzzle->get($url, $queryParams)->getBody();

            return $this->serializer->decode($data, 'json');
        } catch (\Exception $exception) {
            return [];
        }
    }
}
```

Build your GraphQL Schema
----------------------------------

1- Define types for your queries:

```php
<?php

namespace Acme\AppBundle\GraphQL\Type;

use Fnash\GraphqlOnRestBundle\GraphQL\Type\JsonLdObjectType;
use Fnash\GraphqlOnRestBundle\GraphQL\Type\TypeRegistry;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolver;
use GraphQL\Type\Definition\Type;

class ArticleType extends JsonLdObjectType
{
    public function __construct($iriResolver)
    {
        $config = [
            'fields' => function () use ($iriResolver) {
                $fields = [
                    'title' => Type::string(),
                    'body' => Type::string(),
                    'related' => [
                        'type' => Type::listOf(TypeRegistry::get(ArticleType::class)),
                        'resolve' => $iriResolver,
                    ],
                    'tags' => [
                        'type' => Type::listOf(TypeRegistry::get(TagType::class)),
                        'resolve' => $iriResolver,
                    ],
                ];

                return array_merge($fields, TypeRegistry::getInterface(ContentInterfaceType::class)->getFields());
            },
            'resolveField' => TypeResolver::resolveFieldClosure(),
        ];

        parent::__construct($config);
    }
}


namespace Acme\AppBundle\GraphQL\Type;

use Fnash\GraphqlOnRestBundle\GraphQL\Type\JsonLdObjectType;
use GraphQL\Type\Definition\Type;

class TagType extends JsonLdObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function () {
                $fields = [
                    'label' => Type::string(),
                ];

                return array_merge($fields, static::getMetaDataFields());
            },
        ];

        parent::__construct($config);
    }
}

```

2- Create resolvers for your types to fetch data:

```php
namespace Acme\AppBundle\GraphQL\Resolver;

use Fnash\GraphqlOnRestBundle\GraphQL\Type\TypeRegistry;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolver;
use Acme\AppBundle\GraphQL\Type\ArticleType;
use GraphQL\Type\Definition\Type;

class ArticleTypeResolver extends TypeResolver
{
    /**
     * {@inheritdoc}
     */
    public function getQueryFieldConfig(): array
    {
        $type = $this->getType();

        $type->resolveFieldFn = static::resolveFieldClosure();

        $this->configureArguments([
                'id' => Type::listOf(Type::string()),
                'title',
            ]);

        return [
            'type' => Type::listOf($type),
            'resolve' => $this->resolveTypeClosure($this->getEndpoint()),
            'args' => $this->getArgumentsConfig(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): Type
    {
        return TypeRegistry::get(ArticleType::class, [
            $this->resolveIriDeferredClosure(),
        ]);
    }
}


<?php

namespace Acme\AppBundle\GraphQL\Resolver;

use Fnash\GraphqlOnRestBundle\GraphQL\Type\TypeRegistry;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolver;
use Acme\AppBundle\GraphQL\Type\TagType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class TagTypeResolver extends TypeResolver
{
    /**
     * {@inheritdoc}
     */
    public function getQueryFieldConfig(): array
    {
        $type = $this->getType();

        $type->resolveFieldFn = static::resolveFieldClosure();

        return [
            'type' => Type::listOf($type),
            'resolve' => $this->resolveTypeClosure($this->getEndpoint()),
            'args' => $this->getArgumentsConfig(),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getType(): Type
    {
        return TypeRegistry::get(TagType::class);
    }
}

```

- Resolvers must be declared as services and tagged

```yaml
services:
    Acme\AppBundle\GraphQL\Resolver\ArticleTypeResolver:
        parent: 'graphql_on_rest.type_resolver.my_rest_api'
        tags:
            - { name: 'graphql_on_rest.type_resolver', server: 'my_rest_api' }
            
    Acme\AppBundle\GraphQL\Resolver\TagTypeResolver:
        parent: 'graphql_on_rest.type_resolver.my_rest_api'
        tags:
            - { name: 'graphql_on_rest.type_resolver', server: 'my_rest_api' }
```


Execute your queries
----------------------------------   
  
Query 1:
```graphql
{
  article {
    title
    body
  }
}
```

Result:
```graphql
{
  "data": {
    "article": [
      {
        "title": "Nissan rappelle 2 millions de voitures dans le monde",
        "body": "<p><strong>AFP - </strong>Le constructeur automobile japonais Nissan a annoncé jeudi<p>"
      },
      {
        "title": "Vols suspects en série chez les journalistes travaillant sur l'affaire Bettencourt",
        "body": "<p>Des enregistrements réalisés chez Liliane Bettencourt...</p>"
      }
    ]
  },
  "extensions": {
    "http_calls": [
      {
        "url": "/api/articles?limit=2",
        "duration_ms": 355.484
      }
    ],
    "query": {
      "duration_ms": 379,
      "duration_no_http_ms": 6.554
    }
  }
}
```


Query 2:

```graphql
{
  article {
    title
    body
    tags {
      label
    }
  }
}
```

Result:
```graphql
{
  "data": {
    "article": [
      {
        "title": "Nissan rappelle 2 millions de voitures dans le monde",
        "body": "<p><strong>AFP - </strong>Le constructeur automobile japonais Nissan a annoncé jeudi<p>"
        "tags": []
      },
      {
        "title": "Vols suspects en série chez les journalistes travaillant sur l'affaire Bettencourt",
                "body": "<p>Des enregistrements réalisés chez Liliane Bettencourt...</p>"
        "tags": [
          {
            "label": "Justice"
          },
          {
            "label": "France"
          },
          {
            "label": "Affaire Bettencourt"
          },
          {
            "label": "dépêches"
          }
        ]
      }
    ]
  },
  "extensions": {
    "http_calls": [
      {
        "url": "/api/articles?limit=2",
        "duration_ms": 340.168
      },
      {
        "url": "/api/tags?id[0]=8f498ca0-ba33-11e7-add7-02420a050002&id[1]=8f3afcee-ba33-11e7-a697-02420a050002&id[2]=8f1eebee-ba33-11e7-b2fe-02420a050002&id[3]=8a6d60ee-ba33-11e7-9177-02420a050002&limit=4",
        "duration_ms": 91.786
      }
    ],
    "query": {
      "duration_ms": 470,
      "duration_no_http_ms": 18.419
    }
  }
}
```
