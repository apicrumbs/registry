<?php

namespace ApiCrumbs\Providers\Weather;

use ApiCrumbs\Core\Contracts\BaseProvider;

/**
 * OpenMeteoProvider - Hyper-local Weather Context
 * Fetches real-time atmospheric data based on stitched coordinates.
 */
class OpenMeteoProvider extends BaseProvider
{
    public function getName(): string { return 'open_meteo_context'; }

    public function getDependencies(): array { return []; }

    public function getVersion(): string { return '1.0.3'; }

    /**
     * Fetches weather using coordinates from the master context.
     */
    public function fetchData(string $id, array $context = []): array
    {
        // $latLong expected as "51.5074,-0.1278"
        [$lat, $lon] = explode(',', $id);

        if (!$lat || !$lon) {
            // Silently fail if no coordinates are available to keep LLM context clean
            return [];
        }

        $url = "https://api.open-meteo.com/v1/forecast";
        
        try {
            return $this->safeFetch($url, [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current_weather' => true,
                    'timezone' => 'auto'
                ]
            ]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * MetadataTransformer: Converts raw WMO codes into human-readable LLM context.
     */
    public function transform(array $data): string
    {
        if (empty($data['current_weather'])) return "";

        $weather = $data['current_weather'];
        $output = "### 🌦️ LOCAL CLIMATE CONTEXT" . PHP_EOL;
        $output .= "<!-- Source: Open-Meteo | Real-time atmospheric data -->" . PHP_EOL;
        $output .= "- **TEMPERATURE**: " . ($weather['temperature'] ?? 'N/A') . "°C" . PHP_EOL;
        $output .= "- **WIND_SPEED**: " . ($weather['windspeed'] ?? 'N/A') . " km/h" . PHP_EOL;
        $output .= "- **CONDITION_CODE**: WMO " . ($weather['weathercode'] ?? 'N/A') . PHP_EOL;
        
        $output .= "> Info: Weather data is hyper-local to the provided coordinates." . PHP_EOL;

        return $output . "---" . PHP_EOL;
    }
}
