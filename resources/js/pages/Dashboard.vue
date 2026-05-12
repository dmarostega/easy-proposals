<script setup lang="ts">
import StatCard from '../components/StatCard.vue';
defineProps<{ stats: Record<string, number | string | null> }>();
</script>
<template><section class="grid gap-4 md:grid-cols-5"><StatCard v-for="(value, key) in stats" :key="key" :label="key" :value="value ?? 'Ilimitado'" /></section></template>
