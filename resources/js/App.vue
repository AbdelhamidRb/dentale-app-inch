<template>
  <!-- RouterView affiche la vue correspondant à l'URL courante -->
  <!-- Ex: /login → LoginView, /dashboard → DashboardView -->
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
</script>