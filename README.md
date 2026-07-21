# 🚗 Controle de Revisões de Veículos

Sistema web para gerenciamento de proprietários, veículos e revisões automotivas, desenvolvido como teste técnico utilizando Laravel, Vue.js, PostgreSQL e Docker.

---

## Tecnologias

### Backend
- PHP ^8.3
- Laravel 13
- Laravel Sanctum

### Frontend
- Vue.js 2
- JavaScript (ES6)
- HTML5
- CSS3

### Banco de Dados
- PostgreSQL 17

### Ferramentas
- Docker
- Docker Compose
- Laravel Scribe

---

## Funcionalidades

- Autenticação de usuários
- Dashboard com indicadores
- CRUD de Proprietários
- CRUD de Veículos
- CRUD de Revisões
- Relatórios com gráficos
- API REST documentada

---

## Estrutura

```
app/
database/
docs/sql/
resources/
routes/
```

---

## Instalação

Clone o projeto

```bash
git clone https://github.com/CesarDanilo/controle-revisoes-veiculos.git
```

Instale as dependências

```bash
composer install
npm install
```

Configure o ambiente

```bash
cp .env.example .env

php artisan key:generate
```

Suba os containers

```bash
docker compose up -d
```

Execute as migrations e os seeders

```bash
php artisan migrate --seed
```

Inicie a aplicação

```bash
php artisan serve
npm run dev
```

---

## Documentação da API

A documentação foi gerada com **Laravel Scribe** e pode ser acessada em:

```
/docs
```

---

## Relatórios

O sistema possui relatórios de:

- Pessoas
- Veículos
- Revisões

As consultas SQL utilizadas encontram-se em:

```
docs/sql/
```

---

## Arquitetura

- MVC
- REST API
- Eloquent ORM
- UUID como chave primária
- PostgreSQL
- Docker

---

## Desenvolvedor

**César Danilo**

GitHub: https://github.com/CesarDanilo