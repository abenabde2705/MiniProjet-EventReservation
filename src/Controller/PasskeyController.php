<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasskeyService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/passkeys', name: 'api_passkeys_')]
class PasskeyController extends AbstractController
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    /**
     * Step 1 of registration: return PublicKeyCredentialCreationOptions.
     */
    #[Route('/register/options', name: 'register_options', methods: ['POST'])]
    public function registerOptions(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $options = $this->passkeyService->generateRegistrationOptions($user);
        return $this->json($options);
    }

    /**
     * Step 2 of registration: verify and store the new credential.
     */
    #[Route('/register/verify', name: 'register_verify', methods: ['POST'])]
    public function registerVerify(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['id'])) {
            return $this->json(['error' => 'Invalid credential data.'], Response::HTTP_BAD_REQUEST);
        }

        $this->passkeyService->storeCredential($user, [
            'id'        => $data['id'],
            'publicKey' => $data['response']['clientDataJSON'] ?? null,
            'counter'   => 0,
        ]);

        return $this->json(['success' => true, 'message' => 'Passkey registered successfully.']);
    }

    /**
     * Step 1 of login: return PublicKeyCredentialRequestOptions.
     */
    #[Route('/login/options', name: 'login_options', methods: ['POST'])]
    public function loginOptions(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;

        $options = $this->passkeyService->generateAuthenticationOptions($username);
        return $this->json($options);
    }

    /**
     * Step 2 of login: verify assertion and issue JWT token.
     */
    #[Route('/login/verify', name: 'login_verify', methods: ['POST'])]
    public function loginVerify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['id'])) {
            return $this->json(['error' => 'Invalid assertion data.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->passkeyService->findUserByCredentialId($data['id']);
        if (!$user) {
            return $this->json(['error' => 'Credential not found.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token'    => $token,
            'username' => $user->getUsername(),
        ]);
    }

    /**
     * List registered passkeys for the current user.
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function listPasskeys(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $creds = $user->getPasskeyCredentials()
            ? json_decode($user->getPasskeyCredentials(), true)
            : [];

        $result = array_map(static fn($c) => [
            'id'        => $c['id'],
            'createdAt' => $c['createdAt'] ?? null,
        ], $creds);

        return $this->json($result);
    }
}
