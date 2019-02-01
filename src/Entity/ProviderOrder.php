<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProviderOrderRepository")
 */
class ProviderOrder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private $additional_fees;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\order", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $ordeer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Provider", inversedBy="providerOrders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $provider;

    /**
     * @ORM\Column(type="integer")
     */
    private $transport;

    /**
     * @ORM\Column(type="integer")
     */
    private $dedouanement;

    /**
     * @ORM\Column(type="integer")
     */
    private $currency_cost;

    /**
     * @ORM\Column(type="integer")
     */
    private $forwarding_cost;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getAdditionalFees(): ?int
    {
        return $this->additional_fees;
    }

    public function setAdditionalFees(int $additional_fees): self
    {
        $this->additional_fees = $additional_fees;

        return $this;
    }

    public function getOrdeer(): ?order
    {
        return $this->ordeer;
    }

    public function setOrdeer(order $ordeer): self
    {
        $this->ordeer = $ordeer;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getTransport(): ?int
    {
        return $this->transport;
    }

    public function setTransport(int $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function getDedouanement(): ?int
    {
        return $this->dedouanement;
    }

    public function setDedouanement(int $dedouanement): self
    {
        $this->dedouanement = $dedouanement;

        return $this;
    }

    public function getCurrencyCost(): ?int
    {
        return $this->currency_cost;
    }

    public function setCurrencyCost(int $currency_cost): self
    {
        $this->currency_cost = $currency_cost;

        return $this;
    }

    public function getForwardingCost(): ?int
    {
        return $this->forwarding_cost;
    }

    public function setForwardingCost(int $forwarding_cost): self
    {
        $this->forwarding_cost = $forwarding_cost;

        return $this;
    }
}
