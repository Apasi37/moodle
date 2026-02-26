<?php
namespace local_ai_gateway;

interface provider_interface {
    public function generate_text(string $prompt): array;
}