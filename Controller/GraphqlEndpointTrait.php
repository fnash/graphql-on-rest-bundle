<?php

namespace Fnash\GraphqlOnRestBundle\Controller;

use Fnash\GraphqlOnRestBundle\GraphQL\GraphQLClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait GraphqlEndpointTrait
{
    public function graphqlAction(Request $request)
    {
        $graphQLClient = $this->container->get(GraphQLClient::class);

        if (Request::METHOD_GET === $request->getMethod()) {
            $defaultquery = <<<'EOD'
{
  __schema {
    types {
      name
      kind
    }
  }
}
EOD;

            if ($request->query->has('schema')) {
                $printedSchema = $graphQLClient->printSchema();

                if ($request->query->has('raw')) {
                    return new Response($printedSchema, 200, ['Content-Type' => 'text/plain']);
                }

                $html = <<<"EOD"
<html>
    <body>
        <pre>
            <code class="language-graphql">\n$printedSchema</code>
        </pre>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.10.0/themes/prism-twilight.min.css" />    
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.10.0/prism.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.10.0/components/prism-graphql.min.js"></script>
    </body>
</html>
EOD;

                return new Response($html);
            }

            $query = $request->get('query', $defaultquery);
        }

        $variables = null;
        if (Request::METHOD_POST === $request->getMethod()) {
            $requestContent = \json_decode((string) $request->getContent(), true);

            $variables = $requestContent['variables'] ?? null;
            $query = $requestContent['query'];
        }

        $result = $graphQLClient->query($query, $variables, null, true);

        return new JsonResponse($result);
    }
}
