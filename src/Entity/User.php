<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user', orphanRemoval: false)]
    private Collection $reservations;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passkeyCredentials = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string { return $this->password; }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getReservations(): Collection { return $this->reservations; }

    public function getPasskeyCredentials(): ?string { return $this->passkeyCredentials; }

    public function setPasskeyCredentials(?string $passkeyCredentials): static
    {
        $this->passkeyCredentials = $passkeyCredentials;
        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }
}
