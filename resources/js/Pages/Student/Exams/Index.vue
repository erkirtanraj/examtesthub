<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { ArrowUpRight, Clock3, FileQuestion, RotateCcw, Trophy } from 'lucide-vue-next';

defineProps({ exams: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
function start(id) {
    if (confirm(hi.value ? 'परीक्षा शुरू करें? टाइमर तुरंत शुरू होगा।' : 'Start this exam? The timer starts immediately.')) router.post('/student/exams/' + id + '/start');
}
</script>

<template>
<PortalLayout portal="student" :title="hi ? 'उपलब्ध परीक्षाएँ' : 'Available exams'" :subtitle="hi ? 'परीक्षा चुनें और अपनी तैयारी जाँचें।' : 'Choose an exam and test your preparation.'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4"><div><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ hi ? 'आपकी अगली चुनौती' : 'YOUR NEXT CHALLENGE' }}</p><p class="mt-1 text-sm text-slate-600">{{ hi ? 'परीक्षा शुरू करने से पहले समय और प्रयास सीमा जाँचें।' : 'Check the time limit and remaining attempts before you begin.' }}</p></div><span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ exams.total }} {{ hi ? 'परीक्षाएँ' : 'exams available' }}</span></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="e in exams.data" :key="e.id" class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg">
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-sky-400"></div>
            <div class="flex flex-1 flex-col p-5">
                <div class="flex items-start justify-between gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><FileQuestion :size="19"/></span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ e.questions_count }} {{ hi ? 'प्रश्न' : 'questions' }}</span></div>
                <h2 class="mt-4 break-words text-lg font-extrabold leading-snug text-slate-900">{{ e.title }}</h2>
                <p class="mt-2 min-h-10 flex-1 text-sm leading-6 text-slate-500">{{ e.description || (hi ? 'अपनी तैयारी का मूल्यांकन करने के लिए यह परीक्षा दें।' : 'Take this assessment to evaluate your preparation.') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 p-3"><span class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500"><Clock3 :size="13"/>{{ hi ? 'समय' : 'Duration' }}</span><strong class="mt-1 block text-sm font-extrabold text-slate-900">{{ e.duration_minutes }} min</strong></div>
                    <div class="rounded-xl bg-slate-50 p-3"><span class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500"><RotateCcw :size="13"/>{{ hi ? 'प्रयास' : 'Attempts' }}</span><strong class="mt-1 block text-sm font-extrabold text-slate-900">{{ e.attempts_used }}/{{ e.max_attempts }}</strong></div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <span class="text-xs font-semibold" :class="e.latest_attempt?.status === 'submitted' ? 'text-emerald-700' : e.latest_attempt?.status === 'in_progress' ? 'text-amber-700' : 'text-slate-500'">{{ e.latest_attempt?.status === 'submitted' ? (hi ? 'पिछला स्कोर' : 'Last score') + ': ' + e.latest_attempt.score : e.latest_attempt?.status === 'in_progress' ? (hi ? 'परीक्षा जारी है' : 'Attempt in progress') : (hi ? 'शुरू करने के लिए तैयार' : 'Ready to start') }}</span>
                    <button @click="start(e.id)" :disabled="e.attempts_used >= e.max_attempts && e.latest_attempt?.status !== 'in_progress'" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">{{ e.latest_attempt?.status === 'in_progress' ? (hi ? 'जारी रखें' : 'Resume exam') : (hi ? 'शुरू करें' : 'Start exam') }}<ArrowUpRight :size="15"/></button>
                </div>
            </div>
        </article>
        <p v-if="!exams.data.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">{{ hi ? 'अभी कोई परीक्षा उपलब्ध नहीं है।' : 'No exams are available right now.' }}</p>
    </div>
</PortalLayout>
</template>
