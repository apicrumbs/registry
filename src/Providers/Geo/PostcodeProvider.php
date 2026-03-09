<?php

namespace ApiCrumbs\Providers\Geo;

use ApiCrumbs\Core\Contracts\ProviderInterface;
use GuzzleHttp\Client;

class PostcodeProvider implements ProviderInterface
{
    private Client $client;

    public function __construct(?Client $client = null, $verify = false)
    {
        $this->client = $client ?? new Client([
            'verify' => $verify ? $verify : false,
            'base_uri' => 'https://api.postcodes.io',
            'timeout'  => 2.0,
        ]);
    }

    public function getName(): string
    {
        return 'uk_postcode_context';
    }
    
    public function getDependencies(): array 
    { 
        return []; 
    }

    public function fetchData(string $id, array $context = []): array
    {
        // Make sure we remove any spaces first
        $id = str_replace(' ', '', $id);
        
        try {
            $response = $this->client->get("/postcodes/" . urlencode($id));
            $body = json_decode($response->getBody()->getContents(), true);

            if (($body['status'] ?? 0) !== 200) return [];

            $res = $body['result'];
            return [
                'postcode'        => $res['postcode'],
                'admin_district'  => $res['admin_district'],
                'region'          => $res['region'],
                'longitude'       => $res['longitude'],
                'latitude'        => $res['latitude'],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getMetadata(): array
    {
        return [
            'source_url'  => 'https://postcodes.io',
            'reliability' => 'High',
            'tier'        => 'Free'
        ];
    }
}