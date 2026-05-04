<?php

namespace App\Model;

readonly class Question
{
    /** @param Answer[] $answers */
    public function __construct(
        public string $id,
        public string $theme,
        public string $type,
        public string $question,
        public array $answers,
        public string $explanation,
        public ?string $docUrl = null,
    ) {
    }

    public static function fromArray(array $data, string $theme): self
    {
        return new self(
            id: $data['id'],
            theme: $theme,
            type: $data['type'],
            question: $data['question'],
            answers: array_map(Answer::fromArray(...), $data['answers']),
            explanation: $data['explanation'],
            docUrl: $data['doc_url'] ?? null,
        );
    }

    /** @return Answer[] */
    public function correctAnswers(): array
    {
        return array_values(array_filter($this->answers, fn(Answer $a) => $a->correct));
    }

    /** @return string[] */
    public function correctAnswerIds(): array
    {
        return array_map(fn(Answer $a) => $a->id, $this->correctAnswers());
    }

    public function isAnswerCorrect(array $givenAnswerIds): bool
    {
        $correct = $this->correctAnswerIds();
        sort($correct);
        sort($givenAnswerIds);

        return $correct === $givenAnswerIds;
    }
}
