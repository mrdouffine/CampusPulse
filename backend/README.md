# CampusPulse - Backend API (Laravel 11)

Ce dépôt contient le code source de l'API robuste **CampusPulse**, bâtie pour gérer avec efficacité et sécurité une application de suivi d'étudiants, soutenue par une architecture API REST stricte.

## Stack Technique
*   **Framework :** Laravel 11.x
*   **Base de données :** MySQL 8.x (via Laravel Sail / Docker)
*   **Authentification :** Laravel Sanctum (SPA Session Cookie)
*   **Tests Automatisés :** Pest PHP

## 🚀 Guide d'installation rapide

L'environnement de développement repose entièrement sur **Docker** grâce à `Laravel Sail`. Vous n'avez pas besoin d'installer PHP ou MySQL sur votre machine pour travailler sur ce projet.

1.  **Cloner le dépôt et se placer dans le backend :**
    ```bash
    git clone https://github.com/mrdouffine/CampusPulse.git
    cd CampusPulse/backend
    ```

2.  **Installer les dépendances PHP :**
    *(Utilisez l'image Docker légère officielle pour installer composer si vous n'avez pas PHP en local)*
    ```bash
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php8.3-composer:latest \
        composer install --ignore-platform-reqs
    ```

3.  **Préparer l'environnement :**
    ```bash
    cp .env.example .env
    ```

4.  **Démarrer le serveur API :**
    ```bash
    ./vendor/bin/sail up -d
    ```

5.  **Générer la base de données et la clé de l'application :**
    ```bash
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail artisan migrate --seed
    ```

Votre API Backend est désormais accessible à l'adresse **`http://localhost`**.

---

## 🔐 Données de test (Seeders)

La base de données est pré-populée pour faciliter les développements Frontend. Les mots de passe sont tous configurés sur : **`password`**

*   **Administrateur :** `admin@campuspulse.test`
*   **Enseignant :** `turing@campuspulse.test` / `curie@campuspulse.test`
*   **Étudiants :** `student1@campuspulse.test` jusqu'à `student30@campuspulse.test`

---

## 📡 Comment consommer l'API Frontend ?

L'authentification utilise les cookies de session Stateful (Sanctum SPA). Pour se connecter via Postman ou le Frontend Javascript (Axios) :

1.  Demandez le cookie CSRF (Automatique avec Axios) :
    `GET http://localhost/sanctum/csrf-cookie`
2.  Connectez-vous :
    `POST http://localhost/login` (Body JSON : `email` + `password` avec un Header *Accept: application/json*)
3.  Interrogez les routes :
    *   `GET http://localhost/api/student/dashboard`
    *   `GET http://localhost/api/teacher/courses`
    *   `POST http://localhost/api/admin/users`

*💡 Note pour le développement sur Postman : Pour gagner en agilité avec vos tests API, la protection CSRF a été exceptionnellement contournée pour les connexions sur localhost dans le fichier `bootstrap/app.php`.*

---

## ✅ Lancer les Tests automatisés
L'application dispose de sa propre suite de tests fonctionnels avec Pest pour tester ses actions principales (sécurité, validation par lot, accès...). 

Pour exécuter les tests :
```bash
./vendor/bin/sail bin pest
```
