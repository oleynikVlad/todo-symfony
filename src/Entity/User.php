<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User Entity — Doctrine equivalent of a Laravel Eloquent Model.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: User extends Authenticatable (Eloquent model with auth traits)
 * - Symfony: User implements UserInterface + PasswordAuthenticatedUserInterface
 *
 * - Laravel uses $fillable/$guarded for mass assignment protection
 * - Symfony/Doctrine uses explicit getters/setters (no mass assignment concept)
 *
 * - Laravel: $table = 'users', $primaryKey = 'id'
 * - Symfony: #[ORM\Table(name: 'users')], #[ORM\Id] on property
 *
 * - Laravel: $casts for type casting
 * - Symfony: Doctrine handles type mapping via #[ORM\Column(type: '...')]
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Todo> */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Todo::class, orphanRemoval: true)]
    private Collection $todos;

    public function __construct()
    {
        $this->todos = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     * In Laravel, this would be the 'email' field used in Auth::attempt().
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear any temporary sensitive data
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Todo> */
    public function getTodos(): Collection
    {
        return $this->todos;
    }

    public function addTodo(Todo $todo): static
    {
        if (!$this->todos->contains($todo)) {
            $this->todos->add($todo);
            $todo->setOwner($this);
        }
        return $this;
    }

    public function removeTodo(Todo $todo): static
    {
        if ($this->todos->removeElement($todo)) {
            if ($todo->getOwner() === $this) {
                $todo->setOwner(null);
            }
        }
        return $this;
    }
}
