<?php

namespace local_ai_gateway;

defined('MOODLE_INTERNAL') || die();

use core\http_client;

class openai_provider implements provider_interface {

    private string $apikey;
    private string $model;

    public function __construct() {
        $this->apikey = get_config('local_ai_gateway', 'apikey');
        $this->model  = 'gpt-5-nano';
    }

    /**
     * Generate text from OpenAI API
     * 
     * @param string $prompt The prompt to send to the API
     * @return array An associative array with keys: 'text', 'input_tokens', 'output_tokens', 'cost'
     * @throws \moodle_exception If API key not configured, request fails, or response is invalid
     */
    public function generate_text(string $prompt): array {

        if (empty($this->apikey)) {
            throw new \moodle_exception('API key not configured.');
        }

        $client = new \core\http_client();

        $url = 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apikey,
                'Content-Type'  => 'application/json'
            ],
            'json' => $payload,
            'timeout' => 30
        ]);

        
        if ($response->getStatusCode() !== 200) {
            throw new \moodle_exception('OpenAI API request failed: ' . $response->getReasonPhrase());
        }

        $data = json_decode((string)$response->getBody(), true);

        if ($data === null) {
            throw new \moodle_exception('Failed to parse OpenAI API response as JSON.');
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \moodle_exception('Invalid OpenAI API response structure: missing message content.');
        }

        if (!isset($data['usage']['prompt_tokens'], $data['usage']['completion_tokens'])) {
            throw new \moodle_exception('Invalid OpenAI API response structure: missing token usage data.');
        }

        $text = $data['choices'][0]['message']['content'];
        $input_tokens  = $data['usage']['prompt_tokens'];
        $output_tokens = $data['usage']['completion_tokens'];

        $cost = $this->estimate_cost($input_tokens, $output_tokens);

        return [
            'text' => $text,
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'cost' => $cost
        ];
    }

    private function estimate_cost(int $input, int $output): float {

        // Example pricing for gpt-5-nano
        $input_price  = 0.00000005;   // $0.05 per 1M tokens
        $output_price = 0.00000040;   // $0.40 per 1M tokens

        return ($input * $input_price) + ($output * $output_price);
    }
}