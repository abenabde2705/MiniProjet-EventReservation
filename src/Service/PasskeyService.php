<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * PasskeyService provides WebAuthn / Passkey support using the Web Authentication API.
 *
 * This implementation uses a lightweight approach storing credentials as JSON
 * in the user's passkeyCredentials field. For production, consider using the
 * full web-auth/webauthn-symfony-bundle.
 */
class PasskeyService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly string $rpId,
        private readonly string $rpName,
    ) {}

    /**
     * Generate registration options to send to the browser.
     */
    public function generateRegistrationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();

        $options = [
            'challenge' => $challenge,
            'rp' => [
                'id'   => $this->rpId,
                'name' => $this->rpName,
            ],
            'user' => [
                'id'          => base64_encode((string) $user->getId()),
                'name'        => $user->getUsername(),
                'displayName' => $user->getUsername(),
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],   // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'authenticatorSelection' => [
                'residentKey'        => 'preferred',
                'requireResidentKey' => false,
                'userVerification'   => 'preferred',
            ],
            'timeout'     => 60000,
            'attestation' => 'none',
        ];

        return $options;
    }

    /**
     * Store a new passkey credential for a user after successful registration.
     */
    public function storeCredential(User $user, array $credentialData): void
    {
        $existing = $user->getPasskeyCredentials()
            ? json_decode($user->getPasskeyCredentials(), true)
            : [];

        $existing[] = [
            'id'        => $credentialData['id'],
            'publicKey' => $credentialData['publicKey'] ?? null,
            'counter'   => $credentialData['counter'] ?? 0,
            'createdAt' => (new \DateTime())->format('c'),
        ];

        $user->setPasskeyCredentials(json_encode($existing));
        $this->userRepository->save($user);
    }

    /**
     * Generate authentication options for login.
     */
    public function generateAuthenticationOptions(?string $username = null): array
    {
        $allowCredentials = [];

        if ($username) {
            $user = $this->userRepository->findOneBy(['username' => $username]);
            if ($user && $user->getPasskeyCredentials()) {
                $creds = json_decode($user->getPasskeyCredentials(), true);
                foreach ($creds as $cred) {
                    $allowCredentials[] = [
                        'type'       => 'public-key',
                        'id'         => $cred['id'],
                        'transports' => ['internal', 'usb', 'nfc', 'ble'],
                    ];
                }
            }
        }

        return [
            'challenge'        => $this->generateChallenge(),
            'rpId'             => $this->rpId,
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'preferred',
            'timeout'          => 60000,
        ];
    }

    /**
     * Find a user by their passkey credential ID.
     */
    public function findUserByCredentialId(string $credentialId): ?User
    {
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            if (!$user->getPasskeyCredentials()) {
                continue;
            }
            $creds = json_decode($user->getPasskeyCredentials(), true);
            foreach ($creds as $cred) {
                if ($cred['id'] === $credentialId) {
                    return $user;
                }
            }
        }
        return null;
    }

    private function generateChallenge(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
