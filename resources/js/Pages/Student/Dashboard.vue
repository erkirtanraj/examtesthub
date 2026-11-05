<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
const page=usePage(); const hi=computed(()=>page.props.locale==='hi');
</script>
<template>
<PortalLayout portal="student" :title="hi?'विद्यार्थी डैशबोर्ड':'Student dashboard'" :subtitle="hi?'अपनी उपलब्ध परीक्षाएँ दें और परिणाम देखें।':'Take available examinations and review your results.'">
<div class="rounded-2xl bg-gradient-to-r from-indigo-700 to-violet-700 p-6 text-white">
<p class="text-sm font-semibold text-indigo-100">{{hi?'आपका लर्निंग स्पेस':'YOUR LEARNING SPACE'}}</p>
<h2 class="mt-2 text-2xl font-extrabold">{{hi?'नमस्ते':'Hello'}}, {{page.props.auth?.user?.name}}!</h2>
<p class="mt-2 text-sm text-indigo-100">{{hi?'आपकी परीक्षाएँ अब उपलब्ध हैं।':'Your examination area is ready.'}}</p>
<a href="/student/exams" class="mt-4 inline-block rounded-lg bg-white px-4 py-2 text-sm font-bold text-indigo-700">{{hi?'परीक्षाएँ देखें':'Browse exams'}}</a>
</div>
</PortalLayout>
</template>