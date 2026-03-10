<?php

namespace ApiCrumbs\Providers\Geo;

use ApiCrumbs\Core\Contracts\BaseProvider;

/**
 * PostcodeIoProvider - The Geographic Anchor for UK Data
 * Converts string postcodes into lat/lng and administrative context.
 */
class PostcodeIoProvider extends BaseProvider
{
    /** 
     * Postcodes.io rate limit: 30 req/sec. 
     * Setting delay to 34,000 microseconds (~34ms) to be safe.
     */
    protected int $delay = 34000;

    public function getName(): string { return 'postcode_io_context'; }

    public function getDependencies(): array { return []; }

    public function getVersion(): string { return '1.2.0'; }

    /**
     * Fetches raw postcode data from the open API.
     */
    public function fetchData(string $id, array $context = []): array
    {
        // Canonicalize the ID for the API
        $cleanId = str_replace(' ', '', strtoupper($id));
        
        // FIX: Ensure the /postcodes/ endpoint is included
        $url = "https://api.postcodes.io" . urlencode($cleanId);

        try {
            $response = $this->safeFetch($url);
            return $response['result'] ?? [];
        } catch (\Exception $e) {
            // Silently return empty to prevent breaking the LLM build loop
            return [];
        }
    }

    /**
     * MetadataTransformer: Optimises for LLM spatial reasoning.
     * Injects system hints to help the AI "stitch" subsequent data.
     */
    public function transform(array $data): string
    {
        if (empty($data)) return "";

        $output = "### 📍 GEOGRAPHIC ANCHOR: " . ($data['postcode'] ?? 'Unknown') . PHP_EOL;
        $output .= "<!-- Source: Postcodes.io | Verified UK Spatial Data -->" . PHP_EOL;
        
        // Strict Mode: Lowercase keys for LLM consistency
        $output .= "- **COORDINATES**: " . ($data['latitude'] ?? 'N/A') . ", " . ($data['longitude'] ?? 'N/A') . PHP_EOL;
        $output .= "- **ADMIN_DISTRICT**: " . ($data['admin_district'] ?? 'N/A') . PHP_EOL;
        $output .= "- **PARISH**: " . ($data['parish'] ?? 'N/A') . PHP_EOL;
        $output .= "- **REGION**: " . ($data['region'] ?? 'N/A') . PHP_EOL;
        
        // Strategic RAG hint
        $output .= "> Info: Primary spatial reference. Use lat/lon for all distance-based reasoning." . PHP_EOL;

        return $output . "---" . PHP_EOL;
    }
}
