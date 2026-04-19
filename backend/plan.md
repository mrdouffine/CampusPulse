# Plan d'Implémentation - CampusPulse API

## Phase 1: Authentification & Middlewares
- [x] Configuration API de Base (Sanctum)
- [ ] Création du Middleware `CheckRole` pour protéger les accès
- [ ] Mise en place du `routes/api.php` global

## Phase 2: Espace Administrateur (Admin API)
- [ ] `Admin\UserController` : CRUD complet pour les enseignants/étudiants/admins
- [ ] `Admin\CourseController` : Création des cours + Assignation aux professeurs + Inscription étudiants (enroll)
- [ ] `Admin\SettingController` : Mise à jour des paramètres (ex: seuil d'absence)
- [ ] `Admin\ReportController` : Récupération des rapports globaux

## Phase 3: Espace Enseignant (Teacher API)
- [ ] `Teacher\CourseController` : Liste des cours enseignés et détails
- [ ] `Teacher\SessionController` : Liste et création des séances de cours
- [ ] `Teacher\AttendanceController` : Validation de présence en lot (batch processing)
- [ ] `Teacher\EvaluationController` : Création des évaluations
- [ ] `Teacher\GradeController` : Saisie des notes en lot

## Phase 4: Espace Étudiant (Student API)
- [ ] `Student\DashboardController` : Statistiques (taux de présence global, moyenne)
- [ ] `Student\CourseController` : Détail des présences / notes par cours
- [ ] `Student\GradeController` : Historique des notes globales

## Phase 5: Formatage & Validation métiers
- [ ] `FormRequests` : Vérifier la sécurité et la conformité des données entrantes (ex: note stricte entre 0 et 20).
- [ ] `API Resources` : Formater la sortie JSON (pour renvoyer exactement ce dont le frontend aura besoin sans exposer des informations sensibles).
