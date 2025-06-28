<?php
namespace App\Entity;

use DateTime;
use InvalidArgumentException;

class CommentaireEntity
{
    private ?int $id = null;
    private string $contenu;
    private DateTime $dateCreation;
    private DateTime $dateModification;
    private string $etat;
    private int $utilisateurId;
    private int $articleId;

    // Constantes pour les états possibles
    public const ETAT_EN_ATTENTE = 'en_attente';
    public const ETAT_ACCEPTE = 'accepte';
    public const ETAT_REFUSE = 'refuse';
    public const ETAT_EFFACE = 'efface';

    public function __construct()
    {
        $this->dateCreation = new DateTime();
        $this->dateModification = new DateTime();
        $this->etat = self::ETAT_EN_ATTENTE;
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
        $this->updateModification();
        $this->etat = self::ETAT_EN_ATTENTE; // Repasse en modération
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
     * @param DateTime|string $dateCreation 
     * @return self
     */
    public function setDateCreation($dateCreation): self
    {
        if (is_string($dateCreation)) {
            $dateCreation = new DateTime($dateCreation);
        } elseif (!($dateCreation instanceof DateTime)) {
            throw new InvalidArgumentException("Le paramètre doit être une instance de DateTime ou une chaîne de date valide");
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
     * @param DateTime|string $dateModification 
     * @return self
     */
    public function setDateModification($dateModification): self
    {
        if (is_string($dateModification)) {
            $dateModification = new DateTime($dateModification);
        } elseif (!($dateModification instanceof DateTime)) {
            throw new InvalidArgumentException("Le paramètre doit être une instance de DateTime ou une chaîne de date valide");
        }

        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * Met à jour la date de modification à maintenant
     * @return self
     */
    public function updateModification(): self
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
     * @return int
     */
    public function getArticleId(): int
    {
        return $this->articleId;
    }

    /**
     * @param int $articleId 
     * @return self
     */
    public function setArticleId(int $articleId): self
    {
        $this->articleId = $articleId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAccepte(): bool
    {
        return $this->etat === self::ETAT_ACCEPTE;
    }

    /**
     * @return bool
     */
    public function isEnAttente(): bool
    {
        return $this->etat === self::ETAT_EN_ATTENTE;
    }

    /**
     * @return bool
     */
    public function isRefuse(): bool
    {
        return $this->etat === self::ETAT_REFUSE;
    }

    /**
     * @return bool
     */
    public function isEfface(): bool
    {
        return $this->etat === self::ETAT_EFFACE;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'date_creation' => $this->dateCreation->format('Y-m-d H:i:s'),
            'date_modification' => $this->dateModification->format('Y-m-d H:i:s'),
            'etat' => $this->etat,
            'utilisateur_id' => $this->utilisateurId,
            'article_id' => $this->articleId
        ];
    }
}
