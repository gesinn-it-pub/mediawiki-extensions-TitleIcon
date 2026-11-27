-include .env
export

# setup for docker-compose-ci build directory
# delete "build" directory to update docker-compose-ci

ifeq (,$(wildcard ./build/Makefile))
    $(shell git submodule update --init --remote)
endif

EXTENSION=TitleIcon

# docker images
MW_VERSION?=1.43
PHP_VERSION?=8.3
DB_TYPE?=mysql
DB_IMAGE?="mariadb:11.2"

# extensions
SMW_VERSION?=7.4.0

# composer
# Enables "composer update" inside of extension
COMPOSER_EXT?=true

# Enables node.js related tests and "npm install"
NODE_JS?=true

# coverage: PCOV is several times faster than Xdebug (line coverage only)
COVERAGE_DRIVER?=pcov

include build/Makefile
