run:
	docker compose up -d
test:
	docker exec -it php-app /var/www/vendor/phpunit/phpunit/phpunit
