<?php
namespace App\Service;

use App\Repository\ModerationDAO;
use App\Repository\UtilisateurDAO;
use App\Repository\ArticleDAO;
use App\Repository\CommentaireDAO;
use App\Entity\ModerationEntity;
use InvalidArgumentException;
use RuntimeException;

class ModerationService
{
    private ModerationDAO $moderationDAO;
    private UtilisateurDAO $utilisateurDAO;
    private ArticleDAO $articleDAO;
    private CommentaireDAO $commentaireDAO;

    public function __construct()
    {
        $this->moderationDAO = new ModerationDAO();
        $this->utilisateurDAO = new UtilisateurDAO();
        $this->articleDAO = new ArticleDAO();
        $this->commentaireDAO = new CommentaireDAO();
    }

    /**
     * Récupère l'historique des modérations
     * @param int $moderateurId
     * @param array $filtres
     * @return array
     */
    public function getHistoriqueModerations(int $moderateurId, array $filtres = []): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Appliquer les filtres
        if (!empty($filtres['type_action'])) {
            return $this->moderationDAO->findByTypeAction($filtres['type_action']);
        }

        if (!empty($filtres['moderateur_id'])) {
            return $this->moderationDAO->findByModerateur($filtres['moderateur_id']);
        }

        if (!empty($filtres['periode'])) {
            return $this->moderationDAO->findByPeriode(
                $filtres['periode']['debut'],
                $filtres['periode']['fin']
            );
        }

