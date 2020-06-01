<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApprovisionnementRepository")
 */
class Approvisionnement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="approvisionnements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="approvisionnements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $destination;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $created_by;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $updated_by;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_deleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $deleted_by;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DetailsApprovisionnement", mappedBy="approvisionnement")
     */
    private $detailsApprovisionnements;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date_reception;

    public function __construct()
    {
        $this->is_deleted = false;
        $this->created_at = new \DateTime();
        $this->detailsApprovisionnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSource(): ?Store
    {
        return $this->source;
    }

    public function setSource(?Store $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getDestination(): ?Store
    {
        return $this->destination;
    }

    public function setDestination(?Store $destination): self
    {
        $this->destination = $destination;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeInterface $deleted_at): self
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?User $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deleted_by;
    }

    public function setDeletedBy(?User $deleted_by): self
    {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    /**
     * @return Collection|DetailsApprovisionnement[]
     */
    public function getDetailsApprovisionnements(): Collection
    {
        return $this->detailsApprovisionnements;
    }

    public function addDetailsApprovisionnement(DetailsApprovisionnement $detailsApprovisionnement): self
    {
        if (!$this->detailsApprovisionnements->contains($detailsApprovisionnement)) {
            $this->detailsApprovisionnements[] = $detailsApprovisionnement;
            $detailsApprovisionnement->setApprovisionnement($this);
        }

        return $this;
    }

    public function removeDetailsApprovisionnement(DetailsApprovisionnement $detailsApprovisionnement): self
    {
        if ($this->detailsApprovisionnements->contains($detailsApprovisionnement)) {
            $this->detailsApprovisionnements->removeElement($detailsApprovisionnement);
            // set the owning side to null (unless already changed)
            if ($detailsApprovisionnement->getApprovisionnement() === $this) {
                $detailsApprovisionnement->setApprovisionnement(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDateReception(): ?\DateTimeInterface
    {
        return $this->date_reception;
    }

    public function setDateReception(?\DateTimeInterface $date_reception): self
    {
        $this->date_reception = $date_reception;

        return $this;
    }
}
