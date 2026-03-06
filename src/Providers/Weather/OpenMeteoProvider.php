<?php

namespace ApiCrumbs\Providers\Weather;

use ApiCrumbs\Core\Contracts\ProviderInterface;
use GuzzleHttp\Client;

class OpenMeteoProvider implements ProviderInterface
{
    private Client $client;

    /**
     * Constructor injection allows the Registry to pass 
     * a pre-configured Guzzle client during boot.
     */
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.open-meteo.com',
            'timeout'  => 3.0,
        ]);
    }

    public function getName(): string
    {
        return 'local_weather_forecast';
    }

    /**
     * @param string $id Format: "latitude,longitude"
     */
    public function fetchData(string $id): array
    {
        if (!str_contains($id, ',')) return [];

        [$lat, $lon] = explode(',', $id);

        try {
            $response = $this->client->get("/v1/forecast", [
                'query' => [
                    'latitude' => trim($lat),
                    'longitude' => trim($lon),
                    'current_weather' => 'true',
                    'timezone' => 'auto'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'temperature' => ($data['current_weather']['temperature'] ?? 'N/A') . '°C',
                'wind_speed'  => ($data['current_weather']['windspeed'] ?? 'N/A') . ' km/h',
                'time_utc'    => $data['current_weather']['time'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getMetadata(): array
    {
        return [
            'source_url'  => 'https://open-meteo.com',
            'reliability' => 'High',
            'tier'        => 'Free'
        ];
    }
}