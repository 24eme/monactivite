Mon Activité
============

Mon activité est un outil pour récupérer son activité quotidienne à partir de mail, commit, flux RSS etc...

![Page d'acceuil](docs/interface_home.jpg "Page d'acceuil")
![Liste des sources](docs/interface_source.jpg "Liste des sources")

Installation
------------

Récupérer le projet

> git clone https://github.com/24eme/monactivite.git

Récupération locale de composer (optionnelle si vous l'avez déjà installé au global)

> https://getcomposer.org/download/

Récupération des vendors via composer

> php composer.phar install

Création et construction de la base de données

> php app/console doctrine:database:create

> php app/console doctrine:schema:update --force

Chargement des données de base

> php app/console doctrine:fixtures:load

Lancement de l'application

> php app/console server:start
