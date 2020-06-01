<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ClotureRepository")
 */
class Cloture
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ComptaExercice", inversedBy="clotures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $exercice;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_ventes;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_achats;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_acompte_clients;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_acompte_fournisseurs;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_depenses;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_creances;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_arrieres;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_entrees_caisse;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_entrees_banque;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_entrees_services_money;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_avoirs;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_benefices;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_remises;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $created_by;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_regelement_acompte;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_entrees;

    /**
     * @ORM\Column(type="integer")
     */
    private $total_reglement_fournisseur;

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

    public function getTotalVentes(): ?int
    {
        return $this->total_ventes;
    }

    public function setTotalVentes(int $total_ventes): self
    {
        $this->total_ventes = $total_ventes;

        return $this;
    }

    public function getTotalAchats(): ?int
    {
        return $this->total_achats;
    }

    public function setTotalAchats(int $total_achats): self
    {
        $this->total_achats = $total_achats;

        return $this;
    }

    public function getTotalAcompteClients(): ?int
    {
        return $this->total_acompte_clients;
    }

    public function setTotalAcompteClients(int $total_acompte_clients): self
    {
        $this->total_acompte_clients = $total_acompte_clients;

        return $this;
    }

    public function getTotalAcompteFournisseurs(): ?int
    {
        return $this->total_acompte_fournisseurs;
    }

    public function setTotalAcompteFournisseurs(int $total_acompte_fournisseurs): self
    {
        $this->total_acompte_fournisseurs = $total_acompte_fournisseurs;

        return $this;
    }

    public function getTotalDepenses(): ?int
    {
        return $this->total_depenses;
    }

    public function setTotalDepenses(int $total_depenses): self
    {
        $this->total_depenses = $total_depenses;

        return $this;
    }

    public function getTotalCreances(): ?int
    {
        return $this->total_creances;
    }

    public function setTotalCreances(int $total_creances): self
    {
        $this->total_creances = $total_creances;

        return $this;
    }

    public function getTotalArrieres(): ?int
    {
        return $this->total_arrieres;
    }

    public function setTotalArrieres(int $total_arrieres): self
    {
        $this->total_arrieres = $total_arrieres;

        return $this;
    }

    public function getTotalEntreesCaisse(): ?int
    {
        return $this->total_entrees_caisse;
    }

    public function setTotalEntreesCaisse(int $total_entrees_caisse): self
    {
        $this->total_entrees_caisse = $total_entrees_caisse;

        return $this;
    }

    public function getTotalEntreesBanque(): ?int
    {
        return $this->total_entrees_banque;
    }

    public function setTotalEntreesBanque(int $total_entrees_banque): self
    {
        $this->total_entrees_banque = $total_entrees_banque;

        return $this;
    }

    public function getTotalEntreesServicesMoney(): ?int
    {
        return $this->total_entrees_services_money;
    }

    public function setTotalEntreesServicesMoney(int $total_entrees_services_money): self
    {
        $this->total_entrees_services_money = $total_entrees_services_money;

        return $this;
    }

    public function getTotalAvoirs(): ?int
    {
        return $this->total_avoirs;
    }

    public function setTotalAvoirs(int $total_avoirs): self
    {
        $this->total_avoirs = $total_avoirs;

        return $this;
    }

    public function getTotalBenefices(): ?int
    {
        return $this->total_benefices;
    }

    public function setTotalBenefices(int $total_benefices): self
    {
        $this->total_benefices = $total_benefices;

        return $this;
    }

    public function getTotalRemises(): ?int
    {
        return $this->total_remises;
    }

    public function setTotalRemises(int $total_remises): self
    {
        $this->total_remises = $total_remises;

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

    public function getExercice(): ?ComptaExercice
    {
        return $this->exercice;
    }

    public function setExercice(?ComptaExercice $exercice): self
    {
        $this->exercice = $exercice;

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

    public function getTotalRegelementAcompte(): ?int
    {
        return $this->total_regelement_acompte;
    }

    public function setTotalRegelementAcompte(int $total_regelement_acompte): self
    {
        $this->total_regelement_acompte = $total_regelement_acompte;

        return $this;
    }

    public function getTotalEntrees(): ?int
    {
        return $this->total_entrees;
    }

    public function setTotalEntrees(int $total_entrees): self
    {
        $this->total_entrees = $total_entrees;

        return $this;
    }

    public function getTotalReglementFournisseur(): ?int
    {
        return $this->total_reglement_fournisseur;
    }

    public function setTotalReglementFournisseur(int $total_reglement_fournisseur): self
    {
        $this->total_reglement_fournisseur = $total_reglement_fournisseur;

        return $this;
    }

    
}
