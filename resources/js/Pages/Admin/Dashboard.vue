<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
import { Users, ShieldCheck, Brain, ClipboardList, Layers, Library, Activity, UserRound, ArrowUpRight } from 'lucide-vue-next';

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
    { label: 'गतिविधि लॉग', detail: 'प्रशासनिक बदलावों का रिकॉर्ड', href: '/admin/activity', permission: 'audit.view', icon: Activity },
    { label: 'मेरी प्रोफ़ाइल', detail: 'प्रोफ़ाइल और पासवर्ड सुरक्षा', href: '/admin/profile', permission: 'profile.manage', icon: UserRound },
] : [
    { label: 'User management', detail: 'Student and staff accounts', href: '/admin/users', permission: 'users.view', icon: Users },
    { label: 'Role management', detail: 'Roles and granular permissions', href: '/admin/roles', permission: 'roles.manage', icon: ShieldCheck },
    { label: 'Question bank', detail: 'Create, edit, filter and import questions', href: '/admin/questions', permission: 'questions.manage', icon: Brain },
    { label: 'Exam management', detail: 'Build exams, select questions and schedule', href: '/admin/exams', permission: 'tests.manage', icon: ClipboardList },
    { label: 'Test series', detail: 'Manage free and paid packages and student enrollments', href: '/admin/test-series', permission: 'tests.manage', icon: Layers },
    { label: 'LMS courses', detail: 'Build courses, chapters, lessons and enrollments', href: '/admin/courses', permission: 'courses.view', icon: Library },
    { label: 'Subjects & topics', detail: 'Organize the question bank taxonomy', href: '/admin/subjects', permission: 'questions.manage', icon: Library },
    { label: 'Audit activity', detail: 'Administrative change history', href: '/admin/activity', permission: 'audit.view', icon: Activity },
    { label: 'My profile', detail: 'Profile and password security', href: '/admin/profile', permission: 'profile.manage', icon: UserRound },
]);
const visibleCards = computed(() => cards.value.filter(item => page.props.auth?.user?.permissions?.includes(item.permission)));
</script>

<template>
    <PortalLayout portal="admin" :title="hi ? 'एडमिन डैशबोर्ड' : 'Admin dashboard'" :subtitle="hi ? 'ExamTestHub संचालन और प्रबंधन का केंद्रीय स्थान।' : 'Your central workspace for ExamTestHub operations and management.'">
        <section class="overflow-hidden rounded-2xl bg-slate-900 p-5 text-white sm:p-7">
            <p class="text-xs font-bold tracking-widest text-indigo-200 sm:text-sm">{{ hi ? 'सुरक्षित प्रशासनिक क्षेत्र' : 'SECURE ADMINISTRATOR WORKSPACE' }}</p>
            <div class="mt-3 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <h2 class="text-xl font-extrabold sm:text-2xl">{{ hi ? 'स्वागत है' : 'Welcome to ExamTestHub' }}, {{ page.props.auth?.user?.name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ hi ? 'आपकी भूमिका और अनुमतियों के अनुसार प्रशासनिक सुविधाएँ उपलब्ध होंगी।' : 'Your available administration capabilities are controlled by your assigned role and permissions.' }}</p>
                </div>
                <span class="inline-flex w-fit shrink-0 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-indigo-100">{{ hi ? 'प्रशासन' : 'Administrator' }}</span>
            </div>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ hi ? 'त्वरित प्रबंधन' : 'Quick management' }}</h2>
                <span class="text-xs text-slate-500">{{ visibleCards.length }} {{ hi ? 'मॉड्यूल उपलब्ध' : 'modules available' }}</span>
            </div>
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
                <a v-for="card in visibleCards" :key="card.label" :href="card.href" class="group flex min-w-0 items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md sm:p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><component :is="card.icon" :size="21" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="break-words text-sm font-bold text-slate-900 sm:text-base">{{ card.label }}</span>
                            <ArrowUpRight :size="16" class="shrink-0 text-slate-400 transition group-hover:text-indigo-600" />
                        </span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500">{{ card.detail }}</span>
                    </span>
                </a>
            </div>
        </section>
    </PortalLayout>
</template>
