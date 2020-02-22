<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConnexionRepository")
 */
class Connexion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="connexions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $connected_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $browser;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $operating_system;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $disconnected_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ip_address;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getConnectedAt(): ?\DateTimeInterface
    {
        return $this->connected_at;
    }

    public function setConnectedAt(\DateTimeInterface $connected_at): self
    {
        $this->connected_at = $connected_at;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operating_system;
    }

    public function setOperatingSystem(?string $operating_system): self
    {
        $this->operating_system = $operating_system;

        return $this;
    }

    public function getDisconnectedAt(): ?\DateTimeInterface
    {
        return $this->disconnected_at;
    }

    public function setDisconnectedAt(?\DateTimeInterface $disconnected_at): self
    {
        $this->disconnected_at = $disconnected_at;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(?string $ip_address): self
    {
        $this->ip_address = $ip_address;

        return $this;
    }
}
