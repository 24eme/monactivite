all:

install: app/config/parameters.yml vendor/autoload.php data/monactivite.db3

app/config/parameters.yml:
	cp app/config/parameters.yml.dist app/config/parameters.yml

vendor/autoload.php:
	composer install

data/monactivite.db3:
	php bin/console doctrine:database:create
	php bin/console doctrine:schema:update --force
	php bin/console doctrine:fixtures:load --append

test:
	php bin/simple-phpunit
