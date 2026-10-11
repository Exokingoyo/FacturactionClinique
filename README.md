# Facturation Clinique

Application Laravel de gestion des services et des factures de la clinique.

## Utiliser une base commune pour tous les utilisateurs

L'application enregistre déjà les utilisateurs, les services et les factures dans
la base configurée par Laravel. Il n'y a pas de synchronisation manuelle à lancer :
chaque modification est enregistrée immédiatement. Pour que tous les utilisateurs
retrouvent les mêmes données, ils doivent utiliser la même instance de
l'application, connectée à une base centrale.

### Configuration recommandée

1. Créer une base MySQL ou MariaDB chez l'hébergeur de l'application.
2. Déployer l'application Laravel sur un serveur accessible aux utilisateurs.
   Les utilisateurs se connectent à son adresse web depuis leur navigateur ;
   ils ne se connectent pas directement au serveur MySQL.
3. Configurer le fichier `.env` du serveur avec les identifiants de la base :

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=adresse-de-la-base
   DB_PORT=3306
   DB_DATABASE=nom_de_la_base
   DB_USERNAME=utilisateur
   DB_PASSWORD=mot_de_passe
   ```

   Remplacer les valeurs d'exemple par celles de l'hébergeur. Ne pas committer
   ni partager le fichier `.env`.
4. Depuis le dossier de l'application, exécuter les migrations :

   ```bash
   php artisan optimize:clear
   php artisan migrate --force
   ```

   Ne pas utiliser `migrate:fresh` sur une base contenant des données : cette
   commande supprimerait les tables et leurs données.
5. Faire accéder tous les utilisateurs à la même adresse web de l'application.

Si plusieurs serveurs applicatifs sont nécessaires, ils doivent tous utiliser
les mêmes paramètres de base de données et la même `APP_KEY`. En revanche, pour
un déploiement simple avec un seul serveur Laravel, les utilisateurs partagent
déjà les données en utilisant cette seule instance.

### Données déjà présentes dans SQLite

Passer de SQLite à MySQL ne copie pas les données existantes. La migration crée
la structure des tables dans la nouvelle base, mais les anciennes factures,
services et comptes restent dans le fichier SQLite d'origine. Si cette base
contient des données à conserver, il faut les exporter puis les importer dans
MySQL avant de faire utiliser la nouvelle instance à tous les utilisateurs.

### Visibilité des factures

Le partage de la base ne change pas les droits de consultation : un utilisateur
voit ses propres factures, tandis qu'un administrateur peut consulter toutes les
factures. Les comptes et les services sont partagés par l'ensemble de
l'application.

### Créer des factures sans Internet

Après avoir ouvert le formulaire de nouvelle facture en étant connecté et en
ligne, l'utilisateur peut le laisser ouvert et créer des factures même si
Internet est ensuite interrompu. Le catalogue des prestations est celui chargé
lors de cette visite. Il faut garder le formulaire ouvert : l'application ne
peut pas être lancée dans une nouvelle fenêtre sans connexion.
Les factures non envoyées sont conservées dans le stockage local du navigateur
sur cet appareil, puis envoyées automatiquement lorsque la connexion revient
dans la page ouverte ou lorsque l'utilisateur revient dans l'application avec
le même compte. Les identifiants
uniques empêchent de créer un doublon si la réponse du serveur est perdue.

Cette fonction nécessite un navigateur moderne et une adresse HTTPS en
production. Elle ne permet pas de modifier les factures existantes hors ligne.
Les factures en attente ne sont pas partagées avec les autres appareils avant
leur synchronisation. Comme elles contiennent des renseignements de patients,
protégez l'accès à l'ordinateur et au profil du navigateur ; ne partagez pas un
profil de navigateur entre utilisateurs.

## Développement local

La configuration SQLite de `.env.example` reste adaptée au développement local.
Pour préparer une installation locale :

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```
