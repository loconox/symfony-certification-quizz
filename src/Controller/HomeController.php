<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Service\QuizSessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(QuestionRepository $repository, QuizSessionManager $sessionManager): Response
    {
        $activeSession = $sessionManager->get();
        $totalQuestions = count($repository->findAll());
        $countByTheme = $repository->countByTheme();

        return $this->render('home/index.html.twig', [
            'total_questions' => $totalQuestions,
            'count_by_theme' => $countByTheme,
            'active_session' => $activeSession,
        ]);
    }
}
