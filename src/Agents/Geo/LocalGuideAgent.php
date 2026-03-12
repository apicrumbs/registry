<?php

namespace ApiCrumbs\Agents\Geo;

use ApiCrumbs\Core\Contracts\BaseAgent;

/**
 * LocalGuideAgent - Community Tier
 * Specialises in neighborhood summaries using open spatial and weather data.
 */
class LocalGuideAgent extends BaseAgent
{
    public function getName(): string 
    { 
        return 'local_guide'; 
    }

    /**
     * The Data Manifest:
     * This agent automatically summons the Postcode and Weather providers.
     */
    public function getRequiredCrumbs(): array
    {
        return [
            'geo/postcode-io', 
            'weather/open-meteo'
        ];
    }

    /**
     * The Persona:
     * Defines the LLM's behavioral boundaries and expertise.
     */
    public function getSystemInstructions(): array
    {
        return [
            'role'      => 'Expert Local Concierge',
            'objective' => 'Provide a helpful summary of a specific neighborhood based on spatial and weather data.',
            'tone'      => 'Welcoming, informative, and concise.'
        ];
    }
}