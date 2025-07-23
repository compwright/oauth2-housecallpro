test:
	vendor/bin/phpstan analyse --level 9 src tests
	XDEBUG_MODE=coverage vendor/bin/phpunit src tests

style:
	vendor/bin/php-cs-fixer fix
