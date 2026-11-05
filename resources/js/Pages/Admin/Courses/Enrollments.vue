<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
defineProps({course:Object,enrollments:Object});
const page=usePage();const hi=computed(()=>page.props.locale==='hi');
</script>
<template>
<PortalLayout portal="admin" :title="hi?'कोर्स नामांकन':'Course enrollments'" :subtitle="course.title">
<div class="overflow-x-auto rounded-2xl border bg-white"><table class="w-full min-w-[680px] text-left text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Student</th><th class="p-3">Email</th><th class="p-3">Status</th><th class="p-3">Amount paid</th><th class="p-3">Lessons completed</th><th class="p-3">Enrolled</th></tr></thead>
<tbody><tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="border-t"><td class="p-3 font-semibold">{{enrollment.user?.name}}</td><td class="p-3">{{enrollment.user?.email}}</td><td class="p-3 capitalize">{{enrollment.status}}</td><td class="p-3">{{enrollment.currency}} {{enrollment.amount_paid||'0.00'}}</td><td class="p-3">{{enrollment.progress_count}}</td><td class="p-3">{{enrollment.enrolled_at?new Date(enrollment.enrolled_at).toLocaleDateString():'—'}}</td></tr>
<tr v-if="!enrollments.data.length"><td colspan="6" class="p-8 text-center text-slate-500">No enrollments yet.</td></tr></tbody></table></div>
</PortalLayout>
</template>
