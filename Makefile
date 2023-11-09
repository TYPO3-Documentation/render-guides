PHP_BIN ?= docker run -it --rm --user $$(id -u):$$(id -g) -v${PWD}:/opt/project -w /opt/project php:8.2-cli php -d memory_limit=1024M
PHP_PROJECT_BIN = docker run -i --rm --user $$(id -u):$$(id -g) -v${PWD}:/project typo3-docs:local php -d memory_limit=1024M
## This can be adapted in the .git/hooks/pre-commit call of this step. If you change this line (i.e. new PHP version), also change it in that template

.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: code-style
code-style:
	$(PHP_BIN) vendor/bin/php-cs-fixer check

.PHONY: fix-code-style
fix-code-style:
	$(PHP_BIN) vendor/bin/php-cs-fixer fix

.PHONY: static-code-analysis
static-code-analysis: vendor phpstan ## Runs a static code analysis with phpstan/phpstan

.PHONY: phpstan-baseline
phpstan-baseline:
	$(PHP_BIN) -d memory_limit=1024M vendor/bin/phpstan --configuration=phpstan.neon --generate-baseline

.PHONY: phpstan
phpstan:
	$(PHP_BIN) -d memory_limit=1024M vendor/bin/phpstan --configuration=phpstan.neon

.PHONE: githooks
githooks: ## Runs script that injects githook pre-commit, so that 'make pre-commit-test' is performed on each commit.
	./tools/add-githooks.sh

.PHONY: test
test: test-integration test-unit test-docs## Runs all test suites with phpunit/phpunit

.PHONY: test-unit
test-unit: ## Runs integration tests with phpunit/phpunit
	$(PHP_BIN) vendor/bin/phpunit --testsuite=unit

.PHONY: test-integration
test-integration: ## Runs integration tests with phpunit/phpunit
	$(PHP_BIN) vendor/bin/phpunit --testsuite=integration

.PHONY: test-docs
test-docs: ## Generate projects docs without warnings
	$(PHP_BIN) vendor/bin/guides -vvv --no-progress Documentation --output="/tmp/test" --fail-on-log

.PHONY: cleanup
cleanup: cleanup-tests cleanup-cache

.PHONY: cleanup-tests
cleanup-tests: ## Cleans up temp directories created by test-integration
	@find ./tests -type d -name 'temp' -exec sudo rm -rf {} \;

.PHONY: cleanup-cache
cleanup-cache:
	@sudo rm -rf .cache

.PHONY: test-monorepo
test-monorepo:
	$(PHP_PROJECT_BIN) ./vendor/bin/monorepo-builder validate

vendor: composer.json composer.lock
	$(PHP_PROJECT_BIN) composer validate --no-check-publish
	$(PHP_PROJECT_BIN) composer install --no-interaction --no-progress  --ignore-platform-reqs

.PHONY: docs
docs: ## Generate projects docs
	$(PHP_BIN) vendor/bin/guides -vvv --no-progress Documentation

.PHONY: pre-commit-test
pre-commit-test: fix-code-style test code-style static-code-analysis test-monorepo
