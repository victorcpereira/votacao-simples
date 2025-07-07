# Sistema de Votação Simples (Drupal 11)

Este projeto implementa um sistema de votação simples em Drupal 11, com foco
total no backend. Ele permite a criação de perguntas com múltiplas opções de
resposta, votação por usuários anônimos, exibição de resultados e acesso via API
segura.

---

## Instalação com Composer + Lando

```bash
lando start
lando composer install
lando drush site:install
lando drush en votacao -y
```

---

## Módulos necessários

- `votacao` (módulo customizado)
- `rest`
- `restui`
- `inline_entity_form` (Esse módulo foi instalado apenas para poder criar um
  entidade Resposta no mesmo formulário de criação da entidade Pergunta)

---

## Configuração do Token da API

Acesse:

```
/admin/config/votacao/settings
```

Configure o valor do **token da API**, e opcionalmente marque "Desativar sistema
de votação" para suspender temporariamente todos os endpoints e interações de
voto.

---

## Endpoints da API REST

Todos os endpoints exigem o cabeçalho:

```
X-API-TOKEN: seu_token_configurado
```

### GET /api/perguntas

Lista perguntas com paginação:

```bash
curl -X GET http://votacao-simples.lndo.site/api/perguntas \
  -H "X-API-TOKEN: seu_token_configurado"
```

### GET /api/pergunta/{id}

Detalha uma pergunta com opções:

```bash
curl -X GET http://votacao-simples.lndo.site/api/pergunta/1 \
  -H "X-API-TOKEN: seu_token_configurado"
```

### POST /api/pergunta/{id}/votar

Registra um voto para uma opção da pergunta:

```bash
curl -X POST http://votacao-simples.lndo.site/api/pergunta/1/votar \
  -H "Content-Type: application/json" \
  -H "X-API-TOKEN: seu_token_configurado" \
  -d '{
    "opcao_id": 5
  }'
```

---

## Página de votação

A página pública de votação pode ser acessada por qualquer usuário (anônimo ou
autenticado) usando o seguinte padrão de URL:

```
/votacao/[id da pergunta]
```

Nela, o usuário poderá votar em uma das opções disponíveis e, se a configuração
da pergunta permitir, visualizar os resultados após votar.

---

##  Funcionalidades principais

- Cadastro de perguntas e respostas via UI
- Voto anônimo
- Exibição condicional dos resultados (após voto)
- Página administrativa de resultados:
    - `/admin/content/votacao/resultados`
    - Com totais, porcentagens e paginação

---

##  Banco de dados

- O dump do banco de dados se encontra em [database.sql.gz](database.sql.gz)
- Para importar o banco de dados execute o seguinte comando
  - `lando db-import database.sql.gz`
---

##  Credenciais

- As credenciais de administrador, utilizando o banco de dados fornecido são:
    - user: `admin` | password: `admin`
- Também é possível gerar um link único de login executando o seguinte comando:
    - `lando drush uli`
---

## Pronto para entrega!

Este projeto está pronto para avaliação com base nos critérios fornecidos.

Dúvidas ou sugestões? Sinta-se à vontade para revisar o código ou entrar em
contato. ✌️
