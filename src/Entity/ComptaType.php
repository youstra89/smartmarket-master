<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComptaTypeRepository")
 */
class ComptaType
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
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaClasse", mappedBy="type")
     */
    private $comptaClasses;

    public function __construct()
    {
        $this->comptaClasses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|ComptaClasse[]
     */
    public function getComptaClasses(): Collection
    {
        return $this->comptaClasses;
    }

    public function addComptaClass(ComptaClasse $comptaClass): self
    {
        if (!$this->comptaClasses->contains($comptaClass)) {
            $this->comptaClasses[] = $comptaClass;
            $comptaClass->setType($this);
        }

        return $this;
    }

    public function removeComptaClass(ComptaClasse $comptaClass): self
    {
        if ($this->comptaClasses->contains($comptaClass)) {
            $this->comptaClasses->removeElement($comptaClass);
            // set the owning side to null (unless already changed)
            if ($comptaClass->getType() === $this) {
                $comptaClass->setType(null);
            }
        }

        return $this;
    }
}
