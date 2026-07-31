# Task Manager

Aplicação full stack para gerenciamento de projetos e tarefas, desenvolvida como teste técnico. O sistema permite cadastrar projetos, organizar tarefas em um quadro Kanban e acompanhar o trabalho por status, prioridade e prazo.

## Funcionalidades

### Projetos

- Listagem com paginação por cursor e scroll infinito
- Busca por nome ou descrição
- Filtro por status
- Criação e edição
- Contagem de tarefas por projeto

### Tarefas

- Criação, visualização, edição e exclusão lógica
- Quadro Kanban com as colunas:
  - A fazer
  - Em desenvolvimento
  - Completo
- Atualização de status por drag-and-drop
- Paginação por cursor independente em cada coluna
- Scroll infinito por coluna
- Busca por título ou descrição
- Filtros por status, prioridade, período de criação e tarefas atrasadas
- Identificação visual de prioridade e atraso
- Tratamento de carregamento e erros



## Tecnologias



### Backend

- PHP 8.2+
- Laravel 12
- Eloquent ORM
- MySQL
- PHPUnit



### Frontend

- Vue 3
- TypeScript
- Pinia
- Vue Router
- Axios
- Tailwind CSS 4
- Vue Draggable Plus
- Vite
- Vitest



## Arquitetura

O backend utiliza os recursos nativos do Laravel com separação de responsabilidades:

- **Controllers:** recebem as requisições e produzem as respostas HTTP.
- **Form Requests:** concentram autorização e validação.
- **Services:** encapsulam consultas, filtros e paginação.
- **API Resources:** padronizam os dados retornados pela API.
- **Models:** representam as entidades, relacionamentos, casts e scopes.
- **Enums:** restringem os status e as prioridades aceitos.
- **Feature Tests:** verificam os endpoints e suas regras.

No frontend, as responsabilidades estão divididas em:

- **Views e componentes:** renderização e interação com o usuário.
- **Composables:** fluxo das operações, carregamento e tratamento de erros.
- **Stores Pinia:** estado compartilhado de projetos, tarefas e paginações.
- **Services:** comunicação com a API por meio do Axios.
- **Interfaces:** contratos TypeScript usados pela aplicação.



### Paginação do Kanban

As tarefas utilizam o mesmo endpoint com um filtro para cada status:

```http
GET /api/projects/1/tasks?status=todo
GET /api/projects/1/tasks?status=in_progress
GET /api/projects/1/tasks?status=done
```

As três requisições iniciais são executadas em paralelo. Cada coluna mantém seu próprio cursor, estado de carregamento e indicação de próxima página. Dessa forma, o usuário pode percorrer uma coluna sem carregar desnecessariamente todas as tarefas das demais.

## Estrutura do projeto

```text
task-manager/
├── api/    # API REST em Laravel
└── app/    # Aplicação Vue
```



## Pré-requisitos

- PHP 8.2 ou superior
- Composer
- Extensão PDO MySQL habilitada no PHP
- Node.js 22.18+ ou 24.12+
- npm



## Instalação

Clone o repositório:

```bash
git clone https://github.com/douglasgondimp/task-manager.git
cd task-manager
```



### Backend

Acesse o diretório da API:

```bash
cd api
```

Instale as dependências e crie o arquivo de ambiente:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure o `.env` com as configurações do banco de dados mysql:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

Execute as migrations. Para incluir dados de demonstração, use a opção `--seed`:

```bash
php artisan migrate --seed
```

Inicie a API:

```bash
php artisan serve
```

A API ficará disponível em `http://localhost:8000`.

### Frontend

Em outro terminal, acesse o diretório do frontend:

```bash
cd app
```

Instale as dependências:

```bash
npm install
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

O valor padrão aponta para a API local:

```env
VITE_API_URL=http://localhost:8000/api
```

Inicie a aplicação:

```bash
npm run dev
```

A URL de acesso será exibida pelo Vite no terminal.

## Endpoints da API


| Método   | Endpoint                        | Descrição                            |
| -------- | ------------------------------- | ------------------------------------ |
| `GET`    | `/api/projects`                 | Lista e filtra projetos              |
| `POST`   | `/api/projects`                 | Cria um projeto                      |
| `GET`    | `/api/projects/{project}`       | Exibe um projeto                     |
| `PATCH`  | `/api/projects/{project}`       | Atualiza um projeto                  |
| `GET`    | `/api/projects/{project}/tasks` | Lista e filtra as tarefas do projeto |
| `POST`   | `/api/projects/{project}/tasks` | Cria uma tarefa no projeto           |
| `GET`    | `/api/tasks/{task}`             | Exibe uma tarefa                     |
| `PATCH`  | `/api/tasks/{task}`             | Atualiza uma tarefa                  |
| `DELETE` | `/api/tasks/{task}`             | Exclui logicamente uma tarefa        |


Todas as rotas da API possuem limite de 60 requisições por minuto.

### Filtros de projetos


| Parâmetro  | Descrição                             |
| ---------- | ------------------------------------- |
| `search`   | Busca por nome ou descrição           |
| `status`   | Filtra por `active` ou `archived`     |
| `per_page` | Quantidade de registros, entre 1 e 50 |
| `cursor`   | Cursor retornado pela página anterior |




### Filtros de tarefas


| Parâmetro      | Descrição                                      |
| -------------- | ---------------------------------------------- |
| `search`       | Busca por título ou descrição                  |
| `status`       | Filtra por `todo`, `in_progress` ou `done`     |
| `priority`     | Filtra por `low`, `medium` ou `high`           |
| `is_overdue`   | Retorna apenas tarefas atrasadas quando `true` |
| `created_at[]` | Intervalo com data inicial e final             |
| `cursor`       | Cursor retornado pela página anterior          |


Exemplo:

```http
GET /api/projects/1/tasks?status=todo&priority=high&is_overdue=true
```



## Testes e qualidade



### Backend

Execute os testes automatizados:

```bash
cd api
php artisan test
```

Formate o código PHP com Laravel Pint:

```bash
./vendor/bin/pint
```



### Frontend

Execute a verificação de tipos e o build:

```bash
cd app
npm run build
```

Execute os testes unitários:

```bash
npm run test:unit
```

Execute os linters:

```bash
npm run lint
```



## Decisões técnicas

- A paginação por cursor foi escolhida para favorecer o scroll infinito e manter estabilidade durante a inclusão ou alteração de registros.
- As tarefas são carregadas separadamente por status porque cada coluna do Kanban possui uma posição de paginação diferente.
- O `ProjectTaskController` representa operações sobre tarefas que pertencem a um projeto; operações sobre uma tarefa já identificada ficam no `TaskController`.
- Pinia mantém o estado compartilhado, enquanto os composables coordenam a API e os estados de interface.
- Status e prioridades são representados por enums no backend para evitar valores inválidos.
- A exclusão de tarefas é lógica, permitindo preservar os registros no banco de dados.



## Autor

Desenvolvido por [Douglas Gondim](https://github.com/douglasgondimp).