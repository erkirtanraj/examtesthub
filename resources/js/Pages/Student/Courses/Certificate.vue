<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ certificate: Object, student: Object, course: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
function printCertificate() { window.print(); }
</script>

<template>
<PortalLayout portal="student" title="Course Certificate" subtitle="Your verified course completion certificate.">
<div class="mx-auto max-w-4xl space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
    <a href="/student/my-courses" class="text-sm font-bold text-indigo-700">← My courses</a>
    <button type="button" @click="printCertificate" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Print / Save as PDF</button>
  </div>
  <article class="border-8 border-double border-amber-500 bg-white p-8 text-center shadow-sm sm:p-14">
    <p class="text-sm font-bold uppercase tracking-[0.3em] text-amber-700">ExamTestHub</p>
    <h1 class="mt-8 text-3xl font-serif font-bold text-slate-900 sm:text-5xl">Certificate of Completion</h1>
    <p class="mt-8 text-slate-600">This certificate is proudly presented to</p>
    <p class="mt-3 text-3xl font-semibold text-indigo-900">{{ student.name }}</p>
    <p class="mt-8 text-slate-600">for successfully completing the course</p>
    <p class="mt-3 text-2xl font-bold text-slate-900">{{ hi ? (course.title_hi || course.title) : course.title }}</p>
    <div class="mt-12 flex flex-wrap justify-between gap-6 border-t pt-6 text-left text-sm">
      <div><p class="text-slate-500">Issue date</p><p class="font-semibold">{{ certificate.issued_at }}</p></div>
      <div><p class="text-slate-500">Certificate ID</p><p class="font-semibold">{{ certificate.number }}</p></div>
    </div>
    <p class="mt-8 text-xs text-slate-500">Verify this certificate at /certificates/verify/{{ certificate.number }}</p>
  </article>
</div>
</PortalLayout>
</template>

<style>
@media print {
  @page { size: landscape; margin: 12mm; }
  body { background: white !important; }
  body * { visibility: hidden; }
  article, article * { visibility: visible; }
  article { position: absolute; inset: 0; width: 100%; box-shadow: none !important; }
}
</style>
