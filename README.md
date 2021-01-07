# symfony-todo-rest

A simple RESTful API for ToDo items based on Symfony 5.

Features:
* Request validation
* Serialization of entities to json and deserialization from json to entities using symfony/serializer
* Generated Swagger Documentation based on Code and Annotations (nelmio/api-doc-bundle)
* Centralized Exception Handler always returns JSON responses when an exception occurs
* Usage of Doctrine Param Converter

Infrastructure:
* PHP 7.4
* PostgreSQL 13
* Symfony 5.2
* Doctrine ORM
* Nginx

Dev Tools:
* phpstan
* php-cs-fixer
* Xdebug 3
* Composer 2
* Behat

## Setup

Browse to directory `dev`

`make setup`

## Run

1. Browse to directory `dev`
1. `make start` to start docker containers
1. Request endpoints (see Postman Collection (dev/Symfony ToDo.postman_collection.json))

See API Documentation: http://symfony-todo-rest.dev.local:8100/api/doc

## ToDo

- GET all: pagination
- GET all: sorting
- GET all: filter
- Authentication (JWT)
- Build Pipeline
- Upgrade to PHP 8