<?php
namespace local_ai_gateway;

interface provider_interface {
    public function generate_text(string $system_prompt, string $user_prompt): array;
    public function summarize_text(string $system_prompt, string $text): array;
}