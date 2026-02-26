<?php
namespace local_ai_gateway;

class mock_provider implements provider_interface {

    public function generate_text(string $prompt): array {
        return [
            'text' => "Mock response for: " . $prompt,
            'input_tokens' => strlen($prompt) / 4,
            'output_tokens' => 50,
            'cost' => 0.00
        ];
    }
}