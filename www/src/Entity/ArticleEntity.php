<?php
namespace App\Entity;

use DateTime;
use InvalidArgumentException;


class ArticleEntity
{
    private ?int $id = null;
    private string $titre;
    private string $contenu;
    private DateTime|string $dateCreation;
    private DateTime|string $dateModification;
    private string $etat;
    private string $visibilite;
    private int $utilisateurId;


    // Constantes
    public const ETAT_EN_ATTENTE = 'en_attente';
    public const ETAT_ACCEPTE = 'accepte';
    public const ETAT_REFUSE = 'refuse';
    public const ETAT_EFFACE = 'efface';

    public const VISIBILITE_PUBLIC = 'public';
    public const VISIBILITE_PRIVE = 'prive';


    public function __construct()
    {
        $this->dateCreation = new DateTime();
        $this->dateModification = new DateTime();
        $this->etat = self::ETAT_EN_ATTENTE;
        $this->visibilite = self::VISIBILITE_PUBLIC;
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
    public function getTitre(): string
    {
        return $this->titre;
    }

    /**
     * @param string $titre 
     * @return self
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        $this->etat = self::ETAT_EN_ATTENTE;
        $this->updateDateModification();
        return $this;
    }

    /**
     * @return string
     */
    public function getContenu(): string
    {
        return $this->contenu;
    }

    /**
     * @param string $contenu 
     * @return self
     */
    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        $this->etat = self::ETAT_EN_ATTENTE;
        $this->updateDateModification();
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
     * @return DateTime
     */
    public function getDateModification(): DateTime
    {
        return $this->dateModification;
    }

    /**
     * @param DateTime $dateModification 
     * @return self
     */
    public function setDateModification(DateTime|string $dateModification): self
    {
        if (is_string($dateModification)) {
            $dateModification = new DateTime($dateModification);
        }

        $this->dateModification = $dateModification;
        return $this;
    }

    public function updateDateModification(): self
    {
        $this->dateModification = new DateTime();
        return $this;
    }

    /**
     * @return string
     */
    public function getEtat(): string
    {
        return $this->etat;
    }

    /**
     * @param string $etat 
     * @return self
     */
    public function setEtat(string $etat): self
    {
        if (!in_array($etat, [self::ETAT_ACCEPTE, self::ETAT_EN_ATTENTE, self::ETAT_EFFACE, self::ETAT_REFUSE])) {
            throw new InvalidArgumentException("Etat invalide");
        }

        $this->etat = $etat;
        return $this;
    }

    public function isAccepte(): bool
    {
        return $this->etat === self::ETAT_ACCEPTE;
    }

    public function isEnAttente(): bool
    {
        return $this->etat === self::ETAT_EN_ATTENTE;
    }

    public function isEfface(): bool
    {
        return $this->etat === self::ETAT_EFFACE;
    }

    public function isRefuse(): bool
    {
        return $this->etat === self::ETAT_REFUSE;
    }


    /**
     * @return string
     */
    public function getVisibilite(): string
    {
        return $this->visibilite;
    }

    /**
     * @param string $visibilite 
     * @return self
     */
    public function setVisibilite(string $visibilite): self
    {
        if (!in_array($visibilite, [self::VISIBILITE_PRIVE, self::VISIBILITE_PUBLIC])) {
            throw new InvalidArgumentException("Visibilite invalide");
        }

        $this->visibilite = $visibilite;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->visibilite === self::VISIBILITE_PUBLIC;
    }

    public function isPrive(): bool
    {
        return $this->visibilite === self::VISIBILITE_PRIVE;
    }

    /**
     * @return int
     */
    public function getUtilisateurId(): int
    {
        return $this->utilisateurId;
    }

    /**
     * @param int $utilisateurId 
     * @return self
     */
    public function setUtilisateurId(int $utilisateurId): self
    {
        $this->utilisateurId = $utilisateurId;
        return $this;
    }

    /**
     * Summary of toArray
     * @return array{contenu: string, date_creation: string, date_modification: string, etat: string, id: int, titre: string, utilisateur_id: int, visibilite: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'date_creation' => $this->dateCreation->format('Y-m-d H:i:s'),
            'date_modification' => $this->dateModification->format('Y-m-d H:i:s'),
            'etat' => $this->etat,
            'visibilite' => $this->visibilite,
            'utilisateur_id' => $this->utilisateurId
        ];
    }
}