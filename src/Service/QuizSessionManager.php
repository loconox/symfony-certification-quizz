<?php

namespace App\Service;

use App\Model\QuizSession;
use Symfony\Component\HttpFoundation\RequestStack;

class QuizSessionManager
{
    private const SESSION_KEY = 'quiz_session';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function get(): ?QuizSession
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY);
    }

    public function save(QuizSession $session): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $session);
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }
}
