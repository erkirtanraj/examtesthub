<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
import { Users, ShieldCheck, Brain, ClipboardList, Layers, Library, Activity, UserRound, ArrowUpRight, BarChart3, Settings2, Sparkles } from 'lucide-vue-next';

const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const cards = computed(() => hi.value ? [
    { label: 'उपयोगकर्ता प्रबंधन', detail: 'विद्यार्थी और स्टाफ खाते', href: '/admin/users', permission: 'users.view', icon: Users },
    { label: 'भूमिका प्रबंधन', detail: 'भूमिकाएं और अनुमतियां', href: '/admin/roles', permission: 'roles.manage', icon: ShieldCheck },
    { label: 'प्रश्न बैंक', detail: 'प्रश्न बनाएं, संपादित करें और आयात करें', href: '/admin/questions', permission: 'questions.manage', icon: Brain },
    { label: 'परीक्षा प्रबंधन', detail: 'परीक्षा बनाएं, प्रश्न चुनें और शेड्यूल करें', href: '/admin/exams', permission: 'tests.manage', icon: ClipboardList },
    { label: 'टेस्ट सीरीज़', detail: 'फ्री और पेड टेस्ट पैकेज तथा नामांकन प्रबंधित करें', href: '/admin/test-series', permission: 'tests.manage', icon: Layers },
    { label: 'LMS कोर्स', detail: 'कोर्स, अध्याय, पाठ और नामांकन प्रबंधित करें', href: '/admin/courses', permission: 'courses.view', icon: Library },
    { label: 'विषय और टॉपिक', detail: 'प्रश्न बैंक की संरचना व्यवस्थित करें', href: '/admin/subjects', permission: 'questions.manage', icon: Library },
    { label: 'रिपोर्ट और एनालिटिक्स', detail: 'परीक्षा प्रदर्शन, नामांकन और राजस्व देखें', href: '/admin/reports', permission: 'reports.view', icon: BarChart3 },
    { label: 'गतिविधि लॉग', detail: 'प्रशासनिक बदलावों का रिकॉर्ड', href: '/admin/activity', permission: 'audit.view', icon: Activity },
    { label: 'विद्यार्थी प्रोफ़ाइल सेटिंग्स', detail: 'प्रोफ़ाइल और पासवर्ड नियंत्रण', href: '/admin/settings/student-profile', permission: 'settings.manage', icon: Settings2 },
    { label: 'मेरी प्रोफ़ाइल', detail: 'प्रोफ़ाइल और पासवर्ड सुरक्षा', href: '/admin/profile', permission: 'profile.manage', icon: UserRound },
] : [
    { label: 'User management', detail: 'Student and staff accounts', href: '/admin/users', permission: 'users.view', icon: Users },
    { label: 'Role management', detail: 'Roles and granular permissions', href: '/admin/roles', permission: 'roles.manage', icon: ShieldCheck },
    { label: 'Question bank', detail: 'Create, edit, filter and import questions', href: '/admin/questions', permission: 'questions.manage', icon: Brain },
    { label: 'Exam management', detail: 'Build exams, select questions and schedule', href: '/admin/exams', permission: 'tests.manage', icon: ClipboardList },
    { label: 'Test series', detail: 'Manage free and paid packages and student enrollments', href: '/admin/test-series', permission: 'tests.manage', icon: Layers },
    { label: 'LMS courses', detail: 'Build courses, chapters, lessons and enrollments', href: '/admin/courses', permission: 'courses.view', icon: Library },
    { label: 'Subjects & topics', detail: 'Organize the question bank taxonomy', href: '/admin/subjects', permission: 'questions.manage', icon: Library },
    { label: 'Reports & analytics', detail: 'Review exam performance, enrollments and verified revenue', href: '/admin/reports', permission: 'reports.view', icon: BarChart3 },
    { label: 'Audit activity', detail: 'Administrative change history', href: '/admin/activity', permission: 'audit.view', icon: Activity },
    { label: 'Student profile settings', detail: 'Control student profile and password updates', href: '/admin/settings/student-profile', permission: 'settings.manage', icon: Settings2 },
    { label: 'My profile', detail: 'Profile and password security', href: '/admin/profile', permission: 'profile.manage', icon: UserRound },
]);
const visibleCards = computed(() => cards.value.filter(item => page.props.auth?.user?.permissions?.includes(item.permission)));
</script>

