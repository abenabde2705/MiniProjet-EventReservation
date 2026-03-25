<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiAuthController extends AbstractController
{
    /**
     * JWT login is handled by LexikJWTAuthenticationBundle at /api/login
     * This controller handles registration and profile endpoints.
     */

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $email = trim($data['email'] ?? '');

        if (empty($username) || empty($password)) {
            return $this->json(['error' => 'Username and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['username' => $username])) {
            return $this->json(['error' => 'Username already taken.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email ?: null);
        $user->setPassword($hasher->hashPassword($user, $password));

        $userRepository->save($user);

        return $this->json(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
