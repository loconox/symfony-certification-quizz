<?php

namespace App\Model;

class QuizSession
{
    /** @var string[] */
    public array $questionIds = [];

    /** @var array<string, string[]> maps question ID to array of chosen answer IDs */
    public array $userAnswers = [];

    public bool $completed = false;

    public function currentIndex(): int
    {
        return count($this->userAnswers);
    }

    public function totalQuestions(): int
    {
        return count($this->questionIds);
    }

    public function hasAnswered(string $questionId): bool
    {
        return isset($this->userAnswers[$questionId]);
    }

    public function recordAnswer(string $questionId, array $answerIds): void
    {
        $this->userAnswers[$questionId] = $answerIds;
    }

    public function questionIdAtIndex(int $index): ?string
    {
        return $this->questionIds[$index - 1] ?? null;
    }

    public function isLastQuestion(int $index): bool
    {
        return $index >= $this->totalQuestions();
    }
}
