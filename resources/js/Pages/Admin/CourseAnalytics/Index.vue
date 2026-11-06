<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { BookOpen, CheckCircle2, Clock3, GraduationCap, Users } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
    courses: Array,
    recent_enrollments: Array,
    stalled_enrollments: Array,
});
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');

function changeDays(event) {
    router.get('/admin/course-analytics', { days: Number(event.target.value) }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
function number(value) {
    return new Intl.NumberFormat('en-IN').format(value || 0);
}
function percent(value) {
    return Number(value || 0).toFixed(1) + '%';
}
function dateTime(value) {
    return value ? new Date(value).toLocaleString(hi.value ? 'hi-IN' : 'en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}
</script>

<template>
    <PortalLayout
        portal="admin"
        :title="hi ? 'LMS कोर्स एनालिटिक्स' : 'LMS Course Analytics'"
        :subtitle="hi ? 'कोर्स नामांकन, पूर्णता और निष्क्रिय विद्यार्थियों की निगरानी करें।' : 'Monitor course enrollments, completion outcomes and learners who may need follow-up.'"
    >
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4">
                <div>
                    <h2 class="font-extrabold">{{ hi ? 'रिपोर्टिंग अवधि' : 'Reporting period' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'नए नामांकन चुनी गई अवधि के हैं; कुल आँकड़े सभी सक्रिय और पूर्ण नामांकन दिखाते हैं।' : 'New enrollments use the selected period; totals include all active and completed enrollments.' }}</p>
                </div>
                <select :value="filters.days" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold" @change="changeDays">
                    <option :value="7">{{ hi ? 'पिछले 7 दिन' : 'Last 7 days' }}</option>
                    <option :value="30">{{ hi ? 'पिछले 30 दिन' : 'Last 30 days' }}</option>
                    <option :value="90">{{ hi ? 'पिछले 90 दिन' : 'Last 90 days' }}</option>
                </select>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'कोर्स' : 'Courses' }}</span><BookOpen :size="18" class="text-indigo-600"/></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.courses) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'प्रकाशित और ड्राफ्ट' : 'Published and draft courses' }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'कुल नामांकन' : 'Total enrollments' }}</span><Users :size="18" class="text-indigo-600"/></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.enrollments) }}</div>
                    <p class="mt-1 text-xs text-emerald-700">+{{ number(summary.new_enrollments) }} {{ hi ? 'चुनी अवधि में' : 'in selected period' }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'कोर्स पूर्णता दर' : 'Course completion rate' }}</span><CheckCircle2 :size="18" class="text-emerald-600"/></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ percent(summary.completion_rate) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ number(summary.completed_enrollments) }} {{ hi ? 'पूर्ण नामांकन' : 'completed enrollments' }}</p>
                </article>
                <article class="rounded-2xl border bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ hi ? 'फॉलो-अप आवश्यक' : 'Needs follow-up' }}</span><Clock3 :size="18" class="text-amber-600"/></div>
                    <div class="mt-3 text-2xl font-extrabold tabular-nums">{{ number(summary.stalled_enrollments) }}</div>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? '7+ दिनों से गतिविधि नहीं' : 'No recorded activity for 7+ days' }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border bg-white">
                <div class="border-b p-4">
                    <h2 class="font-extrabold">{{ hi ? 'कोर्स के अनुसार प्रदर्शन' : 'Course-wise performance' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ hi ? 'नामांकन, सक्रिय शिक्षार्थी और पूर्णता दर' : 'Enrollment volume, active learners and completion outcomes' }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr><th class="p-3">{{ hi ? 'कोर्स' : 'Course' }}</th><th class="p-3">{{ hi ? 'स्थिति' : 'Status' }}</th><th class="p-3">{{ hi ? 'सेक्शन' : 'Sections' }}</th><th class="p-3">{{ hi ? 'पाठ' : 'Lessons' }}</th><th class="p-3">{{ hi ? 'नामांकन' : 'Enrolled' }}</th><th class="p-3">{{ hi ? 'सक्रिय' : 'Active' }}</th><th class="p-3">{{ hi ? 'पूर्ण' : 'Completed' }}</th><th class="p-3">{{ hi ? 'पूर्णता' : 'Completion' }}</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="course in courses" :key="course.id" class="border-t">
                                <td class="max-w-[240px] p-3 font-semibold">{{ course.title }}</td>
                                <td class="p-3"><span class="rounded-full px-2 py-1 text-[11px] font-bold" :class="course.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ course.status }}</span></td>
                                <td class="p-3 tabular-nums">{{ number(course.sections) }}</td><td class="p-3 tabular-nums">{{ number(course.lessons) }}</td><td class="p-3 tabular-nums">{{ number(course.enrollments) }}</td><td class="p-3 tabular-nums">{{ number(course.active) }}</td><td class="p-3 tabular-nums">{{ number(course.completed) }}</td>
                                <td class="min-w-[130px] p-3"><div class="flex items-center gap-2"><div class="h-2 min-w-12 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-500" :style="{ width: course.completion_rate + '%' }"/></div><span class="w-12 text-right text-xs font-bold tabular-nums">{{ percent(course.completion_rate) }}</span></div></td>
                            </tr>
                            <tr v-if="!courses.length"><td colspan="8" class="p-8 text-center text-slate-500">{{ hi ? 'अभी कोई कोर्स उपलब्ध नहीं है।' : 'No courses are available to report.' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'हाल के नामांकन' : 'Recent enrollments' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'चुनी गई अवधि के नवीनतम 10 नामांकन' : 'Latest 10 enrollments in the selected period' }}</p></div>
                    <div class="divide-y">
                        <div v-for="item in recent_enrollments" :key="item.id" class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ item.student }}</p><p class="truncate text-xs text-slate-500">{{ item.course }} · {{ dateTime(item.enrolled_at) }}</p></div>
                            <span class="shrink-0 rounded-full px-2 py-1 text-[11px] font-bold" :class="item.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700'">{{ item.status }}</span>
                        </div>
                        <p v-if="!recent_enrollments.length" class="p-6 text-center text-sm text-slate-500">{{ hi ? 'इस अवधि में कोई नया नामांकन नहीं।' : 'No new enrollments in this period.' }}</p>
                    </div>
                </article>
                <article class="overflow-hidden rounded-2xl border bg-white">
                    <div class="border-b p-4"><h2 class="font-extrabold">{{ hi ? 'विद्यार्थी फॉलो-अप सूची' : 'Learners to follow up' }}</h2><p class="mt-1 text-xs text-slate-500">{{ hi ? 'सक्रिय नामांकन जिनमें 7 दिनों से गतिविधि दर्ज नहीं हुई' : 'Active enrollments with no recorded lesson activity for at least 7 days' }}</p></div>
                    <div class="divide-y">
                        <div v-for="item in stalled_enrollments" :key="item.id" class="p-3">
                            <p class="truncate text-sm font-semibold">{{ item.student }}</p><p class="truncate text-xs text-slate-500">{{ item.course }} · {{ item.email }}</p>
                            <p class="mt-1 text-[11px] text-amber-700">{{ hi ? 'अंतिम गतिविधि' : 'Last activity' }}: {{ dateTime(item.last_activity_at || item.enrolled_at) }}</p>
                        </div>
                        <p v-if="!stalled_enrollments.length" class="p-6 text-center text-sm text-slate-500">{{ hi ? 'अभी किसी फॉलो-अप की आवश्यकता नहीं है।' : 'No stalled active enrollments found.' }}</p>
                    </div>
                </article>
            </section>
        </div>
    </PortalLayout>
</template>
