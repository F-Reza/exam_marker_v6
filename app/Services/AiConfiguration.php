<?php

namespace App\Services;

use App\Models\AiSetting;
use RuntimeException;

class AiConfiguration
{
    public function resolve(): array
    {
        $setting = AiSetting::first();
        $provider = $setting?->provider ?? config('ai.provider', 'openai');
        $options = config('ai.models.'.$provider, []);
        if (!in_array($provider, ['openai','openrouter','gemini','claude','azure','ollama'], true)) {
            throw new RuntimeException('The selected AI provider does not support remote marking.');
        }
        $options['provider'] = $provider;
        $options['model'] = $setting?->model ?: ($options['model'] ?? null);
        $options['key'] = $setting?->api_key ?: ($options['key'] ?? null);
        if (!$options['model'] || empty($options['url']) || ($provider !== 'ollama' && empty($options['key']))) {
            throw new RuntimeException('AI provider configuration is incomplete. Ask the administrator to set the provider, model, API key and endpoint.');
        }
        return $options;
    }
}
