## Global PHP arguments, applied to both docker and local execution
PHP_ARGS ?= -d memory_limit=1024M -d date.timezone=UTC

## Docker wrapper, for raw php commands (so it's not required on the host)
## This container has no runtime for the `guides` project!
PHP_BIN ?= docker run -i --rm --user $$(id -u):$$(id -g) -v${PWD}:/opt/project -w /opt/project php:8.5-cli php $(PHP_ARGS)

## Docker wrapper to use for a typo3-docs:local container.
## This container provides a runtime for the `guides` project
PHP_PROJECT_BIN ?= docker run -i --rm --user $$(id -u):$$(id -g) -v${PWD}:/project typo3-docs:local

## Docker wrapper to use for a typo3-docs:local container.
## This container provides a composer-runtime; mounts project on /app
PHP_COMPOSER_BIN ?= docker run -i --rm --user $$(id -u):$$(id -g) -v${PWD}:/app composer:2

## These variables can be overriden by other tasks, i.e. by `make PHP_ARGS=-d memory_limit=2G pre-commit-tests`.
## The "--user" argument is required for macOS to pass along ownership of /project

## NOTE: Dependencies listed here (PHP 8.5, composer 2) need to be kept
##       in sync with those inside the Dockerfile and composer.json

## Parse the "make (target) ENV=(local|docker)" argument to set the environment. Defaults to docker.
ifdef ENV
	ifeq ($(ENV),local)
		PHP_BIN = php $(PHP_ARGS)
		PHP_PROJECT_BIN = php $(PHP_ARGS) ./vendor/bin/guides
		PHP_COMPOSER_BIN = composer
		ENV_INFO=ENVIRONMENT: Local (also DDEV)
	else
		ENV_INFO=ENVIRONMENT: Docker
	endif
else
	ENV_INFO=ENVIRONMENT: Docker (default)
endif

.PHONY: help
help: ## Displays this list of targets with descriptions
	@echo "You prepend/append the argument 'ENV=(local|docker)' to each target. This specifies,"
	@echo "whether to execute the target within your local environment, or docker (default).\n"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

## LIST: Targets that can be executed directly

.PHONE: assets
assets: ## Builds all assets (Css, JavaScript, Fonts etc).
	ddev npm-build

.PHONE: assets-install
assets-install: ## Installs the node-modules needed to build the assets.
	ddev npm-ci

.PHONE: assets-debug
assets-debug: ## Builds assets, keeping the sourcemap. It copies the output files directly into Documentation-GENERATED-temp so they can be tested without reloading.
	ddev npm-debug

.PHONE: assets-watch
assets-watch: ## Watches changes of sass files and build automatically on change
	ddev npm-watch

.PHONE: build-phar
build-phar: ## Creates a guides.phar file (github workflow)
	./tools/build-phar.sh

.PHONY: cleanup
cleanup: cleanup-tests cleanup-cache

.PHONY: cleanup-cache
cleanup-cache: ## Cleans up phpstan .cache directory
	@sudo rm -rf .cache

.PHONY: cleanup-tests
cleanup-tests: ## Cleans up temp directories created by test-integration
	@find ./tests -type d -name 'temp' -exec sudo rm -rf {} \;

.PHONY: code-style
code-style: ## Executes php-cs-fixer with "check" option
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/php-cs-fixer check

.PHONY: docs
docs: ## Generate projects docs (from "Documentation" directory)
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/guides --no-progress --config=Documentation

.PHONY: docker-build
docker-build: ## Build docker image 'typo3-docs:local' for local debugging
	docker build -t typo3-docs:local .

.PHONY: fix-code-style
fix-code-style: ## Executes php-cs-fixer with "fix" option
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/php-cs-fixer fix

.PHONY: composer-normalize
composer-normalize: ## Normalizes composer.json
	@echo "$(ENV_INFO)"
	$(PHP_COMPOSER_BIN) normalize

.PHONE: githooks
githooks: ## Runs script that injects githooks (for contributors)
	./tools/add-githooks.sh

.PHONY: monorepo
monorepo: ## Runs monorepo-builder
	@echo "$(ENV_INFO)"
	$(PHP_BIN) ./vendor/bin/monorepo-builder merge

.PHONY: phpstan
phpstan: ## Execute phpstan
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/phpstan --configuration=phpstan.neon

.PHONY: phpstan-baseline
phpstan-baseline: ## Generates phpstan baseline
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/phpstan --configuration=phpstan.neon --generate-baseline

.PHONY: show-env
show-env: ## Shows PHP environment options (buildinfo)
	@echo "$(ENV_INFO)"
	@echo "Base PHP:"
	$(PHP_BIN) --version
	@echo ""

	@echo "Project within Docker:"
	docker run --rm --user $$(id -u):$$(id -g) -v${PWD}:/project typo3-docs:local --version
	$(PHP_PROJECT_BIN) --version
	@echo ""

.PHONY: test
test: test-integration test-unit test-xml test-docs test-rendertest ## Runs all test suites with phpunit/phpunit

.PHONY: test-docs
test-docs: ## Runs project generation tests
	@echo "$(ENV_INFO)"
	rm -rf /tmp/test && $(PHP_BIN) vendor/bin/guides --no-progress Documentation --output="/tmp/test" --config=Documentation --minimal-test

.PHONY: test-rendertest
test-rendertest: ## Runs rendering with Documentation-rendertest
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/guides --no-progress Documentation-rendertest --output="Documentation-GENERATED-rendertest" --config=Documentation-rendertest --minimal-test