        return $this->moderationDAO->findAll();
    }

    /**
     * Récupère les actions récentes de modération
     * @param int $moderateurId
     * @param int $limit
     * @return array
     */
    public function getActionsRecentes(int $moderateurId, int $limit = 10): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        return $this->moderationDAO->findRecentActions($limit);
    }

    /**
     * Récupère l'historique de modération d'un article
     * @param int $articleId
     * @param int $moderateurId
     * @return array
     */
    public function getHistoriqueArticle(int $articleId, int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Vérifier que l'article existe
        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        return $this->moderationDAO->findByCibleArticle($articleId);
    }

    /**
     * Récupère l'historique de modération d'un commentaire
     * @param int $commentaireId
     * @param int $moderateurId
     * @return array
     */
    public function getHistoriqueCommentaire(int $commentaireId, int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Vérifier que le commentaire existe
        $commentaire = $this->commentaireDAO->findById($commentaireId);
        if (!$commentaire) {
            throw new RuntimeException("Commentaire introuvable");
        }

        return $this->moderationDAO->findByCibleCommentaire($commentaireId);
    }

    /**
     * Récupère l'historique des signalements d'un utilisateur
     * @param int $utilisateurCibleId
     * @param int $moderateurId
     * @return array
     */
    public function getHistoriqueSignalements(int $utilisateurCibleId, int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Vérifier que l'utilisateur cible existe
        $utilisateurCible = $this->utilisateurDAO->findById($utilisateurCibleId);
        if (!$utilisateurCible) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        return $this->moderationDAO->findByCibleUtilisateur($utilisateurCibleId);
    }

    /**
     * Récupère les statistiques de modération
     * @param int $moderateurId
     * @return array
     */
    public function getStatistiquesModerations(int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || !$moderateur->isAdministrateur()) {
            throw new RuntimeException("Seuls les administrateurs peuvent voir les statistiques");
        }

        $stats = [];

        // Compter par type d'action
        $stats['refus_articles'] = $this->moderationDAO->countByTypeAction(ModerationEntity::TYPE_REFUS_ARTICLE);
        $stats['refus_commentaires'] = $this->moderationDAO->countByTypeAction(ModerationEntity::TYPE_REFUS_COMMENTAIRE);
        $stats['signalements'] = $this->moderationDAO->countByTypeAction(ModerationEntity::TYPE_SIGNALEMENT);
        $stats['suppressions_compte'] = $this->moderationDAO->countByTypeAction(ModerationEntity::TYPE_SUPPRESSION_COMPTE);

        // Compter les éléments en attente
        $stats['articles_en_attente'] = $this->articleDAO->countByEtat('en_attente');
        $stats['commentaires_en_attente'] = $this->commentaireDAO->countByEtat('en_attente');

        // Total des actions de modération
        $stats['total_actions'] = $this->moderationDAO->countAll();

        return $stats;
    }

    /**
     * Récupère les statistiques d'un modérateur spécifique
     * @param int $moderateurCibleId
     * @param int $administrateurId
     * @return array
     */
    public function getStatistiquesModerateurSpecifique(int $moderateurCibleId, int $administrateurId): array
    {
        // Vérifier les permissions (seuls les admins)
        $administrateur = $this->utilisateurDAO->findById($administrateurId);
        if (!$administrateur || !$administrateur->isAdministrateur()) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Vérifier que le modérateur cible existe
        $moderateurCible = $this->utilisateurDAO->findById($moderateurCibleId);
        if (!$moderateurCible) {
            throw new RuntimeException("Modérateur introuvable");
        }

        $stats = [];
        $stats['moderateur'] = [
            'id' => $moderateurCible->getId(),
            'pseudonyme' => $moderateurCible->getPseudonyme(),
            'type_compte' => $moderateurCible->getTypeCompte()
        ];

        // Actions effectuées par ce modérateur
        $stats['actions_effectuees'] = $this->moderationDAO->countByModerateur($moderateurCibleId);
        $stats['actions_detaillees'] = $this->moderationDAO->findByModerateur($moderateurCibleId);

        return $stats;
    }

    /**
     * Récupère les signalements non traités
     * @param int $moderateurId
     * @return array
     */
    public function getSignalementsNonTraites(int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        return $this->moderationDAO->findSignalementsNonTraites();
    }

    /**
     * Récupère les détails complets des modérations
     * @param int $moderateurId
     * @param int $limit
     * @return array
     */
    public function getModerationDetails(int $moderateurId, int $limit = 50): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        return $this->moderationDAO->findWithDetails($limit);
    }

    /**
     * Enregistre une action de modération personnalisée
     * @param string $typeAction
     * @param string $description
     * @param int $moderateurId
     * @param array $cibles
     * @return ModerationEntity
     */
    public function enregistrerActionPersonnalisee(string $typeAction, string $description, int $moderateurId, array $cibles = []): ModerationEntity
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        // Créer l'action de modération
        $moderation = new ModerationEntity();
        $moderation->setTypeAction($typeAction)
            ->setDescription($description)
            ->setModerateurId($moderateurId);

        // Définir les cibles si spécifiées
        if (!empty($cibles['utilisateur_id'])) {
            $moderation->setCibleUtilisateurId($cibles['utilisateur_id']);
        }
        if (!empty($cibles['article_id'])) {
            $moderation->setCibleArticleId($cibles['article_id']);
        }
        if (!empty($cibles['commentaire_id'])) {
            $moderation->setCibleCommentaireId($cibles['commentaire_id']);
        }

        return $this->moderationDAO->save($moderation);
    }

    /**
     * Génère un rapport de modération pour une période donnée
     * @param string $dateDebut
     * @param string $dateFin
     * @param int $administrateurId
     * @return array
     */
    public function genererRapportPeriode(string $dateDebut, string $dateFin, int $administrateurId): array
    {
        // Vérifier les permissions (seuls les admins)
        $administrateur = $this->utilisateurDAO->findById($administrateurId);
        if (!$administrateur || !$administrateur->isAdministrateur()) {
            throw new RuntimeException("Seuls les administrateurs peuvent générer des rapports");
        }

        // Valider les dates
        if (!$this->validerFormatDate($dateDebut) || !$this->validerFormatDate($dateFin)) {
            throw new InvalidArgumentException("Format de date invalide (attendu: Y-m-d H:i:s)");
        }

        $actions = $this->moderationDAO->findByPeriode($dateDebut, $dateFin);

        $rapport = [
            'periode' => [
                'debut' => $dateDebut,
                'fin' => $dateFin
            ],
            'resume' => [
                'total_actions' => count($actions),
                'refus_articles' => 0,
                'refus_commentaires' => 0,
                'signalements' => 0,
                'suppressions_compte' => 0
            ],
            'details' => $actions,
            'moderateurs_actifs' => []
        ];

        // Analyser les actions
        $moderateursStats = [];
        foreach ($actions as $action) {
            // Compter par type
            switch ($action->getTypeAction()) {
                case ModerationEntity::TYPE_REFUS_ARTICLE:
                    $rapport['resume']['refus_articles']++;
                    break;
                case ModerationEntity::TYPE_REFUS_COMMENTAIRE:
                    $rapport['resume']['refus_commentaires']++;
                    break;
                case ModerationEntity::TYPE_SIGNALEMENT:
                    $rapport['resume']['signalements']++;
                    break;
                case ModerationEntity::TYPE_SUPPRESSION_COMPTE:
                    $rapport['resume']['suppressions_compte']++;
                    break;
            }

            // Compter par modérateur
            $modId = $action->getModerateurId();
            if (!isset($moderateursStats[$modId])) {
                $moderateursStats[$modId] = 0;
            }
            $moderateursStats[$modId]++;
        }

        // Ajouter les stats des modérateurs
        foreach ($moderateursStats as $modId => $nbActions) {
            $mod = $this->utilisateurDAO->findById($modId);
            if ($mod) {
                $rapport['moderateurs_actifs'][] = [
                    'id' => $modId,
                    'pseudonyme' => $mod->getPseudonyme(),
                    'nb_actions' => $nbActions
                ];
            }
        }

        return $rapport;
    }

    /**
     * Valide le format d'une date
     * @param string $date
     * @return bool
     */
    private function validerFormatDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $dateTime && $dateTime->format('Y-m-d H:i:s') === $date;
    }
}
