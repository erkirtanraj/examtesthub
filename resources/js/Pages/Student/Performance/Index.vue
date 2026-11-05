<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Activity, BookOpen, GraduationCap, Trophy } from 'lucide-vue-next';

const props = defineProps({ summary: Object, recent_attempts: Array });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const chartAttempts = computed(() => [...props.recent_attempts].slice(0, 8).reverse());
const maxPercentage = computed(() => Math.max(1, ...chartAttempts.value.map(item => Math.max(0, item.percentage || 0))));
function percent(value) {
    return value === null || value === undefined ? '—' : Number(value).toFixed(1) + '%';
}
function dateTime(value) {
    return value ? new Date(value).toLocaleString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}
</script>

<template>
    <PortalLayout
        portal="student"
        :title="hi ? 'मेरा प्रदर्शन' : 'My Performance'"
        :subtitle="hi ? 'अपने परीक्षा प्रयास, औसत स्कोर और हाल के परिणाम देखें।' : 'Track your submitted exam attempts, average score, best score and recent results.'"
    >
        <div class="space-y-5">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border bg-white p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'पूर्ण परीक्षाएँ' : 'Completed exams' }}</span><Activity :size="18" class="text-indigo-600"/></div><div class="mt-3 text-2xl font-extrabold tabular-nums">{{ summary.attempts }}</div></article>
                <article class="rounded-2xl border bg-white p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'औसत स्कोर' : 'Average score' }}</span><GraduationCap :size="18" class="text-indigo-600"/></div><div class="mt-3 text-2xl font-extrabold tabular-nums">{{ percent(summary.average_percentage) }}</div></article>
                <article class="rounded-2xl border bg-white p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'सर्वश्रेष्ठ स्कोर' : 'Best score' }}</span><Trophy :size="18" class="text-amber-600"/></div><div class="mt-3 text-2xl font-extrabold tabular-nums">{{ percent(summary.best_percentage) }}</div></article>
                <article class="rounded-2xl border bg-white p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'मेरे कोर्स' : 'My courses' }}</span><BookOpen :size="18" class="text-indigo-600"/></div><div class="mt-3 text-2xl font-extrabold tabular-nums">{{ summary.course_enrollments }}</div></article>
                <article class="rounded-2xl border bg-white p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'टेस्ट सीरीज़' : 'Test series' }}</span><GraduationCap :size="18" class="text-indigo-600"/></div><div class="mt-3 text-2xl font-extrabold tabular-nums">{{ summary.series_enrollments }}</div></article>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'हाल के परीक्षा परिणाम' : 'Recent exam results' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'केवल आपके सबमिट किए गए प्रयास' : 'Only your own submitted attempts are shown.' }}</p></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">Exam</th><th class="p-3">Score</th><th class="p-3">Percentage</th><th class="p-3">Correct</th><th class="p-3">Wrong</th><th class="p-3">Date</th></tr></thead>
                            <tbody>
                                <tr v-for="attempt in recent_attempts" :key="attempt.id" class="border-t">
                                    <td class="p-3 font-semibold">{{ attempt.exam }}</td><td class="p-3 tabular-nums">{{ attempt.score }} / {{ attempt.total_marks }}</td><td class="p-3 font-bold tabular-nums">{{ percent(attempt.percentage) }}</td><td class="p-3 tabular-nums text-emerald-700">{{ attempt.correct }}</td><td class="p-3 tabular-nums text-rose-700">{{ attempt.incorrect }}</td><td class="p-3 text-xs text-slate-500">{{ dateTime(attempt.submitted_at) }}</td>
                                </tr>
                                <tr v-if="!recent_attempts.length"><td colspan="6" class="p-8 text-center text-slate-500">{{ hi ? 'अभी कोई परीक्षा परिणाम उपलब्ध नहीं है।' : 'No submitted exam results yet. Start an exam to build your performance history.' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-2xl border bg-white p-4">
                    <h2 class="font-extrabold">{{ hi ? 'स्कोर इतिहास' : 'Score history' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'आपके नवीनतम 8 परिणाम' : 'Your latest 8 submitted results' }}</p>
                    <div v-if="chartAttempts.length" class="mt-5 flex h-44 items-end gap-2 border-b border-slate-100 pb-1">
                        <div v-for="(attempt, index) in chartAttempts" :key="attempt.id" class="group flex h-full min-w-0 flex-1 flex-col justify-end">
                            <div class="w-full rounded-t bg-indigo-500 group-hover:bg-indigo-700" :style="{ height: (attempt.percentage === null ? 2 : Math.max(5, Math.max(0, attempt.percentage) / maxPercentage * 100)) + '%' }" :title="attempt.exam + ': ' + percent(attempt.percentage)"/>
                            <span class="mt-2 truncate text-center text-[10px] text-slate-400">{{ index + 1 }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-8 rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'अभी स्कोर इतिहास उपलब्ध नहीं है।' : 'Your score history will appear after your first submitted exam.' }}</p>
                    <div class="mt-4 rounded-xl bg-indigo-50 p-3 text-xs leading-5 text-indigo-800">{{ hi ? 'औसत और सर्वश्रेष्ठ स्कोर आपके सबमिट किए गए प्रयासों से निकाले जाते हैं।' : 'Average and best scores are calculated from your submitted exam attempts.' }}</div>
                </article>
            </section>
        </div>
    </PortalLayout>
</template>
