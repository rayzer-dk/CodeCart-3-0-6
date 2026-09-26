<?php
namespace CodeCart\Core;

interface CompatibilityAdapterInterface {
    public function id(): string;
    public function priority(): int;
    public function active(): bool;
    public function adapt(string $contract, array $payload): array;
}
