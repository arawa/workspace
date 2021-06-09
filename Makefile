# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# Uses the following commands:
# * make, make all, make build: to build your app for a production environment
# * make dev: to build your app for a development environment
# * make dist: to build your app for the offical Nextcloud appstore (doesn't work atm)
# * make clean: to clean your project folder from the appstore build artifacts
# * make fullclean: same as 'make clean' but also removes all project dependencies
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.

app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
source_build_directory=$(CURDIR)/build/artifacts/source
source_package_name=$(source_build_directory)/$(app_name)
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)
flavor=prod

.PHONY: all
all: build

.PHONY: dev
dev: flavor=dev
dev: build

# Fetches the PHP and JS dependencies and compiles the JS.
.PHONY: build
build:
ifneq (,$(wildcard $(CURDIR)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(CURDIR)/package.json))
	npm install
	make npm-$(flavor)
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
else
	composer install --prefer-dist
endif

# Builds the js part of the app for production use
.PHONY: npm
npm-prod:
	npm run build

# Builds the js part of the app for development use
.PHONY: npm
npm-dev:
	npm run dev

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
fullclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules

.PHONY: test
test: composer
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.xml
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml

######################################################
#
# Everything from here relates to building a valid app
# for Nextcloud's official appstore
#
######################################################

# Builds the source and appstore package
.PHONY: dist
dist:
	make source
	make appstore

# Builds the source package
.PHONY: source
source:
	rm -rf $(source_build_directory)
	mkdir -p $(source_build_directory)
	tar cvzf ${source_package_name} \
	--exclude-vcs \
	--exclude="../$(app_name)/.git" \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/node_modules" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/js/*.log" \
	../$(app_name)

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	tar cvzf "$(appstore_package_name).tar.gz" \
	--exclude-vcs \
	--exclude="../$(app_name)/.git" \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/tests" \
	--exclude="../$(app_name)/Makefile" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/phpunit*xml" \
	--exclude="../$(app_name)/composer.*" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/js/tests" \
	--exclude="../$(app_name)/js/test" \
	--exclude="../$(app_name)/js/*.log" \
	--exclude="../$(app_name)/js/package.json" \
	--exclude="../$(app_name)/js/bower.json" \
	--exclude="../$(app_name)/js/karma.*" \
	--exclude="../$(app_name)/js/protractor.*" \
	--exclude="../$(app_name)/package.json" \
	--exclude="../$(app_name)/bower.json" \
	--exclude="../$(app_name)/karma.*" \
	--exclude="../$(app_name)/protractor\.*" \
	--exclude="../$(app_name)/.*" \
	--exclude="../$(app_name)/js/.*" \
	../$(app_name)
	zip -r "$(appstore_package_name).zip" ../$(app_name) \
	--exclude "../$(app_name)/.git/*" "../$(app_name)/build/*" "../$(app_name)/tests/*" \
	"../$(app_name)/Makefile" "../$(app_name)/*.log" "../$(app_name)/phpunit*xml" \
	"../$(app_name)/composer.*" "../$(app_name)/js/node_modules/*" "../$(app_name)/js/tests/*" \
	"../$(app_name)/js/test/*" "../$(app_name)/js/*.log" "../$(app_name)/js/package.json" \
	"../$(app_name)/js/bower.json" "../$(app_name)/js/karma.*" "../$(app_name)/js/protractor.*" \
	"../$(app_name)/package.json" "../$(app_name)/bower.json" "../$(app_name)/karma.*" \
	"../$(app_name)/protractor.*" "../$(app_name)/.*" "../$(app_name)/js/.*"
