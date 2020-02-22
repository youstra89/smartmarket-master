<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity(repositoryClass="App\Repository\UserRepository")
* @ORM\Table(name="user")
* @UniqueEntity(fields="email", message="Ce email est déjà pris")
* @UniqueEntity(fields="username", message="Ce lastname d'utilisateur est déjà pris")
*/
class User implements UserInterface, \Serializable
{
    const SEXE = [
      0 => 'Homme',
      1 => 'Femme',
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $gender;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $residence;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     */
    private $birthday;

    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = [];

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $pwd_changed_at;

    /**
     * @var string le token qui servira lors de l'oubli de mot de passe
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $token;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disabled = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $password_requested_at;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommande", mappedBy="seller")
     */
    private $customerCommandes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Settlement", mappedBy="receiver")
     */
    private $settlements;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_root;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $last_login;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Connexion", mappedBy="user")
     */
    private $connexions;


    public function __construct()
    {
        $this->is_root           = false;
        $this->is_deleted        = false;
        $this->created_at        = new \DateTime();
        $this->customerCommandes = new ArrayCollection();
        $this->settlements       = new ArrayCollection();
        $this->connexions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getResidence(): ?string
    {
        return $this->residence;
    }

    public function setResidence(string $residence): self
    {
        $this->residence = $residence;

        return $this;
    }


    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function getGenderType(): string
    {
      return self::SEXE[$this->gender];
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Retourne les rôles de l'user
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Afin d'être sûr qu'un user a toujours au moins 1 rôle
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
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

    public function getPwdChangedAt(): ?\DateTimeInterface
    {
        return $this->pwd_changed_at;
    }

    public function setPwdChangedAt(?\DateTimeInterface $pwd_changed_at): self
    {
        $this->pwd_changed_at = $pwd_changed_at;

        return $this;
    }

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }


    /**
     * Retour le salt qui a servi à coder le mot de passe
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        // See "Do you need to use a Salt?" at https://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // Nous n'avons pas besoin de cette methode car nous n'utilions pas de plainPassword
        // Mais elle est obligatoire car comprise dans l'interface UserInterface
        // $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        return serialize([$this->id, $this->username, $this->password]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        [$this->id, $this->username, $this->password] = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): self
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->password_requested_at;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $password_requested_at): self
    {
        $this->password_requested_at = $password_requested_at;

        return $this;
    }

    /**
     * @return Collection|CustomerCommande[]
     */
    public function getCustomerCommandes(): Collection
    {
        return $this->customerCommandes;
    }

    public function addCustomerCommande(CustomerCommande $customerCommande): self
    {
        if (!$this->customerCommandes->contains($customerCommande)) {
            $this->customerCommandes[] = $customerCommande;
            $customerCommande->setSeller($this);
        }

        return $this;
    }

    public function removeCustomerCommande(CustomerCommande $customerCommande): self
    {
        if ($this->customerCommandes->contains($customerCommande)) {
            $this->customerCommandes->removeElement($customerCommande);
            // set the owning side to null (unless already changed)
            if ($customerCommande->getSeller() === $this) {
                $customerCommande->setSeller(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Settlement[]
     */
    public function getSettlements(): Collection
    {
        return $this->settlements;
    }

    public function addSettlement(Settlement $settlement): self
    {
        if (!$this->settlements->contains($settlement)) {
            $this->settlements[] = $settlement;
            $settlement->setReceiver($this);
        }

        return $this;
    }

    public function removeSettlement(Settlement $settlement): self
    {
        if ($this->settlements->contains($settlement)) {
            $this->settlements->removeElement($settlement);
            // set the owning side to null (unless already changed)
            if ($settlement->getReceiver() === $this) {
                $settlement->setReceiver(null);
            }
        }

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

    public function getUpdatedBy(): ?self
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?self $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedBy(): ?self
    {
        return $this->deleted_by;
    }

    public function setDeletedBy(?self $deleted_by): self
    {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->last_login;
    }

    public function setLastLogin(?\DateTimeInterface $last_login): self
    {
        $this->last_login = $last_login;

        return $this;
    }

    /**
     * @return Collection|Connexion[]
     */
    public function getConnexions(): Collection
    {
        return $this->connexions;
    }

    public function addConnexion(Connexion $connexion): self
    {
        if (!$this->connexions->contains($connexion)) {
            $this->connexions[] = $connexion;
            $connexion->setUser($this);
        }

        return $this;
    }

    public function removeConnexion(Connexion $connexion): self
    {
        if ($this->connexions->contains($connexion)) {
            $this->connexions->removeElement($connexion);
            // set the owning side to null (unless already changed)
            if ($connexion->getUser() === $this) {
                $connexion->setUser(null);
            }
        }

        return $this;
    }
}
