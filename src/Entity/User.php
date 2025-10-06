<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\UserLog;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialization = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updateAt = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Establishment $establishment = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'client')]
    private Collection $appointmentsAsClient;

    /**
     * @var Collection<int, Establishment>
     */
    #[ORM\OneToMany(targetEntity: Establishment::class, mappedBy: 'owner')]
    private Collection $establishments;

    /**
     * @var Collection<int, Loyalty>
     */
    #[ORM\OneToMany(targetEntity: Loyalty::class, mappedBy: 'client')]
    private Collection $loyalties;

    /**
     * @var Collection<int, UserLog>
     */
    #[ORM\OneToMany(targetEntity: UserLog::class, mappedBy: 'relatedUser')]
    private Collection $userlogs;

    /**
     * @var Collection<int, AccountSuspension>
     */
    #[ORM\OneToMany(targetEntity: AccountSuspension::class, mappedBy: 'suspendedUser')]
    private Collection $accountSuspensions;

    /**
     * @var Collection<int, AccountSuspension>
     */
    #[ORM\OneToMany(targetEntity: AccountSuspension::class, mappedBy: 'adminUser')]
    private Collection $adminSuspensions;

    public function __construct()
    {
        $this->appointmentsAsClient = new ArrayCollection();
        $this->establishments = new ArrayCollection();
        $this->loyalties = new ArrayCollection();
        $this->userlogs = new ArrayCollection();
        $this->accountSuspensions = new ArrayCollection();
        $this->adminSuspensions = new ArrayCollection();
    }


    // GETTERS & SETTERS


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(string $specialization): static
    {
        $this->specialization = $specialization;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }


    // RELATIONS



    public function getAppointmentsAsClient(): Collection
    {
        return $this->appointmentsAsClient;
    }

    public function addAppointmentsAsClient(Appointment $appointmentsAsClient): static
    {
        if (!$this->appointmentsAsClient->contains($appointmentsAsClient)) {
            $this->appointmentsAsClient->add($appointmentsAsClient);
            $appointmentsAsClient->setClient($this);
        }

        return $this;
    }

    public function removeAppointmentsAsClient(Appointment $appointmentsAsClient): static
    {
        if ($this->appointmentsAsClient->removeElement($appointmentsAsClient)) {
            if ($appointmentsAsClient->getClient() === $this) {
                $appointmentsAsClient->setClient(null);
            }
        }

        return $this;
    }

    public function getEstablishments(): Collection
    {
        return $this->establishments;
    }

    public function addEstablishment(Establishment $establishment): static
    {
        if (!$this->establishments->contains($establishment)) {
            $this->establishments->add($establishment);
            $establishment->setOwner($this);
        }

        return $this;
    }

    public function removeEstablishment(Establishment $establishment): static
    {
        if ($this->establishments->removeElement($establishment)) {
            if ($establishment->getOwner() === $this) {
                $establishment->setOwner(null);
            }
        }

        return $this;
    }

    public function getLoyalties(): Collection
    {
        return $this->loyalties;
    }

    public function addLoyalty(Loyalty $loyalty): static
    {
        if (!$this->loyalties->contains($loyalty)) {
            $this->loyalties->add($loyalty);
            $loyalty->setClient($this);
        }

        return $this;
    }

    public function removeLoyalty(Loyalty $loyalty): static
    {
        if ($this->loyalties->removeElement($loyalty)) {
            if ($loyalty->getClient() === $this) {
                $loyalty->setClient(null);
            }
        }

        return $this;
    }

    public function getUserlogs(): Collection
    {
        return $this->userlogs;
    }

    public function addUserlog(UserLog $userlog): static
    {
        if (!$this->userlogs->contains($userlog)) {
            $this->userlogs->add($userlog);
            $userlog->setRelatedUser($this);
        }

        return $this;
    }

    public function removeUserlog(UserLog $userlog): static
    {
        if ($this->userlogs->removeElement($userlog)) {
            if ($userlog->getRelatedUser() === $this) {
                $userlog->setRelatedUser(null);
            }
        }

        return $this;
    }

    public function getAccountSuspensions(): Collection
    {
        return $this->accountSuspensions;
    }

    public function addAccountSuspension(AccountSuspension $accountSuspension): static
    {
        if (!$this->accountSuspensions->contains($accountSuspension)) {
            $this->accountSuspensions->add($accountSuspension);
            $accountSuspension->setSuspendedUser($this);
        }

        return $this;
    }

    public function removeAccountSuspension(AccountSuspension $accountSuspension): static
    {
        if ($this->accountSuspensions->removeElement($accountSuspension)) {
            if ($accountSuspension->getSuspendedUser() === $this) {
                $accountSuspension->setSuspendedUser(null);
            }
        }

        return $this;
    }

    public function getAdminSuspensions(): Collection
    {
        return $this->adminSuspensions;
    }

    public function addAdminSuspension(AccountSuspension $adminSuspension): static
    {
        if (!$this->adminSuspensions->contains($adminSuspension)) {
            $this->adminSuspensions->add($adminSuspension);
            $adminSuspension->setAdminUser($this);
        }
        return $this;
    }

    public function removeAdminSuspension(AccountSuspension $adminSuspension): static
    {
        if ($this->adminSuspensions->removeElement($adminSuspension)) {
            if ($adminSuspension->getAdminUser() === $this) {
                $adminSuspension->setAdminUser(null);
            }
        }
        return $this;
    }


    // SECURITY


    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = [$this->role ?? 'ROLE_USER'];

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // nettoyer les données sensibles si besoin
    }


    // LIFECYCLE CALLBACKS


    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updateAt = new \DateTime();

        if ($this->isActive === null) {
            $this->isActive = true;
        }
    }

    #[ORM\PreUpdate]
    public function setUpdateAtValue(): void
    {
        $this->updateAt = new \DateTime();
    }
}
