<?php

namespace Bangpound\Bloomerang\Api;

use League\OpenAPIValidation\PSR7\RequestValidator;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use Nyholm\Psr7\Request;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

class OpenApiHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait, HttpClientTrait {
        DecoratorTrait::withOptions insteadof HttpClientTrait;
    }

    public function __construct(
        ?HttpClientInterface               $client = null,
        private readonly ?RequestValidator $requestValidator = null,
        private readonly ?ResponseValidator $responseValidator = null,
        private readonly ?string           $baseUri = null,
        private readonly ?string           $apiToken = null,
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->requestValidator) {
            $url = self::parseUrl($url, $options['query'] ?? []);

            if (\is_string($options['base_uri'] ?? null)) {
                $options['base_uri'] = self::parseUrl($options['base_uri']);
            }

            try {
                $url = implode('', self::resolveUrl($url, $options['base_uri'] ?? null));
            } catch (InvalidArgumentException $e) {
                $defaultOptions = [
                    'base_uri' => $this->baseUri,
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-API-KEY' => $this->apiToken,
                    ]
                ];
                $options = self::mergeDefaultOptions($options, $defaultOptions, true);
                if (\is_string($options['base_uri'] ?? null)) {
                    $options['base_uri'] = self::parseUrl($options['base_uri']);
                }
                $url = implode('', self::resolveUrl($url, $options['base_uri'] ?? null, $defaultOptions['query'] ?? []));
            }
            $request = new Request($method, $url);
            $op = $this->requestValidator->validate($request);
        }
        return $this->client->request($method, $url, $options);
    }
}
