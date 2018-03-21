include makefile.config

#
# Insure that docker and docker compose command is exists.
#
DOCKER := $(shell command -v docker 2> /dev/null)
DOCKER_COMPOSE := $(shell command -v docker-compose 2> /dev/null)

DOCKER_COMPOSE_FLAGS := --project-name $(PROJECT_NAME)
DOCKER_COMPOSE_DEV_FLAGS := $(DOCKER_COMPOSE_FLAGS) --file ./docker/docker-compose.development.yml

ifndef DOCKER
$(error You should install 'docker' first)
endif

ifndef DOCKER_COMPOSE
$(error You should install 'docker-compose' first)
endif

phinx_config: phinx.yml.dist
	@sed -- 's/host: ~/host: $(DB_HOST)/; s/name: ~/name: $(DB_NAME)/; s/user: ~/user: $(DB_USER)/; s/pass: ~/pass: $(DB_PASSWORD)/' phinx.yml.dist > phinx.yml

.PHONY: start db-cli app-cli node-cli node-restart

#
# Start application in docker containers. Development environment.
#
start:
	sudo $(DOCKER_COMPOSE) $(DOCKER_COMPOSE_DEV_FLAGS) up --build

#
# Open mysql cli to database in container.
#
db-cli:
	sudo $(DOCKER_COMPOSE) $(DOCKER_COMPOSE_DEV_FLAGS) exec db mysql -u $(DB_USER) -p$(DB_PASSWORD) $(DB_NAME)

#
# Open shell to application server.
#
app-cli:
	sudo $(DOCKER_COMPOSE) $(DOCKER_COMPOSE_DEV_FLAGS) exec php-fpm /bin/ash -l

#
# Open shell to node dev server.
#
node-cli:
	sudo $(DOCKER_COMPOSE) $(DOCKER_COMPOSE_DEV_FLAGS) exec node /bin/ash -l

node-restart:
	sudo $(DOCKER_COMPOSE) $(DOCKER_COMPOSE_DEV_FLAGS) restart node
