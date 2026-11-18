<?php

function codecartDeadCodeInvalidFixture(): void
{
    $forgotten = 'never read';
}

final class CodeCartDeadCodeInvalidFixture
{
    private string $unusedProperty = 'unused';

    private function unusedMethod(): void
    {
    }

    public function run(): void
    {
    }
}
