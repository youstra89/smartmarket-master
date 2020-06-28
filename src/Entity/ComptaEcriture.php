<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComptaEcritureRepository")
 */
class ComptaEcriture
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaExercice", inversedBy="comptaEcritures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $exercice;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $numero;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaCompteExercice", inversedBy="comptaEcritures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $debit;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaCompteExercice", inversedBy="comptaEcritures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $credit;

    /**
     * @ORM\Column(type="integer")
     */
    private $tva;

    /**
     * @ORM\Column(type="integer")
     */
    private $montant;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $deleted_by;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remarque;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_editable;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CustomerCommande", inversedBy="comptaEcriture", cascade={"persist", "remove"})
     */
    private $vente;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ProviderCommande", inversedBy="comptaEcriture", cascade={"persist", "remove"})
     */
    private $achat;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Settlement", inversedBy="comptaEcriture", cascade={"persist", "remove"})
     */
    private $reglement_client;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ProviderSettlement", inversedBy="comptaEcriture", cascade={"persist", "remove"})
     */
    private $regelement_fournisseur;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Depense", inversedBy="comptaEcriture", cascade={"persist", "remove"})
     */
    private $depense;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Acompte", cascade={"persist", "remove"})
     */
    private $acompte;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\RetraitAcompte", cascade={"persist", "remove"})
     */
    private $retrait_acompte;

    public function __construct()
    {
      $this->is_deleted  = false;
      $this->is_editable = false;
      $this->created_at  = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExercice(): ?ComptaExercice
    {
        return $this->exercice;
    }

    public function setExercice(?ComptaExercice $exercice): self
    {
        $this->exercice = $exercice;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDebit(): ?ComptaCompteExercice
    {
        return $this->debit;
    }

    public function setDebit(?ComptaCompteExercice $debit): self
    {
        $this->debit = $debit;

        return $this;
    }

    public function getCredit(): ?ComptaCompteExercice
    {
        return $this->credit;
    }

    public function setCredit(?ComptaCompteExercice $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    public function getTva(): ?int
    {
        return $this->tva;
    }

    public function setTva(int $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): self
    {
        $this->remarque = $remarque;

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

    public function getIsEditable(): ?bool
    {
        return $this->is_editable;
    }

    public function setIsEditable(bool $is_editable): self
    {
        $this->is_editable = $is_editable;

        return $this;
    }

    public function getVente(): ?CustomerCommande
    {
        return $this->vente;
    }

    public function setVente(?CustomerCommande $vente): self
    {
        $this->vente = $vente;

        return $this;
    }

    public function getAchat(): ?ProviderCommande
    {
        return $this->achat;
    }

    public function setAchat(?ProviderCommande $achat): self
    {
        $this->achat = $achat;

        return $this;
    }

    public function getReglementClient(): ?Settlement
    {
        return $this->reglement_client;
    }

    public function setReglementClient(?Settlement $reglement_client): self
    {
        $this->reglement_client = $reglement_client;

        return $this;
    }

    public function getRegelementFournisseur(): ?ProviderSettlement
    {
        return $this->regelement_fournisseur;
    }

    public function setRegelementFournisseur(?ProviderSettlement $regelement_fournisseur): self
    {
        $this->regelement_fournisseur = $regelement_fournisseur;

        return $this;
    }

    public function getDepense(): ?Depense
    {
        return $this->depense;
    }

    public function setDepense(?Depense $depense): self
    {
        $this->depense = $depense;

        return $this;
    }

    public function getAcompte(): ?Acompte
    {
        return $this->acompte;
    }

    public function setAcompte(?Acompte $acompte): self
    {
        $this->acompte = $acompte;

        return $this;
    }

    public function getRetraitAcompte(): ?RetraitAcompte
    {
        return $this->retrait_acompte;
    }

    public function setRetraitAcompte(?RetraitAcompte $retrait_acompte): self
    {
        $this->retrait_acompte = $retrait_acompte;

        return $this;
    }
}
