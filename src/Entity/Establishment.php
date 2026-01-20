<?php

namespace App\Entity;

use App\Repository\EstablishmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstablishmentRepository::class)]
class Establishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // /**
    //  * @var Collection<int, User>
    //  */
    // #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'establishment')]
    // private Collection $users;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'establishment')]
    private Collection $services;

    /**
     * @var Collection<int, Equipement>
     */
    #[ORM\OneToMany(targetEntity: Equipement::class, mappedBy: 'establishment')]
    private Collection $equipments;

    /**
     * @var Collection<int, OpeningHour>
     */
    #[ORM\OneToMany(targetEntity: OpeningHour::class, mappedBy: 'establishment')]
    private Collection $openingHours;

    /**
     * @var Collection<int, Availability>
     */
    #[ORM\OneToMany(targetEntity: Availability::class, mappedBy: 'establishment')]
    private Collection $availabilities;

    // #[ORM\ManyToOne(inversedBy: 'establishments')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?User $owner = null;

    /**
     * @var Collection<int, Loyalty>
     */
    #[ORM\OneToMany(targetEntity: Loyalty::class, mappedBy: 'establishment')]
    private Collection $loyalties;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionalEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $professionalPhone = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'establishment')]
    private Collection $users;

    #[ORM\ManyToOne(inversedBy: 'establishments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        // $this->users = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->equipments = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
        $this->availabilities = new ArrayCollection();
        $this->loyalties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    // /**
    //  * @return Collection<int, User>
    //  */
    // public function getUsers(): Collection
    // {
    //     return $this->users;
    // }

    // public function addUser(User $user): static
    // {
    //     if (!$this->users->contains($user)) {
    //         $this->users->add($user);
    //         $user->setEstablishment($this);
    //     }

    //     return $this;
    // }

    // public function removeUser(User $user): static
    // {
    //     if ($this->users->removeElement($user)) {
    //         // set the owning side to null (unless already changed)
    //         if ($user->getEstablishment() === $this) {
    //             $user->setEstablishment(null);
    //         }
    //     }

    //     return $this;
    // }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setEstablishment($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getEstablishment() === $this) {
                $service->setEstablishment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Equipement>
     */
    public function getEquipments(): Collection
    {
        return $this->equipments;
    }

    public function addEquipment(Equipement $equipment): static
    {
        if (!$this->equipments->contains($equipment)) {
            $this->equipments->add($equipment);
            $equipment->setEstablishment($this);
        }

        return $this;
    }

    public function removeEquipment(Equipement $equipment): static
    {
        if ($this->equipments->removeElement($equipment)) {
            // set the owning side to null (unless already changed)
            if ($equipment->getEstablishment() === $this) {
                $equipment->setEstablishment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OpeningHour>
     */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHour $openingHour): static
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setEstablishment($this);
        }

        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): static
    {
        if ($this->openingHours->removeElement($openingHour)) {
            // set the owning side to null (unless already changed)
            if ($openingHour->getEstablishment() === $this) {
                $openingHour->setEstablishment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(Availability $availability): static
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setEstablishment($this);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): static
    {
        if ($this->availabilities->removeElement($availability)) {
            // set the owning side to null (unless already changed)
            if ($availability->getEstablishment() === $this) {
                $availability->setEstablishment(null);
            }
        }

        return $this;
    }

    // public function getOwner(): ?User
    // {
    //     return $this->owner;
    // }

    // public function setOwner(?User $owner): static
    // {
    //     $this->owner = $owner;

    //     return $this;
    // }

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
            $loyalty->setEstablishment($this);
        }

        return $this;
    }

    public function removeLoyalty(Loyalty $loyalty): static
    {
        if ($this->loyalties->removeElement($loyalty)) {
            // set the owning side to null (unless already changed)
            if ($loyalty->getEstablishment() === $this) {
                $loyalty->setEstablishment(null);
            }
        }

        return $this;
    }

    public function getProfessionalEmail(): ?string
    {
        return $this->professionalEmail;
    }

    public function setProfessionalEmail(?string $professionalEmail): static
    {
        $this->professionalEmail = $professionalEmail;

        return $this;
    }

    public function getProfessionalPhone(): ?string
    {
        return $this->professionalPhone;
    }

    public function setProfessionalPhone(?string $professionalPhone): static
    {
        $this->professionalPhone = $professionalPhone;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setEstablishment($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getEstablishment() === $this) {
                $user->setEstablishment(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
