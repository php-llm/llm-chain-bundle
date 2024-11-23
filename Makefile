qa:
	composer update --prefer-stable
	PHP_CS_FIXER_IGNORE_ENV=true vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit

qa-lowest:
	composer update --prefer-lowest
	PHP_CS_FIXER_IGNORE_ENV=true vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit

qa-dev:
	composer require php-llm/llm-chain:dev-main
	PHP_CS_FIXER_IGNORE_ENV=true vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit
	# revert
	git restore composer.json

coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage
