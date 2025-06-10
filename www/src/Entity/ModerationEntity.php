<?php
namespace App\Entity;

use DateTime;
use InvalidArgumentException;

class ModerationEntity
{
    private ?int $id = null;
    private string $typeAction;
    private ?string $description;
    private DateTime $dateAction;
    private int $moderateurId;
    private ?int $cibleUtilisateurId;
    private ?int $cibleArticleId;
    private ?int $cibleCommentaireId;

    // Constantes pour les types d'action
    public const TYPE_REFUS_ARTICLE = 'refus_article';
    public const TYPE_REFUS_COMMENTAIRE = 'refus_commentaire';
    public const TYPE_SIGNALEMENT = 'signalement';
    public const TYPE_SUPPRESSION_COMPTE = 'suppression_compte';

    public function __construct()
    {
        $this->dateAction = new DateTime();
        $this->description = null;
        $this->cibleUtilisateurId = null;
        $this->cibleArticleId = null;
        $this->cibleCommentaireId = null;
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
    public function getTypeAction(): string
    {
        return $this->typeAction;
    }

    /**
     * @param string $typeAction 
     * @return self
     */
    public function setTypeAction(string $typeAction): self
    {
        $validTypes = [
            self::TYPE_REFUS_ARTICLE,
            self::TYPE_REFUS_COMMENTAIRE,
            self::TYPE_SIGNALEMENT,
            self::TYPE_SUPPRESSION_COMPTE
        ];

        if (!in_array($typeAction, $validTypes)) {
            throw new InvalidArgumentException("Type d'action invalide");
        }

        $this->typeAction = $typeAction;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description 
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateAction(): DateTime
    {
        return $this->dateAction;
    }

    /**
     * @param DateTime|string $dateAction 
     * @return self
     */
    public function setDateAction($dateAction): self
    {
        if (is_string($dateAction)) {
            $dateAction = new DateTime($dateAction);
        } elseif (!($dateAction instanceof DateTime)) {
            throw new InvalidArgumentException("Le paramètre doit être une instance de DateTime ou une chaîne de date valide");
        }

        $this->dateAction = $dateAction;
        return $this;
    }

    /**
     * @return int
     */
    public function getModerateurId(): int
    {
        return $this->moderateurId;
    }

    /**
     * @param int $moderateurId 
     * @return self
     */
    public function setModerateurId(int $moderateurId): self
    {
        $this->moderateurId = $moderateurId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCibleUtilisateurId(): ?int
    {
        return $this->cibleUtilisateurId;
    }

    /**
     * @param int|null $cibleUtilisateurId 
     * @return self
     */
    public function setCibleUtilisateurId(?int $cibleUtilisateurId): self
    {
        $this->cibleUtilisateurId = $cibleUtilisateurId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCibleArticleId(): ?int
    {
        return $this->cibleArticleId;
    }

    /**
     * @param int|null $cibleArticleId 
     * @return self
     */
    public function setCibleArticleId(?int $cibleArticleId): self
    {
        $this->cibleArticleId = $cibleArticleId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCibleCommentaireId(): ?int
    {
        return $this->cibleCommentaireId;
    }

    /**
     * @param int|null $cibleCommentaireId 
     * @return self
     */
    public function setCibleCommentaireId(?int $cibleCommentaireId): self
    {
        $this->cibleCommentaireId = $cibleCommentaireId;
        return $this;
    }

    /**
     * Vérifie si la modération cible un article
     * @return bool
     */
    public function estModerationArticle(): bool
    {
        return $this->typeAction === self::TYPE_REFUS_ARTICLE && $this->cibleArticleId !== null;
    }

    /**
     * Vérifie si la modération cible un commentaire
     * @return bool
     */
    public function estModerationCommentaire(): bool
    {
        return $this->typeAction === self::TYPE_REFUS_COMMENTAIRE && $this->cibleCommentaireId !== null;
    }

    /**
     * Vérifie si la modération est un signalement d'utilisateur
     * @return bool
     */
    public function estSignalementUtilisateur(): bool
    {
        return $this->typeAction === self::TYPE_SIGNALEMENT && $this->cibleUtilisateurId !== null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type_action' => $this->typeAction,
            'description' => $this->description,
            'date_action' => $this->dateAction->format('Y-m-d H:i:s'),
            'moderateur_id' => $this->moderateurId,
            'cible_utilisateur_id' => $this->cibleUtilisateurId,
            'cible_article_id' => $this->cibleArticleId,
            'cible_commentaire_id' => $this->cibleCommentaireId
        ];
    }
}
