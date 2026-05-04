<?php

namespace App\Controller;

use App\Model\QuizSession;
use App\Repository\QuestionRepository;
use App\Service\QuizSessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    #[Route('/start', name: 'quiz_start', methods: ['POST'])]
    public function start(Request $request, QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $total = count($repository->findAll());
        $count = max(1, min((int) $request->request->get('count', $total), $total));
        $questions = $repository->findRandom($count);

        $session = new QuizSession();
        $session->questionIds = array_map(fn($q) => $q->id, $questions);

        $sessionManager->save($session);

        return $this->redirectToRoute('quiz_question', ['index' => 1]);
    }

    #[Route('/question/{index}', name: 'quiz_question', methods: ['GET'], requirements: ['index' => '\d+'])]
    public function question(int $index, QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $quizSession = $sessionManager->get();

        if (!$quizSession || $quizSession->completed) {
            return $this->redirectToRoute('home');
        }

        if ($index < 1 || $index > $quizSession->totalQuestions()) {
            return $this->redirectToRoute('quiz_question', ['index' => $quizSession->currentIndex() + 1]);
        }

        $questionId = $quizSession->questionIdAtIndex($index);
        $question = $repository->findById($questionId);

        if (!$question) {
            throw $this->createNotFoundException();
        }

        return $this->render('quiz/question.html.twig', [
            'question' => $question,
            'index' => $index,
            'total' => $quizSession->totalQuestions(),
            'already_answered' => $quizSession->hasAnswered($questionId),
            'user_answers' => $quizSession->userAnswers[$questionId] ?? [],
        ]);
    }

    #[Route('/question/{index}', name: 'quiz_answer', methods: ['POST'], requirements: ['index' => '\d+'])]
    public function answer(int $index, Request $request, QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $quizSession = $sessionManager->get();

        if (!$quizSession || $quizSession->completed) {
            return $this->redirectToRoute('home');
        }

        $questionId = $quizSession->questionIdAtIndex($index);
        $question = $repository->findById($questionId);

        if (!$question) {
            throw $this->createNotFoundException();
        }

        $answers = $request->request->all('answers');
        if (empty($answers)) {
            $answers = [];
        }

        $quizSession->recordAnswer($questionId, $answers);

        if ($quizSession->isLastQuestion($index)) {
            $quizSession->completed = true;
            $sessionManager->save($quizSession);
            return $this->redirectToRoute('quiz_results');
        }

        $sessionManager->save($quizSession);

        return $this->redirectToRoute('quiz_question', ['index' => $index + 1]);
    }

    #[Route('/results', name: 'quiz_results', methods: ['GET'])]
    public function results(QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $quizSession = $sessionManager->get();

        if (!$quizSession || !$quizSession->completed) {
            return $this->redirectToRoute('home');
        }

        $questions = $repository->findByIds($quizSession->questionIds);
        $correctCount = 0;
        $wrongQuestions = [];
        $statsByTheme = [];

        foreach ($questions as $question) {
            $userAnswers = $quizSession->userAnswers[$question->id] ?? [];
            $isCorrect = $question->isAnswerCorrect($userAnswers);

            $theme = $question->theme;
            if (!isset($statsByTheme[$theme])) {
                $statsByTheme[$theme] = ['total' => 0, 'correct' => 0];
            }
            $statsByTheme[$theme]['total']++;

            if ($isCorrect) {
                $correctCount++;
                $statsByTheme[$theme]['correct']++;
            } else {
                $wrongQuestions[] = $question;
            }
        }

        $total = $quizSession->totalQuestions();
        $score = $total > 0 ? round($correctCount / $total * 100) : 0;

        return $this->render('quiz/results.html.twig', [
            'score' => $score,
            'correct_count' => $correctCount,
            'total' => $total,
            'wrong_questions' => $wrongQuestions,
            'stats_by_theme' => $statsByTheme,
        ]);
    }
}
