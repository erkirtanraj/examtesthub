<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Download, Search, Users, UserCheck, UserX, TrendingDown, Eye } from 'lucide-vue-next';

const props = defineProps({ students: Object, filters: Object, summary: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const activity = ref(props.filters?.activity || '');
const perPage = ref(String(props.filters?.per_page || 25));
function apply() { router.get('/admin/students', { search: search.value, status: status.value, activity: activity.value, per_page: Number(perPage.value) }, { preserveState: true, preserveScroll: true, replace: true }); }
function clear() { search.value = ''; status.value = ''; activity.value = ''; perPage.value = '25'; apply(); }
function date(value) { return value ? new Date(value).toLocaleDateString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : (hi.value ? 'कोई परीक्षा नहीं' : 'No exam yet'); }
function percent(value) { return value === null || value === undefined ? '—' : Number(value).toFixed(1) + '%'; }
</script>
<template>
<PortalLayout portal="admin" :title="hi ? 'विद्यार्थी प्रबंधन' : 'Student Management'" :subtitle="hi ? 'विद्यार्थी गतिविधि, परीक्षा प्रदर्शन और नामांकन का प्रबंधन करें।' : 'Review student activity, exam performance and learning enrollments.'">
<div class="space-y-5">
<section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
<article class="rounded-2xl border bg-white p-4"><div class="flex items-center gap-2 text-sm text-slate-500"><Users :size="17"/> {{hi?'कुल विद्यार्थी':'Total students'}}</div><p class="mt-2 text-2xl font-black">{{summary.total}}</p></article>
<article class="rounded-2xl border bg-white p-4"><div class="flex items-center gap-2 text-sm text-slate-500"><UserCheck :size="17"/> {{hi?'सक्रिय खाते':'Active accounts'}}</div><p class="mt-2 text-2xl font-black text-emerald-700">{{summary.active}}</p></article>
<article class="rounded-2xl border bg-white p-4"><div class="flex items-center gap-2 text-sm text-slate-500"><UserX :size="17"/> {{hi?'निष्क्रिय खाते':'Inactive accounts'}}</div><p class="mt-2 text-2xl font-black">{{summary.inactive}}</p></article>
<article class="rounded-2xl border bg-white p-4"><div class="flex items-center gap-2 text-sm text-slate-500"><TrendingDown :size="17"/> {{hi?'कम प्रदर्शन (<40%)':'Low performance (<40%)'}}</div><p class="mt-2 text-2xl font-black text-rose-700">{{summary.low_performing}}</p></article>
</section>
<section class="rounded-2xl border bg-white p-4">
<div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_160px_190px_110px_auto_auto]">
<label class="relative"><Search :size="16" class="absolute left-3 top-3 text-slate-400"/><input v-model="search" @keyup.enter="apply" :placeholder="hi?'नाम या ईमेल खोजें':'Search name or email'" class="w-full rounded-xl border py-2.5 pl-9 pr-3 text-sm"/></label>
<select v-model="status" class="rounded-xl border bg-white px-3 py-2.5 text-sm"><option value="">{{hi?'सभी खाते':'All accounts'}}</option><option value="active">{{hi?'सक्रिय':'Active'}}</option><option value="inactive">{{hi?'निष्क्रिय':'Inactive'}}</option></select>
<select v-model="activity" class="rounded-xl border bg-white px-3 py-2.5 text-sm"><option value="">{{hi?'सभी गतिविधि':'All activity'}}</option><option value="active">{{hi?'हाल में सक्रिय':'Active in 30 days'}}</option><option value="inactive">{{hi?'30 दिन से निष्क्रिय':'No exam in 30 days'}}</option><option value="low_performing">{{hi?'औसत स्कोर 40% से कम':'Average below 40%'}}</option></select>
<select v-model="perPage" class="rounded-xl border bg-white px-3 py-2.5 text-sm"><option value="10">10 / page</option><option value="25">25 / page</option><option value="50">50 / page</option><option value="100">100 / page</option></select>
<button @click="apply" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">{{hi?'फ़िल्टर':'Apply'}}</button>
<button @click="clear" class="rounded-xl border px-4 py-2.5 text-sm font-bold">{{hi?'रीसेट':'Reset'}}</button>
</div>
<div class="mt-3 flex flex-wrap items-center justify-between gap-2"><p class="text-xs text-slate-500">{{students.total}} {{hi?'विद्यार्थी':'students found'}}</p><a v-if="page.props.auth?.user?.permissions?.includes('users.export')" :href="'/admin/students/export?'+new URLSearchParams({search:search,status:status,activity:activity,per_page:perPage}).toString()" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold text-slate-700"><Download :size="15"/> {{hi?'CSV डाउनलोड':'Export CSV'}}</a></div>
</section>
<section class="overflow-hidden rounded-2xl border bg-white">
<div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">{{hi?'विद्यार्थी':'Student'}}</th><th class="p-4">{{hi?'खाता':'Account'}}</th><th class="p-4">{{hi?'प्रयास':'Attempts'}}</th><th class="p-4">{{hi?'औसत स्कोर':'Average'}}</th><th class="p-4">{{hi?'अंतिम परीक्षा':'Last exam'}}</th><th class="p-4 text-right">{{hi?'विवरण':'Details'}}</th></tr></thead>
<tbody><tr v-for="student in students.data" :key="student.id" class="border-t"><td class="p-4"><p class="font-bold">{{student.name}}</p><p class="text-xs text-slate-500">{{student.email}}</p></td><td class="p-4"><span :class="student.is_active?'text-emerald-700':'text-rose-700'" class="font-semibold">{{student.is_active?(hi?'सक्रिय':'Active'):(hi?'निष्क्रिय':'Inactive')}}</span></td><td class="p-4 tabular-nums">{{student.submitted_attempts}}</td><td class="p-4 font-semibold">{{percent(student.average_percentage)}}</td><td class="p-4">{{date(student.last_exam_at)}}</td><td class="p-4 text-right"><a :href="'/admin/students/'+student.id" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-bold text-indigo-700"><Eye :size="14"/> {{hi?'रिपोर्ट देखें':'View report'}}</a></td></tr>
<tr v-if="!students.data.length"><td colspan="6" class="p-10 text-center text-slate-500">{{hi?'कोई विद्यार्थी नहीं मिला।':'No students matched these filters.'}}</td></tr></tbody></table></div>
<div class="flex flex-wrap items-center justify-between gap-2 border-t p-3 text-sm"><span class="text-xs text-slate-500">{{hi?'पृष्ठ':'Page'}} {{students.current_page}} / {{students.last_page}} · {{students.total}}</span><div class="flex gap-1"><a v-for="link in students.links" :key="link.label" :href="link.url||'#'" v-html="link.label" :class="['rounded-lg border px-3 py-2 text-xs font-semibold',link.active?'border-indigo-600 bg-indigo-600 text-white':'border-slate-200',!link.url?'pointer-events-none opacity-40':'']"/></div></div>
</section>
</div>
</PortalLayout>
</template>
