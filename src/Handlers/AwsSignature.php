<?php

namespace LaravelHunt\Handlers;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use Aws\Signature\SignatureV4;
use Elasticsearch\ClientBuilder;
use Aws\Credentials\Credentials;
use Psr\Http\Message\RequestInterface;

class AwsSignature
{
    private $signer;
    private $config = [];
    private $wrappedHandler;

    /**
     * An AWS Signature V4 signing handler for use the Amazon Elasticsearch Service.
     *
     * @paramn array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->signer = new SignatureV4('es', Arr::get($config, 'region', 'us-east-1'));

        $this->wrappedHandler = ClientBuilder::defaultHandler();
    }

    public function __invoke(array $request)
    {
        $creds = new Credentials(
            Arr::get($this->config, 'key'),
            Arr::get($this->config, 'secret')
        );

        $psr7Request = $this->createPsr7Request($request);
        $signedRequest = $this->signer
            ->signRequest($psr7Request, $creds);

        return call_user_func($this->wrappedHandler, array_replace(
            $request,
            $this->createRingRequest($signedRequest)
        ));
    }

    private function createPsr7Request(array $ringPhpRequest)
    {
        // Amazon ES listens on standard ports (443 for HTTPS, 80 for HTTP).
        // Consequently, the port should be stripped from the host header.
        $ringPhpRequest['headers']['host'][0]
            = parse_url($ringPhpRequest['headers']['host'][0])['host'];

        // Create a PSR-7 URI from the array passed to the handler
        $uri = (new Uri($ringPhpRequest['uri']))
            ->withScheme($ringPhpRequest['scheme'])
            ->withHost($ringPhpRequest['headers']['host'][0]);

        if (isset($ringPhpRequest['query_string'])) {
            $uri = $uri->withQuery($ringPhpRequest['query_string']);
        }

        // Create a PSR-7 request from the array passed to the handler
        return new Request(
            $ringPhpRequest['http_method'],
            $uri,
            $ringPhpRequest['headers'],
            $ringPhpRequest['body']
        );
    }

    private function createRingRequest(RequestInterface $request)
    {
        $uri = $request->getUri();

        $ringRequest = [
            'http_method' => $request->getMethod(),
            'scheme' => $uri->getScheme(),
            'uri' => $uri->getPath(),
            'body' => (string)$request->getBody(),
            'headers' => $request->getHeaders(),
        ];

        if ($uri->getQuery()) {
            $ringRequest['query_string'] = $uri->getQuery();
        }

        return $ringRequest;
    }
}