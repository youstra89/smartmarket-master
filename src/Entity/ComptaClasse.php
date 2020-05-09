<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComptaClasseRepository")
 */
class ComptaClasse
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaType", inversedBy="comptaClasses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaCompte", mappedBy="classe")
     */
    private $comptaComptes;

    public function __construct()
    {
        $this->comptaComptes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?ComptaType
    {
        return $this->type;
    }

    public function setType(?ComptaType $type): self
    {
        $this->type = $type;

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
     * @return Collection|ComptaCompte[]
     */
    public function getComptaComptes(): Collection
    {
        return $this->comptaComptes;
    }

    public function addComptaCompte(ComptaCompte $comptaCompte): self
    {
        if (!$this->comptaComptes->contains($comptaCompte)) {
            $this->comptaComptes[] = $comptaCompte;
            $comptaCompte->setClasse($this);
        }

        return $this;
    }

    public function removeComptaCompte(ComptaCompte $comptaCompte): self
    {
        if ($this->comptaComptes->contains($comptaCompte)) {
            $this->comptaComptes->removeElement($comptaCompte);
            // set the owning side to null (unless already changed)
            if ($comptaCompte->getClasse() === $this) {
                $comptaCompte->setClasse(null);
            }
        }

        return $this;
    }
}
