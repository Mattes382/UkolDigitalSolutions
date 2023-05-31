<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $surname = null;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private ?string $externalApiId;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private ?string $token;

    /**
     * @var Collection<int, Money>
     *
     * @ORM\OneToMany(targetEntity="Money", mappedBy="user", cascade={"persist"})
     */
    private Collection $money;

    public function __construct()
    {
        $this->money = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * @return Collection<int, Money>
     */
    public function getMoney(): Collection
    {
        return $this->money;
    }

    public function addMoney(Money $money): void
    {
        if (!$this->money->contains($money)) {
            $this->money->add($money);
            $money->setUser($this);
        }
    }

    public function removeMoney(Money $money): void
    {
        $this->money->removeElement($money);
        $money->setUser(null);
    }

    public function getExternalApiId(): ?string
    {
        return $this->externalApiId;
    }

    public function setExternalApiId(string $externalApiId): void
    {
        $this->externalApiId = $externalApiId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }
}
