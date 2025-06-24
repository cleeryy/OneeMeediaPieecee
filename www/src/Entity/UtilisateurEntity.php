<?php
namespace App\Entity;

use DateTime;
use InvalidArgumentException;

class UtilisateurEntity
{
    private ?int $id = null;
    private string $email;
    private string $motDePasse;
    private string $pseudonyme;
    private DateTime|string $dateCreation;
    private string $typeCompte;
    private string $etatCompte;
    private bool $estBanni;

    // Constantes pour le type de compte
    public const TYPE_ADMINISTRATEUR = 'administrateur';
    public const TYPE_MODERATEUR = 'moderateur';
    public const TYPE_REDACTEUR = 'redacteur';

    // Constantes pour l'état du compte
    public const ETAT_EN_ATTENTE = 'en_attente';
    public const ETAT_VALIDE = 'valide';
    public const ETAT_REFUSE = 'refuse';


    public function __construct()
    {
        $this->dateCreation = new DateTime();
        $this->typeCompte = self::TYPE_REDACTEUR;
        $this->etatCompte = self::ETAT_EN_ATTENTE;
        $this->estBanni = false;
    }

    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    }


    /**
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param ?int $id 
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email 
     * @return self
     */
    public function setEmail(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Format d'email invalide");
        }

        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    /**
     * @param string $motDePasse 
     * @return self
     */
    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    /**
     * @return string
     */
    public function getPseudonyme(): string
    {
        return $this->pseudonyme;
    }

    /**
     * @param string $pseudonyme 
     * @return self
     */
    public function setPseudonyme(string $pseudonyme): self
    {
        $this->pseudonyme = $pseudonyme;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreation(): DateTime
    {
        return $this->dateCreation;
    }

    /**
     * @param DateTime $dateCreation 
     * @return self
     */
    public function setDateCreation(DateTime|string $dateCreation): self
    {
        if (is_string($dateCreation)) {
            $dateCreation = new DateTime($dateCreation);
        }

        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeCompte(): string
    {
        return $this->typeCompte;
    }

    /**
     * @param string $typeCompte 
     * @return self
     */
    public function setTypeCompte(string $typeCompte): self
    {
        if (!in_array($typeCompte, [self::TYPE_ADMINISTRATEUR, self::TYPE_MODERATEUR, self::TYPE_REDACTEUR])) {
            throw new InvalidArgumentException("Type de compte invalide");
        }
        $this->typeCompte = $typeCompte;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAdministrateur(): bool
    {
        return $this->typeCompte === self::TYPE_ADMINISTRATEUR;
    }

    /**
     * @return bool
     */
    public function isModerateur(): bool
    {
        return $this->typeCompte === self::TYPE_MODERATEUR;
    }

    /**
     * @return bool
     */
    public function isRedacteur(): bool
    {
        return $this->typeCompte === self::TYPE_REDACTEUR;
    }

    /**
     * @return string
     */
    public function getEtatCompte(): string
    {
        return $this->etatCompte;
    }

    /**
     * @param string $etatCompte 
     * @return self
     */
    public function setEtatCompte(string $etatCompte): self
    {
        if (!in_array($etatCompte, [self::ETAT_EN_ATTENTE, self::ETAT_VALIDE, self::ETAT_REFUSE])) {
            throw new InvalidArgumentException("État de compte invalide");
        }
        $this->etatCompte = $etatCompte;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCompteValide(): bool
    {
        return $this->etatCompte === self::ETAT_VALIDE;
    }

    /**
     * @return bool
     */
    public function isCompteEnAttente(): bool
    {
        return $this->etatCompte === self::ETAT_EN_ATTENTE;
    }

    /**
     * @return bool
     */
    public function isCompteRefuse(): bool
    {
        return $this->etatCompte === self::ETAT_REFUSE;
    }

    /**
     * @return bool
     */
    public function getEstBanni(): bool
    {
        return $this->estBanni;
    }

    /**
     * @param bool $estBanni 
     * @return self
     */
    public function setEstBanni(bool $estBanni): self
    {
        $this->estBanni = $estBanni;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'pseudonyme' => $this->pseudonyme,
            'date_creation' => $this->dateCreation->format('Y-m-d H:i:s'),
            'type_compte' => $this->typeCompte,
            'etat_compte' => $this->etatCompte,
            'est_banni' => $this->estBanni
        ];
    }
}