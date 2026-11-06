<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Activity, Award, Brain, Users } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    exams: Array,
    summary: Object,
    exam_performance: Array,
    question_performance: Array,
    recent_attempts: Array,
});
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');

function filter(name, value) {
    const params = { days: props.filters.days, exam_id: props.filters.exam_id };
    params[name] = value || null;
    router.get('/admin/exam-analytics', params, { preserveScroll: true, preserveState: true, replace: true });
}
function number(value) { return new Intl.NumberFormat('en-IN').format(value || 0); }
function percent(value) { return value === null || value === undefined ? '—' : Number(value).toFixed(1) + '%'; }
function dateTime(value) {
    return value ? new Date(value).toLocaleString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
}
</script>

<template>
    <PortalLayout
        portal="admin"
        :title="hi ? 'परीक्षा एनालिटिक्स' : 'Exam Analytics'"
        :subtitle="hi ? 'परीक्षा परिणाम, प्रश्नों की सटीकता और विद्यार्थी प्रदर्शन का विश्लेषण।' : 'Analyze exam outcomes, question accuracy and student performance.'"
    >
        <div class="space-y-5">
            <section class="flex flex-wrap items-end justify-between gap-3 rounded-2xl border bg-white p-4">
                <div>
                    <h2 class="font-extrabold">{{ hi ? 'रिपोर्ट फ़िल्टर' : 'Report filters' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'केवल सबमिट किए गए प्रयास शामिल हैं।' : 'Only submitted attempts are included in these reports.' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select :value="filters.days" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="filter('days', Number($event.target.value))">
                        <option :value="7">{{ hi ? '7 दिन' : 'Last 7 days' }}</option>
                        <option :value="30">{{ hi ? '30 दिन' : 'Last 30 days' }}</option>
                        <option :value="90">{{ hi ? '90 दिन' : 'Last 90 days' }}</option>
                    </select>
                    <select :value="filters.exam_id || ''" class="min-w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="filter('exam_id', $event.target.value)">
                        <option value="">{{ hi ? 'सभी परीक्षाएँ' : 'All exams' }}</option>
                        <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.title }}</option>
                    </select>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'सबमिट प्रयास' : 'Submitted attempts' }}</span><Activity :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold">{{ number(summary.attempts) }}</div>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'विद्यार्थी' : 'Unique students' }}</span><Users :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold">{{ number(summary.students) }}</div>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'औसत स्कोर' : 'Average score' }}</span><Award :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold">{{ percent(summary.average_percentage) }}</div>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'कुल सही उत्तर' : 'Correct responses' }}</span><Brain :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold">{{ number(summary.correct) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ number(summary.incorrect) }} {{ hi ? 'गलत' : 'incorrect' }} · {{ number(summary.unanswered) }} {{ hi ? 'अनुत्तरित' : 'unanswered' }}</p>
                </article>
            </section>

            <section class="rounded-2xl border bg-white p-4 sm:p-5">
                <div class="mb-4"><h2 class="font-extrabold">{{ hi ? 'परीक्षा-वार प्रदर्शन' : 'Exam-wise performance' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'प्रयास, अलग-अलग विद्यार्थी और औसत प्रतिशत।' : 'Attempts, unique students and average percentage by exam.' }}</p></div>
                <div v-if="exam_performance.length" class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3 pr-3">{{ hi ? 'परीक्षा' : 'Exam' }}</th><th class="py-3 pr-3">{{ hi ? 'प्रयास' : 'Attempts' }}</th><th class="py-3 pr-3">{{ hi ? 'विद्यार्थी' : 'Students' }}</th><th class="py-3">{{ hi ? 'औसत स्कोर' : 'Average score' }}</th></tr></thead>
                        <tbody><tr v-for="exam in exam_performance" :key="exam.id" class="border-b last:border-0"><td class="py-3 pr-3 font-semibold">{{ exam.title }}</td><td class="py-3 pr-3">{{ number(exam.attempts) }}</td><td class="py-3 pr-3">{{ number(exam.students) }}</td><td class="py-3 font-bold">{{ percent(exam.average_percentage) }}</td></tr></tbody>
                    </table>
                </div>
                <p v-else class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'चुनी गई अवधि में कोई परिणाम नहीं मिला।' : 'No submitted exam results found for this period.' }}</p>
            </section>

            <section class="rounded-2xl border bg-white p-4 sm:p-5">
                <div class="mb-4"><h2 class="font-extrabold">{{ hi ? 'प्रश्न प्रदर्शन और कठिन प्रश्न' : 'Question performance & difficult questions' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'सबसे कम सटीकता वाले प्रश्न पहले; केवल उत्तर दिए गए प्रयासों से सटीकता।' : 'Lowest accuracy first; accuracy is based on answered responses only.' }}</p></div>
                <div v-if="question_performance.length" class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3 pr-3">{{ hi ? 'प्रश्न' : 'Question' }}</th><th class="py-3 pr-3">{{ hi ? 'विषय' : 'Subject' }}</th><th class="py-3 pr-3">{{ hi ? 'कठिनाई' : 'Difficulty' }}</th><th class="py-3 pr-3">{{ hi ? 'उत्तर' : 'Answered' }}</th><th class="py-3 pr-3">{{ hi ? 'सही' : 'Correct' }}</th><th class="py-3 pr-3">{{ hi ? 'गलत' : 'Incorrect' }}</th><th class="py-3">{{ hi ? 'सटीकता' : 'Accuracy' }}</th></tr></thead>
                        <tbody><tr v-for="question in question_performance" :key="question.id" class="border-b last:border-0"><td class="max-w-sm py-3 pr-3"><div class="line-clamp-2 font-medium">{{ question.prompt }}</div><div class="mt-1 text-xs text-slate-400">#{{ question.id }} · {{ question.type }}</div></td><td class="py-3 pr-3">{{ question.subject }}</td><td class="py-3 pr-3 capitalize">{{ question.difficulty }}</td><td class="py-3 pr-3">{{ number(question.answered) }} <span class="text-xs text-slate-400">/ {{ number(question.responses) }}</span></td><td class="py-3 pr-3 text-emerald-700">{{ number(question.correct) }}</td><td class="py-3 pr-3 text-rose-700">{{ number(question.incorrect) }}</td><td class="py-3 font-bold">{{ percent(question.accuracy) }}</td></tr></tbody>
                    </table>
                </div>
                <p v-else class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'प्रश्न-वार डेटा उपलब्ध नहीं है।' : 'No question-level response data is available for this period.' }}</p>
            </section>

            <section class="rounded-2xl border bg-white p-4 sm:p-5">
                <div class="mb-4"><h2 class="font-extrabold">{{ hi ? 'हाल के विद्यार्थी परिणाम' : 'Recent student results' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'नवीनतम 20 सबमिट किए गए प्रयास।' : 'Latest 20 submitted attempts.' }}</p></div>
                <div v-if="recent_attempts.length" class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3 pr-3">{{ hi ? 'विद्यार्थी' : 'Student' }}</th><th class="py-3 pr-3">{{ hi ? 'परीक्षा' : 'Exam' }}</th><th class="py-3 pr-3">{{ hi ? 'स्कोर' : 'Score' }}</th><th class="py-3 pr-3">{{ hi ? 'सही / गलत / खाली' : 'Correct / wrong / blank' }}</th><th class="py-3">{{ hi ? 'सबमिट समय' : 'Submitted' }}</th></tr></thead>
                        <tbody><tr v-for="attempt in recent_attempts" :key="attempt.id" class="border-b last:border-0"><td class="py-3 pr-3"><div class="font-semibold">{{ attempt.student }}</div><div class="text-xs text-slate-400">{{ attempt.email }}</div></td><td class="py-3 pr-3">{{ attempt.exam }}</td><td class="py-3 pr-3 font-bold">{{ attempt.score }} / {{ attempt.total_marks }} <span class="text-xs text-slate-500">({{ percent(attempt.percentage) }})</span></td><td class="py-3 pr-3">{{ attempt.correct }} / {{ attempt.incorrect }} / {{ attempt.unanswered }}</td><td class="py-3 text-xs text-slate-500">{{ dateTime(attempt.submitted_at) }}</td></tr></tbody>
                    </table>
                </div>
                <p v-else class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'कोई परिणाम उपलब्ध नहीं है।' : 'No recent results are available.' }}</p>
            </section>
        </div>
    </PortalLayout>
</template>
