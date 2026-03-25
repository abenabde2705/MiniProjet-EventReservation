<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username', ''));
            $password = $request->request->get('password', '');
            $email = trim($request->request->get('email', ''));

            if (empty($username) || empty($password)) {
                $error = 'Username and password are required.';
            } elseif ($userRepository->findOneBy(['username' => $username])) {
                $error = 'Username already exists.';
            } else {
                $user = new User();
                $user->setUsername($username);
                $user->setEmail($email ?: null);
                $user->setPassword($hasher->hashPassword($user, $password));

                $userRepository->save($user);

                $this->addFlash('success', 'Account created! You can now log in.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony handles this via the firewall logout configuration
    }
}
