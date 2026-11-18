<?php

final class CodeCartDeadCodeValidFixture
{
    private int $value = 3;

    private function getValue(): int
    {
        return $this->value;
    }

    public function run(): int
    {
        return $this->getValue();
    }
}
