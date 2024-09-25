qa:
	composer update --prefer-stable
	vendor/bin/php-cs-fixer fix --diff --verbose
	vendor/bin/phpstan

qa-lowest:
	composer update --prefer-lowest
	vendor/bin/php-cs-fixer fix --diff --verbose
	vendor/bin/phpstan
