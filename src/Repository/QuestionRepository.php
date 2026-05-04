<?php

namespace App\Repository;

use App\Model\Question;

class QuestionRepository
{
    /** @var Question[]|null */
    private ?array $questions = null;

    public function __construct(private readonly string $dataDir)
    {
    }

    /** @return Question[] */
    public function findAll(): array
    {
        if ($this->questions !== null) {
            return $this->questions;
        }

        $this->questions = [];
        $files = glob($this->dataDir . '/*.json') ?: [];

        foreach ($files as $file) {
            $theme = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);

            foreach ($data as $item) {
                $this->questions[] = Question::fromArray($item, $theme);
            }
        }

        return $this->questions;
    }

    public function findById(string $id): ?Question
    {
        foreach ($this->findAll() as $question) {
            if ($question->id === $id) {
                return $question;
            }
        }

        return null;
    }

    /** @return Question[] */
    public function findByIds(array $ids): array
    {
        $indexed = [];
        foreach ($this->findAll() as $question) {
            $indexed[$question->id] = $question;
        }

        return array_values(array_filter(
            array_map(fn(string $id) => $indexed[$id] ?? null, $ids)
        ));
    }

    /** @return Question[] */
    public function findRandom(int $count): array
    {
        $all = $this->findAll();
        if (count($all) <= $count) {
            shuffle($all);
            return $all;
        }

        $keys = array_rand($all, $count);
        return array_map(fn(int $k) => $all[$k], (array) $keys);
    }

    /** @return array<string, int> theme => count */
    public function countByTheme(): array
    {
        $counts = [];
        foreach ($this->findAll() as $question) {
            $counts[$question->theme] = ($counts[$question->theme] ?? 0) + 1;
        }
        return $counts;
    }
}
