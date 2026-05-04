<?php

namespace App\Model;

readonly class Answer
{
    public function __construct(
        public string $id,
        public string $text,
        public bool $correct,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            text: $data['text'],
            correct: $data['correct'],
        );
    }
}
