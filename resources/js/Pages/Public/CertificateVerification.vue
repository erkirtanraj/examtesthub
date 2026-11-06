<script setup>
defineProps({ valid: Boolean, certificate: Object });
</script>
<template>
<main class="mx-auto max-w-2xl p-8">
  <section class="rounded-2xl border bg-white p-8 text-center shadow-sm">
    <p class="text-sm font-bold uppercase tracking-widest text-indigo-700">ExamTestHub</p>
    <h1 class="mt-4 text-2xl font-extrabold">Certificate verification</h1>
    <p v-if="valid" class="mt-4 font-semibold text-emerald-700">Valid certificate</p>
    <p v-else class="mt-4 font-semibold text-red-700">Certificate not found or invalid</p>
    <div v-if="valid" class="mt-6 space-y-2 text-sm text-slate-700">
      <p><strong>Student:</strong> {{ certificate.student }}</p>
      <p><strong>Course:</strong> {{ certificate.course }}</p>
      <p><strong>Certificate ID:</strong> {{ certificate.number }}</p>
      <p><strong>Issued:</strong> {{ certificate.issued_at }}</p>
    </div>
  </section>
</main>
</template>
