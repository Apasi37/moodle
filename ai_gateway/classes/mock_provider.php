<?php
namespace local_ai_gateway;

class mock_provider implements provider_interface {

    public function generate_text(string $system_prompt, string $user_prompt): array {
        return [
            'text' => "Mock response for: " . $user_prompt,
            'input_tokens' => (strlen($system_prompt) + strlen($user_prompt)) / 4,
            'output_tokens' => 50,
            'cost' => 0.00
        ];
    }

    public function summarize_text(string $system_prompt, string $text): array {
        $summary = substr($text, 0, 150);
        if (strlen($text) > 150) {
            $summary .= '...';
        }
        return [
            'text' => $summary,
            'input_tokens' => (strlen($system_prompt) + strlen($text)) / 4,
            'output_tokens' => strlen($summary) / 4,
            'cost' => 0.00
        ];
    }
}