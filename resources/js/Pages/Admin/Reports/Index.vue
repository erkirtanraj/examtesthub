<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Activity, BookOpen, CreditCard, GraduationCap, TrendingUp, Users } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
    trend: Array,
    top_exams: Array,
    recent_attempts: Array,
    recent_payments: Array,
});
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const chartDays = computed(() => props.trend.slice(-30));
const maxAttempts = computed(() => Math.max(1, ...chartDays.value.map(item => item.attempts)));
const maxRevenue = computed(() => Math.max(1, ...chartDays.value.map(item => item.revenue)));
const maxEnrollments = computed(() => Math.max(1, ...chartDays.value.map(item => item.course_enrollments + item.series_enrollments)));

function changeDays(event) {
    router.get('/admin/reports', { days: Number(event.target.value) }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
function money(value) {
    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(value || 0);
}
function number(value) {
    return new Intl.NumberFormat('en-IN').format(value || 0);
}
function dateTime(value) {
    return value ? new Date(value).toLocaleString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';
}
function percent(value) {
    return value === null || value === undefined ? '—' : Number(value).toFixed(1) + '%';
}
</script>

<template>
    <PortalLayout
        portal="admin"
        :title="hi ? 'रिपोर्ट और एनालिटिक्स' : 'Reports & Analytics'"
        :subtitle="hi ? 'परीक्षा प्रदर्शन, विद्यार्थी नामांकन और सत्यापित राजस्व का अवलोकन।' : 'Operational overview of exam performance, student enrollment and verified payment revenue.'"
    >
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4">
                <div>
                    <h2 class="font-extrabold">{{ hi ? 'प्रदर्शन अवधि' : 'Reporting period' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'सभी आँकड़े चुनी गई अवधि के हैं; कुल विद्यार्थी संख्या अब तक के पंजीकरण दिखाती है।' : 'Activity metrics use the selected period. Total students reflects all student accounts.' }}</p>
                </div>
                <select :value="filters.days" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold" @change="changeDays">
                    <option :value="7">{{ hi ? 'पिछले 7 दिन' : 'Last 7 days' }}</option>
                    <option :value="30">{{ hi ? 'पिछले 30 दिन' : 'Last 30 days' }}</option>
                    <option :value="90">{{ hi ? 'पिछले 90 दिन' : 'Last 90 days' }}</option>
                </select>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'कुल विद्यार्थी' : 'Total students' }}</span><Users :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.students_total) }}</div>
                    <p class="mt-1 text-xs text-emerald-700">+{{ number(summary.new_students) }} {{ hi ? 'इस अवधि में नए' : 'new in period' }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'सबमिट किए गए प्रयास' : 'Submitted attempts' }}</span><Activity :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.attempts) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'औसत स्कोर' : 'Average score' }}: {{ percent(summary.average_percentage) }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'सत्यापित राजस्व' : 'Verified revenue' }}</span><CreditCard :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ money(summary.revenue) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ number(summary.captured_payments) }} {{ hi ? 'सफल भुगतान' : 'captured payments' }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'नामांकन' : 'Enrollments' }}</span><GraduationCap :size="18" class="text-indigo-600" /></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.course_enrollments + summary.series_enrollments) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ number(summary.course_enrollments) }} {{ hi ? 'कोर्स' : 'courses' }} · {{ number(summary.series_enrollments) }} {{ hi ? 'सीरीज़' : 'series' }}</p>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="min-w-0 rounded-2xl border bg-white p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-2">
                        <div><h2 class="font-extrabold">{{ hi ? 'दैनिक परीक्षा गतिविधि' : 'Daily exam activity' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'सबमिट किए गए प्रयास' : 'Submitted attempts' }}</p></div>
                        <TrendingUp :size="19" class="text-indigo-600" />
                    </div>
                    <div v-if="chartDays.some(item => item.attempts > 0)" class="mt-5 flex h-36 items-end gap-1 overflow-x-auto border-b border-slate-100 pb-1">
                        <div v-for="item in chartDays" :key="item.date" class="group flex h-full min-w-[7px] flex-1 flex-col justify-end">
                            <div class="w-full rounded-t bg-indigo-500 transition group-hover:bg-indigo-700" :style="{ height: (item.attempts ? Math.max(5, item.attempts / maxAttempts * 100) : 1) + '%' }" :title="item.label + ': ' + item.attempts"/>
                        </div>
                    </div>
                    <p v-else class="mt-8 rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'इस अवधि में कोई परीक्षा सबमिट नहीं हुई।' : 'No submitted exam attempts in this period.' }}</p>
                    <div class="mt-2 flex justify-between text-[10px] text-slate-400"><span>{{ chartDays[0]?.label }}</span><span>{{ chartDays[chartDays.length - 1]?.label }}</span></div>
                </article>

                <article class="min-w-0 rounded-2xl border bg-white p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-2">
                        <div><h2 class="font-extrabold">{{ hi ? 'दैनिक सत्यापित राजस्व' : 'Daily verified revenue' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'केवल कैप्चर किए गए Razorpay भुगतान' : 'Captured Razorpay payments only' }}</p></div>
                        <CreditCard :size="19" class="text-emerald-600" />
                    </div>
                    <div v-if="chartDays.some(item => item.revenue > 0)" class="mt-5 flex h-36 items-end gap-1 overflow-x-auto border-b border-slate-100 pb-1">
                        <div v-for="item in chartDays" :key="item.date" class="group flex h-full min-w-[7px] flex-1 flex-col justify-end">
                            <div class="w-full rounded-t bg-emerald-500 transition group-hover:bg-emerald-700" :style="{ height: (item.revenue ? Math.max(5, item.revenue / maxRevenue * 100) : 1) + '%' }" :title="item.label + ': ' + money(item.revenue)"/>
                        </div>
                    </div>
                    <p v-else class="mt-8 rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">{{ hi ? 'इस अवधि में सत्यापित भुगतान नहीं हैं।' : 'No captured payments in this period.' }}</p>
                    <div class="mt-2 flex justify-between text-[10px] text-slate-400"><span>{{ chartDays[0]?.label }}</span><span>{{ chartDays[chartDays.length - 1]?.label }}</span></div>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'सबसे अधिक प्रयास वाली परीक्षाएँ' : 'Most attempted exams' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'अवधि के अनुसार रैंक' : 'Ranked by submitted attempts in this period' }}</p></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[440px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">Exam</th><th class="p-3">Attempts</th><th class="p-3">Students</th><th class="p-3">Avg. score</th></tr></thead>
                            <tbody>
                                <tr v-for="exam in top_exams" :key="exam.id" class="border-t">
                                    <td class="max-w-[220px] p-3 font-semibold">{{ exam.title }}</td><td class="p-3 tabular-nums">{{ number(exam.attempts) }}</td><td class="p-3 tabular-nums">{{ number(exam.students) }}</td><td class="p-3 tabular-nums">{{ percent(exam.average_percentage) }}</td>
                                </tr>
                                <tr v-if="!top_exams.length"><td colspan="4" class="p-6 text-center text-slate-500">{{ hi ? 'कोई डेटा उपलब्ध नहीं।' : 'No exam activity to report.' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'नामांकन प्रवृत्ति' : 'Enrollment trend' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'कोर्स और टेस्ट सीरीज़ के सक्रिय/पूर्ण नामांकन' : 'Active or completed course and test-series enrollments' }}</p></div>
                    <div class="space-y-3 p-4">
                        <div v-for="item in chartDays.slice(-7)" :key="item.date" class="grid grid-cols-[54px_minmax(0,1fr)_42px] items-center gap-3 text-xs">
                            <span class="text-slate-500">{{ item.label }}</span>
                            <div class="flex h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="bg-indigo-500" :style="{ width: (item.course_enrollments / maxEnrollments * 100) + '%' }"/>
                                <div class="bg-violet-400" :style="{ width: (item.series_enrollments / maxEnrollments * 100) + '%' }"/>
                            </div>
                            <span class="text-right font-semibold tabular-nums">{{ item.course_enrollments + item.series_enrollments }}</span>
                        </div>
                        <div class="flex flex-wrap gap-4 pt-1 text-xs text-slate-500"><span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-indigo-500"/>Courses</span><span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-violet-400"/>Test series</span></div>
                    </div>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'हाल के परीक्षा परिणाम' : 'Recent exam results' }}</h2></div>
                    <div class="divide-y">
                        <div v-for="attempt in recent_attempts" :key="attempt.id" class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ attempt.student }}</p><p class="truncate text-xs text-slate-500">{{ attempt.exam }} · {{ dateTime(attempt.submitted_at) }}</p></div>
                            <div class="shrink-0 text-right"><p class="text-sm font-extrabold tabular-nums">{{ percent(attempt.percentage) }}</p><p class="text-xs text-slate-500">{{ attempt.score }} / {{ attempt.total_marks }}</p></div>
                        </div>
                        <p v-if="!recent_attempts.length" class="p-6 text-center text-sm text-slate-500">No recent exam results.</p>
                    </div>
                </article>
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'हाल के सत्यापित भुगतान' : 'Recent captured payments' }}</h2></div>
                    <div class="divide-y">
                        <div v-for="payment in recent_payments" :key="payment.id" class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ payment.student }}</p><p class="truncate text-xs text-slate-500">{{ payment.item }} · {{ dateTime(payment.verified_at) }}</p></div>
                            <p class="shrink-0 text-sm font-extrabold tabular-nums">{{ money(payment.amount) }}</p>
                        </div>
                        <p v-if="!recent_payments.length" class="p-6 text-center text-sm text-slate-500">No captured payments in this period.</p>
                    </div>
                </article>
            </section>
        </div>
    </PortalLayout>
</template>
