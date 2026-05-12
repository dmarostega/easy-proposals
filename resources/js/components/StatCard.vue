<script setup lang="ts">
defineProps<{ label: string; value: string | number }>();
</script>
<template><article class="rounded-xl bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">{{ label }}</p><strong class="text-2xl">{{ value }}</strong></article></template>