.PHONY: rendertest
rendertest: ## Runs rendering with Documentation-rendertest
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/guides --no-progress Documentation-rendertest --output="Documentation-GENERATED-rendertest" --config=Documentation-rendertest

.PHONY: mdtest
mdtest: ## Runs rendering with Documentation-markdowntest
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/guides --no-progress Documentation-markdowntest --output="Documentation-GENERATED-markdowntest" --config=Documentation-markdowntest

.PHONY: rendertestassets
rendertestassets: assets rendertest ## Rebuild assets and make rendertest

.PHONY: test-integration
test-integration: ## Runs integration tests with phpunit
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/phpunit --testsuite=integration

.PHONY: integration-baseline
integration-baseline: ## Copies the output files of the integration tests into the expected directories, making a new baseline.
	@echo "$(ENV_INFO)"
	-$(PHP_BIN) vendor/bin/phpunit --testsuite=integration
	$(PHP_BIN) tools/integration-test-baseline.php

.PHONY: test-monorepo
test-monorepo: ## Runs monorepo-builder tests
	@echo "$(ENV_INFO)"
	$(PHP_BIN) ./vendor/bin/monorepo-builder validate

.PHONY: test-unit
test-unit: ## Runs unit tests with phpunit
	@echo "$(ENV_INFO)"
	$(PHP_BIN) vendor/bin/phpunit --testsuite=unit

.PHONY: test-xml
test-xml: ## Lint all guides.xml
	$(PHP_BIN) packages/typo3-guides-cli/bin/typo3-guides lint-guides-xml

.PHONY: migrate-settings
migrate-settings: ## Migrate Settings.cfg to guides.xml
	@if [ -z "$(path)" ]; then \
        echo "Please provide a path using 'make migrate-settings path=/your/path'"; \
        exit 1; \
    fi
	$(PHP_BIN) packages/typo3-guides-cli/bin/typo3-guides migrate $(path)

api-docs: .phpdoc/template phpdoc.dist.xml ## Generate API documentation
	docker run -i --rm --user $$(id -u):$$(id -g) -v${PWD}:/data phpdoc/phpdoc:3  --setting template.typo3_version=12.4

.HIDDEN: .phpdoc/template phpdoc.dist.xml
.phpdoc/template:
	@mkdir -p .phpdoc
	cp -r ${PWD}/packages/typo3-api/template .phpdoc/template

phpdoc.dist.xml:
	cp ${PWD}/packages/typo3-api/phpdoc.dist.xml phpdoc.dist.xml

clone-typo3:
	git clone git@github.com:TYPO3/typo3.git .Build/TYPO3

## LIST: Compound targets that are triggers for others.

.PHONY: cleanup
cleanup: cleanup-tests cleanup-cache ## Runs all cleanup tasks

.PHONY: pre-commit-test
pre-commit-test: composer-normalize fix-code-style test code-style static-code-analysis test-monorepo ## Runs all tests and code guideline checks (for contributors)

.PHONY: static-code-analysis
static-code-analysis: vendor phpstan ## Runs a static code analysis with phpstan (ensures composer)

## LIST: Triggered targets that operate on specific file changes

vendor: composer.json composer.lock
	@echo "$(ENV_INFO)"
	$(PHP_COMPOSER_BIN) validate --no-check-publish
	$(PHP_COMPOSER_BIN) install --no-interaction --no-progress --ignore-platform-reqs

## LIST: Benchmark targets for performance testing

.PHONY: benchmark-cold
benchmark-cold: ## Run cold render benchmark (no cache)
	@echo "$(ENV_INFO)"
	./benchmark/run-benchmark.sh cold 3

.PHONY: benchmark-warm
benchmark-warm: ## Run warm render benchmark (with cache, no changes)
	@echo "$(ENV_INFO)"
	./benchmark/run-benchmark.sh warm 3

.PHONY: benchmark-partial
benchmark-partial: ## Run partial change benchmark (one file modified)
	@echo "$(ENV_INFO)"
	./benchmark/run-benchmark.sh partial 3

.PHONY: benchmark-all
benchmark-all: benchmark-cold benchmark-warm benchmark-partial ## Run all benchmark scenarios

.PHONY: benchmark-compare
benchmark-compare: ## Compare benchmarks between main and current branch
	@echo "$(ENV_INFO)"
	./benchmark/compare-branches.sh main

## LIST: Docker-based benchmark targets (recommended for reproducibility)

.PHONY: benchmark-download-docs
benchmark-download-docs: ## Download TYPO3 CoreApi documentation for large benchmarks
	./benchmark/download-test-docs.sh TYPO3CMS-Reference-CoreApi

.PHONY: benchmark-docker-cold
benchmark-docker-cold: docker-build ## Run cold benchmark in Docker (small docs)
	./benchmark/benchmark-docker.sh cold 3 small

.PHONY: benchmark-docker-warm
benchmark-docker-warm: docker-build ## Run warm benchmark in Docker (small docs)
	./benchmark/benchmark-docker.sh warm 3 small

.PHONY: benchmark-docker-partial
benchmark-docker-partial: docker-build ## Run partial benchmark in Docker (small docs)
	./benchmark/benchmark-docker.sh partial 3 small

.PHONY: benchmark-docker-all
benchmark-docker-all: docker-build ## Run all benchmarks in Docker (small docs)
	./benchmark/benchmark-docker.sh all 3 small

.PHONY: benchmark-docker-large
benchmark-docker-large: docker-build benchmark-download-docs ## Run all benchmarks with large TYPO3 docs
	./benchmark/benchmark-docker.sh all 3 large

.PHONY: benchmark-report
benchmark-report: ## Generate markdown comparison report
	./benchmark/generate-report.sh
