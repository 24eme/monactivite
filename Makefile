all: .make composer.phar app/config/parameters.yml app/bootstrap.php.cache composer.json .make/db .make/schema .make/fixtures

.make:
	mkdir .make

composer.phar:
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('SHA384', 'composer-setup.php').\"\n\" === file_get_contents('https://composer.github.io/installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"

app/config/parameters.yml:
	cp app/config/parameters.yml.dist app/config/parameters.yml

app/bootstrap.php.cache:
	php composer.phar install

.make/db:
	php app/console doctrine:database:create && echo "1" > .make/db

.make/schema:
	php app/console doctrine:schema:update --force && echo "1" > .make/schema

.make/fixtures:
	php app/console doctrine:fixtures:load --append && echo "1" > .make/fixtures

clean:
	rm -rf .make
