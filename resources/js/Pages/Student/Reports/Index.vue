<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Award, BookOpen, Download, Printer, Target, Trophy } from 'lucide-vue-next';

const props = defineProps({ student: Object, filters: Object, summary: Object, subject_performance: Array, exam_history: Array });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
function percent(value) { return value === null || value === undefined ? '—' : Number(value).toFixed(1) + '%'; }
function filter(days) { router.get('/student/reports', { days }, { preserveScroll: true, preserveState: true, replace: true }); }
function dateTime(value) { return value ? new Date(value).toLocaleString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'; }
function printReport() { window.print(); }
</script>

<template>
    <PortalLayout portal="student" :title="hi ? 'मेरी विस्तृत रिपोर्ट' : 'My Detailed Report'" :subtitle="hi ? 'आपकी परीक्षा प्रगति, विषय-वार प्रदर्शन और रैंक का व्यक्तिगत सारांश।' : 'Your personal exam progress, subject performance and cohort ranking summary.'">
        <div class="space-y-5">
            <section class="no-print flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4">
                <div><h2 class="font-extrabold">{{ hi ? 'रिपोर्ट अवधि' : 'Report period' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'चुनी गई अवधि में सबमिट किए गए प्रयास।' : 'Submitted attempts within the selected reporting period.' }}</p></div>
                <div class="flex flex-wrap gap-2">
                    <select :value="filters.days" class="rounded-lg border px-3 py-2 text-sm" @change="filter(Number($event.target.value))">
                        <option :value="30">{{ hi ? 'पिछले 30 दिन' : 'Last 30 days' }}</option>
                        <option :value="90">{{ hi ? 'पिछले 90 दिन' : 'Last 90 days' }}</option>
                        <option :value="365">{{ hi ? 'पिछले 365 दिन' : 'Last 365 days' }}</option>
                    </select>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700" @click="printReport"><Printer :size="16"/> {{ hi ? 'PDF / प्रिंट' : 'Print / Save PDF' }}</button>
                </div>
            </section>

            <section id="student-report" class="space-y-5 rounded-2xl border bg-white p-4 sm:p-6">
                <header class="border-b pb-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">ExamTestHub</p><h2 class="mt-1 text-2xl font-black">{{ hi ? 'विद्यार्थी प्रदर्शन रिपोर्ट' : 'Student Performance Report' }}</h2><p class="mt-2 text-sm text-slate-600">{{ student.name }} · {{ student.email }}</p></div>
                        <div class="text-right text-xs text-slate-500"><p>{{ hi ? 'रिपोर्ट बनाई गई' : 'Generated' }}</p><p class="mt-1 font-semibold">{{ dateTime(student.generated_at) }}</p><p class="mt-1">{{ filters.days }} {{ hi ? 'दिन की अवधि' : 'day period' }}</p></div>
                    </div>
                </header>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><BookOpen :size="16"/>{{ hi ? 'सबमिट प्रयास' : 'Submitted attempts' }}</div><p class="mt-2 text-2xl font-black">{{ summary.attempts }}</p></article>
                    <article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Target :size="16"/>{{ hi ? 'औसत स्कोर' : 'Average score' }}</div><p class="mt-2 text-2xl font-black">{{ percent(summary.average_percentage) }}</p></article>
                    <article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Trophy :size="16"/>{{ hi ? 'सर्वश्रेष्ठ स्कोर' : 'Best score' }}</div><p class="mt-2 text-2xl font-black">{{ percent(summary.best_percentage) }}</p></article>
                    <article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Award :size="16"/>{{ hi ? 'सही उत्तर' : 'Correct answers' }}</div><p class="mt-2 text-2xl font-black text-emerald-700">{{ summary.correct }}</p><p class="mt-1 text-xs text-slate-500">{{ summary.incorrect }} {{ hi ? 'गलत' : 'incorrect' }} · {{ summary.unanswered }} {{ hi ? 'खाली' : 'unanswered' }}</p></article>
                </section>

                <section>
                    <h3 class="font-extrabold">{{ hi ? 'विषय-वार ताकत और सुधार के क्षेत्र' : 'Subject strengths & improvement areas' }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'सटीकता केवल उत्तर दिए गए प्रश्नों पर आधारित है।' : 'Accuracy is calculated from answered questions only; unanswered questions are shown separately.' }}</p>
                    <div v-if="subject_performance.length" class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left text-sm">
                            <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3 pr-3">{{ hi ? 'विषय' : 'Subject' }}</th><th class="py-3 pr-3">{{ hi ? 'उत्तर दिए' : 'Answered' }}</th><th class="py-3 pr-3">{{ hi ? 'सही' : 'Correct' }}</th><th class="py-3 pr-3">{{ hi ? 'गलत' : 'Incorrect' }}</th><th class="py-3 pr-3">{{ hi ? 'खाली' : 'Blank' }}</th><th class="py-3">{{ hi ? 'सटीकता' : 'Accuracy' }}</th></tr></thead>
                            <tbody><tr v-for="subject in subject_performance" :key="subject.subject" class="border-b last:border-0"><td class="py-3 pr-3 font-semibold">{{ subject.subject }}</td><td class="py-3 pr-3">{{ subject.answered }}/{{ subject.responses }}</td><td class="py-3 pr-3 text-emerald-700">{{ subject.correct }}</td><td class="py-3 pr-3 text-rose-700">{{ subject.incorrect }}</td><td class="py-3 pr-3">{{ subject.unanswered }}</td><td class="py-3 font-bold">{{ percent(subject.accuracy) }}</td></tr></tbody>
                        </table>
                    </div>
                    <p v-else class="mt-3 rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">{{ hi ? 'इस अवधि में विषय-वार डेटा उपलब्ध नहीं है।' : 'No subject-level responses are available for this period.' }}</p>
                </section>

                <section>
                    <h3 class="font-extrabold">{{ hi ? 'परीक्षा इतिहास और रैंक' : 'Exam history & cohort ranking' }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'रैंक प्रत्येक परीक्षा में सभी विद्यार्थियों के सर्वश्रेष्ठ सबमिट स्कोर से तुलना करती है।' : 'Rank compares your attempt percentage with each student’s best submitted score in the same exam (all time).' }}</p>
                    <div v-if="exam_history.length" class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3 pr-3">{{ hi ? 'परीक्षा' : 'Exam' }}</th><th class="py-3 pr-3">{{ hi ? 'स्कोर' : 'Score' }}</th><th class="py-3 pr-3">{{ hi ? 'प्रतिशत' : 'Percentage' }}</th><th class="py-3 pr-3">{{ hi ? 'रैंक' : 'Rank' }}</th><th class="py-3 pr-3">{{ hi ? 'पर्सेंटाइल' : 'Percentile' }}</th><th class="py-3">{{ hi ? 'तिथि' : 'Date' }}</th></tr></thead>
                            <tbody><tr v-for="attempt in exam_history" :key="attempt.id" class="border-b last:border-0"><td class="py-3 pr-3 font-semibold">{{ attempt.exam }}</td><td class="py-3 pr-3">{{ attempt.score }} / {{ attempt.total_marks }}</td><td class="py-3 pr-3 font-bold">{{ percent(attempt.percentage) }}</td><td class="py-3 pr-3">{{ attempt.rank ? attempt.rank + ' / ' + attempt.cohort_size : '—' }}</td><td class="py-3 pr-3">{{ percent(attempt.percentile) }}</td><td class="py-3">{{ dateTime(attempt.submitted_at) }}</td></tr></tbody>
                        </table>
                    </div>
                    <p v-else class="mt-3 rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">{{ hi ? 'इस अवधि में कोई सबमिट परीक्षा नहीं है।' : 'No submitted exams found for this period.' }}</p>
                </section>
                <footer class="border-t pt-3 text-xs text-slate-500">ExamTestHub · {{ hi ? 'यह रिपोर्ट केवल आपके खाते के प्रदर्शन डेटा पर आधारित है।' : 'This report contains your own performance data.' }}</footer>
            </section>
        </div>
    </PortalLayout>
</template>

<style>
@media print {
    @page { size: A4; margin: 12mm; }
    body { background: #fff !important; }
    body * { visibility: hidden !important; }
    #student-report, #student-report * { visibility: visible !important; }
    #student-report { position: absolute; inset: 0 auto auto 0; width: 100%; border: 0 !important; padding: 0 !important; }
    .no-print { display: none !important; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
}
</style>
