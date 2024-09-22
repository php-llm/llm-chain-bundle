qa:
	composer update --prefer-stable
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan

qa-lowest:
	composer update --prefer-lowest
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