<template>
    <PortalLayout portal="admin" :title="hi ? 'एडमिन डैशबोर्ड' : 'Admin dashboard'" :subtitle="hi ? 'ExamTestHub संचालन और प्रबंधन का केंद्रीय स्थान।' : 'Your central workspace for ExamTestHub operations and management.'">
        <section class="relative isolate overflow-hidden rounded-3xl bg-slate-950 p-5 text-white shadow-xl shadow-indigo-950/10 sm:p-8">
            <div class="absolute -right-16 -top-24 -z-10 h-72 w-72 rounded-full bg-indigo-500/30 blur-3xl"></div>
            <div class="absolute -bottom-28 left-1/3 -z-10 h-64 w-64 rounded-full bg-violet-500/20 blur-3xl"></div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-300/20 bg-indigo-400/10 px-3 py-1.5 text-[11px] font-bold tracking-[.14em] text-indigo-100"><Sparkles :size="14" />{{ hi ? 'सुरक्षित प्रशासनिक क्षेत्र' : 'ADMIN CONTROL CENTER' }}</span>
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold text-slate-300">{{ hi ? 'भूमिका आधारित पहुँच' : 'Role-based access' }}</span>
            </div>
            <div class="mt-5 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ hi ? 'स्वागत है' : 'Welcome back' }}, {{ page.props.auth?.user?.name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">{{ hi ? 'आपकी भूमिका और अनुमतियों के अनुसार प्रशासनिक सुविधाएँ उपलब्ध होंगी।' : 'Manage learning, assessments and operations from one secure workspace. Your assigned permissions determine which modules are available.' }}</p>
                </div>
                <div class="flex w-fit items-center gap-3 rounded-2xl border border-white/10 bg-white/[.07] px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-400/15 text-indigo-200"><ShieldCheck :size="20" /></span>
                    <span><span class="block text-xs font-semibold text-slate-300">{{ hi ? 'उपलब्ध मॉड्यूल' : 'Available modules' }}</span><strong class="mt-0.5 block text-xl font-extrabold text-white">{{ visibleCards.length }}</strong></span>
                </div>
            </div>
        </section>

        <section class="mt-7">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div><p class="text-xs font-bold uppercase tracking-[.14em] text-indigo-600">{{ hi ? 'कार्यस्थान' : 'WORKSPACE' }}</p><h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">{{ hi ? 'त्वरित प्रबंधन' : 'Quick management' }}</h2><p class="mt-1 text-sm text-slate-500">{{ hi ? 'अपनी अनुमति वाले मॉड्यूल खोलें।' : 'Open a module to continue your work.' }}</p></div>
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500">{{ visibleCards.length }} {{ hi ? 'मॉड्यूल' : 'modules' }}</span>
            </div>
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 2xl:grid-cols-3">
                <a v-for="(card, index) in visibleCards" :key="card.href" :href="card.href" class="group flex min-w-0 items-start gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm shadow-slate-900/[.02] transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/[.06] sm:p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 text-indigo-700 ring-1 ring-indigo-100 transition group-hover:from-indigo-600 group-hover:to-violet-600 group-hover:text-white group-hover:ring-transparent"><component :is="card.icon" :size="21" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2"><span class="break-words text-sm font-bold text-slate-900 sm:text-base">{{ card.label }}</span><ArrowUpRight :size="16" class="shrink-0 text-slate-300 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-indigo-600" /></span>
                        <span class="mt-1.5 block text-sm leading-5 text-slate-500">{{ card.detail }}</span>
                    </span>
                </a>
            </div>
            <p v-if="!visibleCards.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">{{ hi ? 'आपके खाते में अभी कोई प्रबंधन अनुमति उपलब्ध नहीं है।' : 'No management modules are currently assigned to your account.' }}</p>
        </section>
    </PortalLayout>
</template>
