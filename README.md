## Flash Wise

Flash Wise is an intelligent web-based learning system designed to help you conquer the forgetting curve. Using the power of spaced repetition, our platform schedules flashcard reviews at the perfect moment, right before you're about to forget. Stop cramming and start building lasting knowledge efficiently.

### Dependências

- Docker
- Docker Compose

### To run

#### Clone Repository

```
git clone https://github.com/AllanCordova/flashwise
cd flashwise
```

#### Define the env variables

```
cp .env.example .env
```

#### Install the dependencies

```
./run composer install
```

#### Up the containers

```
docker compose up -d
```

ou

```
./run up -d
```

> **Nota**: O comando `./run up` configura automaticamente as permissões dos diretórios de upload. As permissões são aplicadas apenas aos diretórios, não aos arquivos já existentes.

#### Create database and tables

```
./run db:reset
```

#### Populate database

```
./run db:populate
```

#### Setup upload permissions

```
./run setup:permissions
```

#### Run the tests

```
docker compose run --rm php ./vendor/bin/phpunit tests --color
```

ou

```
./run test
```

#### Run the linters

[PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer/)

```
./run phpcs
```

[PHPStan](https://phpstan.org/)

```
./run phpstan
```

Access [localhost](http://localhost)

### Teste de API

#### Rota não autenticada

```shell
curl -H "Accept: application/json" localhost/problems
```

#### Rota autenticada

Neste caso precisa alterar o valor do PHPSESSID de acordo com a o id da sua sessão.

```shell
curl -H "Accept: application/json" -b "PHPSESSID=5f55f364a48d87fb7ef9f18425a8ae88" localhost/decks
```
