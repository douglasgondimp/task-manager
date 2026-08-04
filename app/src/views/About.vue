<script setup lang="ts">
defineOptions({ name: 'AboutPage' })
</script>

<template>
    <div>
        <h1 class="text-3xl text-white mb-4 font-bold">Sobre o projeto</h1>
        <p class="text-gray-300 pb-2">Uma aplicação full stack desenvolvida para simplificar o acompanhamento de
            projetos e a
            organização de tarefas por meio de uma interface intuitiva e responsiva.</p>
        <p class="text-gray-300 pb-2">O sistema permite criar e editar projetos, cadastrar suas respectivas tarefas e
            acompanhar o andamento de cada uma através de um quadro Kanban. As tarefas podem ser movimentadas entre as
            etapas A fazer, Em andamento e Concluída utilizando o recurso de arrastar e soltar.</p>
        <p class="text-gray-300">Para manter um bom desempenho mesmo em projetos com muitas tarefas, cada coluna do
            Kanban possui carregamento progressivo com paginação por cursor. Também é possível pesquisar e filtrar
            tarefas por prioridade, prazo, período de criação e situação.</p>

        <h3 class="text-xl text-white mt-5 mb-3 font-bold">Principais funcionalidades</h3>
        <ul class="list-disc pl-5">
            <li class="text-gray-300">Criação e edição de projetos;</li>
            <li class="text-gray-300">Cadastro e edição de tarefas;</li>
            <li class="text-gray-300">Organização das tarefas em um quadro Kanban;</li>
            <li class="text-gray-300">Atualização de status por arrastar e soltar;</li>
            <li class="text-gray-300">Pesquisa e filtros combináveis;</li>
            <li class="text-gray-300">Carregamento progressivo de tarefas por coluna;</li>
            <li class="text-gray-300">Identificação visual de prioridades e tarefas atrasadas;</li>
            <li class="text-gray-300">Interface adaptada para diferentes tamanhos de tela.</li>
        </ul>

        <h3 class="text-xl text-white mt-5 mb-3 font-bold">Tecnologias utilizadas</h3>
        <p class="text-gray-300 pb-2">O backend foi desenvolvido com Laravel, responsável pela API, regras de negócio,
            validações e persistência dos dados. A interface utiliza <b>Vue.js 3 com TypeScript</b>, gerenciamento de
            estado com <b>Pinia</b> e estilização com <b>Tailwind CSS</b>.</p>
        <p class="text-gray-300">A aplicação segue uma arquitetura organizada em camadas, separando componentes visuais,
            gerenciamento de estado, comunicação com a API e regras de negócio. Essa divisão facilita a manutenção, os
            testes e a evolução do projeto.</p>
    </div>
</template>
