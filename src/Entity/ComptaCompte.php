<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComptaCompteRepository")
 */
class ComptaCompte
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaClasse", inversedBy="comptaComptes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $classe;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Length(min=4, max=4)
     */
    private $numero;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $created_by;

    /**
     * @ORM\Column(type="datetime", nullable=true)
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
     * @ORM\Column(type="boolean")
     */
    private $is_root;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $deleted_by;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaEcriture", mappedBy="debit")
     */
    private $comptaEcritures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaCompteExercice", mappedBy="compte")
     */
    private $comptaCompteExercices;

    public function __construct()
    {
        $this->is_root    = false;
        $this->is_deleted = false;
        $this->created_at = new \DateTime();
        $this->comptaEcritures = new ArrayCollection();
        $this->comptaCompteExercices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClasse(): ?ComptaClasse
    {
        return $this->classe;
    }

    public function setClasse(?ComptaClasse $classe): self
    {
        $this->classe = $classe;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection|ComptaEcriture[]
     */
    public function getComptaEcritures(): Collection
    {
        return $this->comptaEcritures;
    }

    public function addComptaEcriture(ComptaEcriture $comptaEcriture): self
    {
        if (!$this->comptaEcritures->contains($comptaEcriture)) {
            $this->comptaEcritures[] = $comptaEcriture;
            $comptaEcriture->setDebit($this);
        }

        return $this;
    }

    public function removeComptaEcriture(ComptaEcriture $comptaEcriture): self
    {
        if ($this->comptaEcritures->contains($comptaEcriture)) {
            $this->comptaEcritures->removeElement($comptaEcriture);
            // set the owning side to null (unless already changed)
            if ($comptaEcriture->getDebit() === $this) {
                $comptaEcriture->setDebit(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
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

    public function getIsRoot(): ?bool
    {
        return $this->is_root;
    }

    public function setIsRoot(bool $is_root): self
    {
        $this->is_root = $is_root;

        return $this;
    }

    /**
     * @return Collection|ComptaCompteExercice[]
     */
    public function getComptaCompteExercices(): Collection
    {
        return $this->comptaCompteExercices;
    }

    public function addComptaCompteExercice(ComptaCompteExercice $comptaCompteExercice): self
    {
        if (!$this->comptaCompteExercices->contains($comptaCompteExercice)) {
            $this->comptaCompteExercices[] = $comptaCompteExercice;
            $comptaCompteExercice->setCompte($this);
        }

        return $this;
    }

    public function removeComptaCompteExercice(ComptaCompteExercice $comptaCompteExercice): self
    {
        if ($this->comptaCompteExercices->contains($comptaCompteExercice)) {
            $this->comptaCompteExercices->removeElement($comptaCompteExercice);
            // set the owning side to null (unless already changed)
            if ($comptaCompteExercice->getCompte() === $this) {
                $comptaCompteExercice->setCompte(null);
            }
        }

        return $this;
    }
}
