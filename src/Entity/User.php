<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialization = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updateAt = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
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

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'professional', orphanRemoval: true)]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointmentsAsClient = new ArrayCollection();
        $this->establishments = new ArrayCollection();
        $this->loyalties = new ArrayCollection();
        $this->userlogs = new ArrayCollection();
        $this->accountSuspensions = new ArrayCollection();
        $this->adminSuspensions = new ArrayCollection();
        $this->appointments = new ArrayCollection();
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
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(?string $specialization): static
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

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTime $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
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

    /**
     * @return Collection<int, Appointment>
     */
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
            // set the owning side to null (unless already changed)
            if ($appointmentsAsClient->getClient() === $this) {
                $appointmentsAsClient->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Establishment>
     */
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
            // set the owning side to null (unless already changed)
            if ($establishment->getOwner() === $this) {
                $establishment->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Loyalty>
     */
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
            // set the owning side to null (unless already changed)
            if ($loyalty->getClient() === $this) {
                $loyalty->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserLog>
     */
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
            // set the owning side to null (unless already changed)
            if ($userlog->getRelatedUser() === $this) {
                $userlog->setRelatedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccountSuspension>
     */
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
            // set the owning side to null (unless already changed)
            if ($accountSuspension->getSuspendedUser() === $this) {
                $accountSuspension->setSuspendedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccountSuspension>
     */
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
            // set the owning side to null (unless already changed)
            if ($adminSuspension->getAdminUser() === $this) {
                $adminSuspension->setAdminUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setProfessional($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getProfessional() === $this) {
                $appointment->setProfessional(null);
            }
        }

        return $this;
    }
}
