<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                 return $this->redirectToRoute('app_dashboard');
             }

             return $this->redirectToRoute('app_shop');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/deactivated', name: 'app_deactivated')]
    public function deactivated(): Response
    {
        $this->addFlash('error', 'Your account is deactivated. Please contact support.');

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/user-page', name: 'app_user_page')]
    public function userPage(UserRepository $userRepository): Response
    {
        // A dedicated page for staff/non-admin users after login
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_dashboard');
        }

        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }
}
