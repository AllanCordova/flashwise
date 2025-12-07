## Vídeo
### Explicativo
[Link do vídeo será adicionado aqui]

## Informações da entrega
### Sobre
* Carregamento dinâmico das conquistas do usuário via AJAX
* Página de conquistas que carrega os dados sem recarregar a página
* Criada rota e controller que retorna dados em formato JSON
* Método `renderJson` no framework para retornar respostas JSON
* Arquivo JavaScript `achievement.js` com classe `AchievementService` para requisições assíncronas
* Arquivo de view JSON `achievements/index.json.php` para formatar a resposta
* Testes de Browser para validar a funcionalidade AJAX

### PRs da Entrega
[PR Ajax - Conquistas](https://github.com/[SEU-USUARIO]/flashwise/pull/X)

### Dados dos usuários
Para popular o banco de dados execute os seguintes comandos:
```
./run db:reset
```
```
./run db:populate
```

### Dados de Login Admin
Email:
```
admin@flashwise.com
```
Senha:
```
admin123
```

### Dados de Login Usuário
Email:
```
user1@flashwise.com
```
Senha:
```
password123
```

### Testes
PHPUnit
```
./run test
```

Browser
```
./run test:browser
```

PHPCS
```
./run composer cs-fix-dry-run
```

PHPStan
```
./run composer phpstan
```

### Mais informações
Para mais informações consulte o arquivo `run` do repositório, nele constam todos os comandos simplificados do projeto.
