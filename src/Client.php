<?php

namespace Bangpound\Bloomerang\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class Client
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function getCustomFieldsByType(string $type, bool $isActive = true): array
    {
        $response = $this->httpClient->request('GET', 'customFields/{type}/{?isActive}', [
            'vars' => [
                'type' => $type,
                'isActive' => $isActive ? 'true' : 'false',
            ]
        ]);
        return $response->toArray();
    }

    public function searchConstituents(string $search, ?string $type = null, ?int $skip = null, ?int $take = null): array
    {
        $vars = $this->buildSearchParams($search, $type, $skip, $take);
        $response = $this->httpClient->request('GET', 'constituents/search/{?search,type,skip,take}', [
            'vars' => $vars,
        ]);
        return $response->toArray();
    }

    private function buildSearchParams(string $searchTerm, ?string $type, ?int $offset, ?int $limit): array
    {
        $params = ['search' => $searchTerm];

        if ($type !== null) {
            $params['type'] = $type;
        }
        if ($offset !== null) {
            $params['skip'] = $offset;
        }
        if ($limit !== null) {
            $params['take'] = $limit;
        }

        return $params;
    }
}
