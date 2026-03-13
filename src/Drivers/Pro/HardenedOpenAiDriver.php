<?php

namespace ApiCrumbs\Drivers\Pro;

use ApiCrumbs\Core\Contracts\BaseAgentDriver;

/**
 * OllamaDriver - Private & Local Transport
 * Runs reasoning on local hardware to ensure zero data leakage.
 */
class HardenedOpenAiDriver extends BaseAgentDriver
{
    public function execute(array $inst, string $context, string $query): string
    {
        // 1. Audit usage (Local tokens are free, but we still track weight)
        $this->logUsage('ollama', strlen($context));

        $host = getenv('OLLAMA_HOST') ?: 'http://localhost:11434';
        $model = getenv('OLLAMA_MODEL') ?: 'llama3';

        // 2. Map Persona to Ollama Chat Format
        $systemText = "Role: {$inst['role']}. Objective: {$inst['objective']}. Tone: {$inst['tone']}.";

        $response = $this->client->post("{$host}/api/chat", [
            'json' => [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemText],
                    ['role' => 'user', 'content' => "CONTEXT:\n{$context}\n\nQUERY: {$query}"]
                ],
                'stream' => false, // We want the full response string
                'options' => [
                    'temperature' => 0.2
                ]
            ]
        ]);

        return $this->parseResponse(json_decode($response->getBody(), true));
    }

    protected function parseResponse(array $data): string
    {
        return $data['message']['content'] ?? 'Error: Ollama failed to reason.';
    }
}