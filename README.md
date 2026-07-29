# Task Manager

Aplicação para gerenciamento de projetos e tarefas, desenvolvida como teste técnico para uma vaga Full Stack.

O sistema permite criar e gerenciar projetos, organizar tarefas em um quadro Kanban, aplicar filtros e acompanhar prazos e prioridades.

## Tecnologias

### Backend

- PHP 8.4+
- Laravel 12
- sqlite
- Laravel Sanctum
- PHPUnit

### Frontend

- Vue 3
- TypeScript
- Pinia
- Vue Router
- Tailwind CSS
- Vite

## Funcionalidades

### Projetos

- Listagem de projetos
- Criação de projetos
- Atualização de projetos
- Filtros por nome, status e período

### Tarefas

- Listagem de tarefas por projeto
- Criação de tarefas
- Visualização de uma tarefa
- Atualização de tarefas
- Exclusão lógica com Soft Delete
- Filtros por texto, status, prioridade, período e atraso
- Paginação por cursor para scroll infinito
- Status e prioridades representados por enums

### Frontend

- Quadro Kanban por projeto
- Movimentação de tarefas entre colunas
- Scroll infinito
- Filtros de tarefas
- Feedback visual de carregamento e erros

## Arquitetura

O backend foi estruturado utilizando os recursos nativos do Laravel e separação de responsabilidades:

- **Controllers:** recebem as requisições e retornam as respostas HTTP.
- **Form Requests:** cuidam da validação e autorização.
- **Services:** concentram consultas e regras que não pertencem diretamente ao controller.
- **Resources:** padronizam a representação dos dados da API.
- **Models:** representam as entidades e seus relacionamentos.
- **Enums:** restringem os possíveis valores de status e prioridade.
- **Feature Tests:** validam o comportamento dos endpoints.

O `ProjectTaskController` representa os endpoints relacionados às tarefas pertencentes a um projeto:

```text
GET  /api/projects/{project}/tasks
POST /api/projects/{project}/tasks