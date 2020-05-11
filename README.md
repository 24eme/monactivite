Mon Activité [![Build Status](https://travis-ci.org/24eme/monactivite.svg?branch=master)](https://travis-ci.org/24eme/monactivite)
============

Mon activité est un outil pour récupérer son activité quotidienne à partir de mail, commit, flux RSS, calendrier etc...

![Page d'acceuil](docs/interface_home.jpg "Page d'acceuil")
![Liste des sources](docs/interface_source.jpg "Liste des sources")

Installation
------------

Récupérer le projet

    git clone https://github.com/24eme/monactivite.git

### Compatibilité

 - php 5.6
 - php 7.0
 - php 7.1
 - php 7.2
 - php 7.3

### Dépendances

Sous debian, voici les dépendances de librairies php :

 - php-curl
 - php-dom
 - php-imap
 - php-mbstring

Ainsi que la librairie de la base de données choisie :

 - php-sqlite3 (par défaut)
 - php-mysql
 - php-pgsql

### Installation automatisée

Lancer simplement la commande :

    make install

L'installation se fera avec sqlite, la bdd est stockée dans le fichier data/monactivite.db3

### Installation pas à pas

Installation de composer (optionnelle si vous l'avez déjà installé en global)

    https://getcomposer.org/download/

Copier le fichier de configuration

    cp app/config/parameters.yml{.dump,}

Récupération des libairies externes via composer

    composer install

Création et construction de la base de données

    php bin/console doctrine:database:create
    php bin/console doctrine:schema:update --force

Chargement des données initiales

    php bin/console doctrine:fixtures:load --append

### Lancer l'application

    php bin/console server:start


Contribuer
----------

### Tests

Lancer les tests unitaires

    make test
