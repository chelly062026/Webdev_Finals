<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST','GET'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
        , LoggerInterface $logger
    ): JsonResponse {

        // Debugging: write a concise entry to var/log/login_debug.log for each attempt
        $logPath = $this->getParameter('kernel.project_dir') . '/var/log/login_debug.log';

        $data = json_decode($request->getContent(), true);

        // Fallback to form-encoded data if JSON body empty
        if (!$data || !is_array($data)) {
            $data = $request->request->all();
        }

        if (!$data || !isset($data['username'], $data['password'])) {
            $fallback = $request->request->all();
            $entry = [
                'time' => date('c'),
                'note' => 'missing_json_credentials',
                'json' => $data,
                'fallback' => $fallback,
                'ip' => $request->getClientIp(),
            ];
            @file_put_contents($logPath, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $logger->info('Login attempt missing credentials', $entry);
            return new JsonResponse(['error' => 'Invalid JSON or missing credentials'], 400);
        }

        $user = $userRepository->findOneBy(['username' => $data['username']]);

        if (!$user) {
            $entry = ['time' => date('c'), 'note' => 'user_not_found', 'username' => $data['username'], 'ip' => $request->getClientIp()];
            @file_put_contents($logPath, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $logger->info('Login user not found', $entry);
            return new JsonResponse(['error' => 'User not found'], 401);
        }

        $passwordValid = $passwordHasher->isPasswordValid($user, $data['password']);
        if (!$passwordValid) {
            $entry = ['time' => date('c'), 'note' => 'invalid_password', 'username' => $data['username'], 'userId' => $user->getId(), 'ip' => $request->getClientIp()];
            @file_put_contents($logPath, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $logger->info('Login invalid password', $entry);
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Successful login -- log it
        @file_put_contents($logPath, json_encode(['time' => date('c'), 'note' => 'login_success', 'username' => $data['username'], 'userId' => $user->getId(), 'ip' => $request->getClientIp()]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $logger->info('Login success', ['username' => $data['username'], 'userId' => $user->getId()]);

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ]
        ]);
    }
}