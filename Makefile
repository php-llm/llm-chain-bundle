qa:
	composer update --prefer-stable
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit

qa-lowest:
	composer update --prefer-lowest
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit

qa-dev:
	composer require php-llm/llm-chain:dev-main
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
	vendor/bin/phpunit
	# revert
	git restore composer.json

coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage
