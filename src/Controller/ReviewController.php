<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Service\QuizSessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz/review')]
class ReviewController extends AbstractController
{
    #[Route('', name: 'review_list', methods: ['GET'])]
    public function list(QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $quizSession = $sessionManager->get();

        if (!$quizSession || !$quizSession->completed) {
            return $this->redirectToRoute('home');
        }

        $questions = $repository->findByIds($quizSession->questionIds);
        $wrongQuestions = [];

        foreach ($questions as $question) {
            $userAnswers = $quizSession->userAnswers[$question->id] ?? [];
            if (!$question->isAnswerCorrect($userAnswers)) {
                $wrongQuestions[] = [
                    'question' => $question,
                    'user_answers' => $userAnswers,
                ];
            }
        }

        return $this->render('review/list.html.twig', [
            'wrong_questions' => $wrongQuestions,
        ]);
    }

    #[Route('/{questionId}', name: 'review_detail', methods: ['GET'])]
    public function detail(string $questionId, QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $quizSession = $sessionManager->get();

        if (!$quizSession || !$quizSession->completed) {
            return $this->redirectToRoute('home');
        }

        $question = $repository->findById($questionId);

        if (!$question) {
            throw $this->createNotFoundException();
        }

        $userAnswers = $quizSession->userAnswers[$questionId] ?? [];

        $wrongIds = array_values(array_filter(
            $quizSession->questionIds,
            fn(string $id) => !$repository->findById($id)?->isAnswerCorrect($quizSession->userAnswers[$id] ?? [])
        ));

        $currentPos = array_search($questionId, $wrongIds);
        $prevId = $currentPos > 0 ? $wrongIds[$currentPos - 1] : null;
        $nextId = isset($wrongIds[$currentPos + 1]) ? $wrongIds[$currentPos + 1] : null;

        return $this->render('review/detail.html.twig', [
            'question' => $question,
            'user_answers' => $userAnswers,
            'prev_id' => $prevId,
            'next_id' => $nextId,
            'current_pos' => $currentPos + 1,
            'total_wrong' => count($wrongIds),
        ]);
    }
}
