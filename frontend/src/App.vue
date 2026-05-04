<template>
  <div class="min-vh-100 bg-light d-flex flex-column">
    <header class="navbar bg-white border-bottom">
      <div class="container flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
        <span class="navbar-brand mb-0 h1">A25 Yandex Tracker</span>

        <a
          class="btn btn-outline-primary btn-sm ms-sm-auto"
          href="https://forms.yandex.ru/cloud/69f66caa1f1eb55b647f8495"
          target="_blank"
          rel="noopener noreferrer"
        >
          Создать задачу
        </a>
      </div>
    </header>

    <main class="py-5 flex-grow-1">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-md-8 col-lg-5">
            <TaskFilterForm />
          </div>
        </div>
      </div>
    </main>

    <footer class="bg-white border-top py-3">
      <div class="container">
        <span class="text-muted">Сарыглар Начын</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import TaskFilterForm from './components/TaskFilterForm.vue'
</script>
