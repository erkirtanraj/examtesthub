<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    Activity, BookOpen, Brain, ChevronDown, ClipboardList, CreditCard,
    GraduationCap, LayoutDashboard, Library, Menu, ShieldCheck, Users,
    X, UserRound, Settings, FileText, BarChart3, Layers,
} from 'lucide-vue-next';

const props = defineProps({
    portal: { type: String, required: true },
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const user = computed(() => page.props.auth?.user || null);
const isHindi = computed(() => locale.value === 'hi');
const isAdmin = computed(() => props.portal === 'admin');
const mobileOpen = ref(false);
const expandedGroups = ref(['Dashboard']);

const iconMap = {
    dashboard: LayoutDashboard, users: Users, academic: Library, questions: Brain,
    exams: ClipboardList, series: Layers, payments: CreditCard, activity: Activity,
    profile: UserRound, courses: BookOpen, materials: FileText, reports: BarChart3,
    security: ShieldCheck, settings: Settings, learning: GraduationCap,
};

const adminGroups = computed(() => [
    {
        key: 'Dashboard', label: isHindi.value ? 'डैशबोर्ड' : 'Dashboard', icon: 'dashboard',
        items: [{ label: isHindi.value ? 'अवलोकन' : 'Overview', href: '/admin/dashboard' }],
    },
    {
        key: 'Users', label: isHindi.value ? 'उपयोगकर्ता प्रबंधन' : 'User Management', icon: 'users',
        items: [
            { label: isHindi.value ? 'सभी उपयोगकर्ता' : 'All Users', href: '/admin/users', permission: 'users.view' },
            { label: isHindi.value ? 'भूमिकाएँ' : 'Roles', href: '/admin/roles', permission: 'roles.manage' },
            { label: isHindi.value ? 'अनुमतियाँ' : 'Permissions', href: '/admin/permissions', permission: 'permissions.manage' },
        ],
    },
    {
        key: 'Courses', label: isHindi.value ? 'LMS कोर्स प्रबंधन' : 'LMS Course Management', icon: 'courses',
        items: [
            { label: isHindi.value ? 'सभी कोर्स' : 'All Courses', href: '/admin/courses', permission: 'courses.view' },
            { label: isHindi.value ? 'PDF सामग्री' : 'Study Materials', href: '/admin/materials', permission: 'materials.manage' },
        ],
    },
    {
        key: 'Academics', label: isHindi.value ? 'शैक्षणिक प्रबंधन' : 'Academic Management', icon: 'academic',
        items: [
            { label: isHindi.value ? 'विषय और टॉपिक' : 'Subjects & Topics', href: '/admin/subjects', permission: 'questions.manage' },
            { label: isHindi.value ? 'प्रश्न बैंक' : 'Question Bank', href: '/admin/questions', permission: 'questions.manage' },
        ],
    },
    {
        key: 'Exams', label: isHindi.value ? 'परीक्षा प्रबंधन' : 'Exam Management', icon: 'exams',
        items: [
            { label: isHindi.value ? 'सभी परीक्षाएँ' : 'All Exams', href: '/admin/exams', permission: 'tests.manage' },
            { label: isHindi.value ? 'टेस्ट सीरीज़' : 'Test Series', href: '/admin/test-series', permission: 'tests.manage' },
            { label: isHindi.value ? 'भुगतान' : 'Payments', href: '/admin/payments', permission: 'tests.manage' },
        ],
    },
    {
        key: 'Reporting', label: isHindi.value ? 'रिपोर्ट और एनालिटिक्स' : 'Reports & Analytics', icon: 'reports',
        items: [
            { label: isHindi.value ? 'प्रदर्शन डैशबोर्ड' : 'Performance dashboard', href: '/admin/reports', permission: 'reports.view' },
        ],
    },
    {
        key: 'Administration', label: isHindi.value ? 'प्रशासन और सुरक्षा' : 'Administration & Security', icon: 'security',
        items: [
            { label: isHindi.value ? 'गतिविधि लॉग' : 'Audit Activity', href: '/admin/activity', permission: 'audit.view' },
            { label: isHindi.value ? 'मेरी प्रोफ़ाइल' : 'My Profile', href: '/admin/profile', permission: 'profile.manage' },
        ],
    },
]);

const studentItems = computed(() => [
    { label: isHindi.value ? 'डैशबोर्ड' : 'Dashboard', href: '/student/dashboard', icon: 'dashboard' },
    { label: isHindi.value ? 'कोर्स कैटलॉग' : 'Course Catalogue', href: '/student/courses', icon: 'courses' },
    { label: isHindi.value ? 'मेरे कोर्स' : 'My Courses', href: '/student/my-courses', icon: 'learning' },
    { label: isHindi.value ? 'मेरा प्रदर्शन' : 'My Performance', href: '/student/performance', icon: 'reports', permission: 'results.view-own' },
    { label: isHindi.value ? 'PDF सामग्री' : 'Study Materials', href: '/student/materials', icon: 'materials' },
    { label: isHindi.value ? 'टेस्ट सीरीज़' : 'Test Series', href: '/student/test-series', icon: 'series' },
    { label: isHindi.value ? 'मेरी टेस्ट सीरीज़' : 'My Test Series', href: '/student/my-test-series', icon: 'learning' },
    { label: isHindi.value ? 'उपलब्ध परीक्षाएँ' : 'Available Exams', href: '/student/exams', icon: 'exams' },
    { label: isHindi.value ? 'भुगतान इतिहास' : 'Payment History', href: '/student/payments', icon: 'payments' },
]);

const visibleStudentItems = computed(() => studentItems.value.filter(item => !item.permission || user.value?.permissions?.includes(item.permission)));

const visibleAdminGroups = computed(() => adminGroups.value.map(group => ({
    ...group,
    items: group.items.filter(item => !item.permission || user.value?.permissions?.includes(item.permission)),
})).filter(group => group.items.length));

function hasPermission(permission) {
    return !permission || user.value?.permissions?.includes(permission);
}
function isActive(href) {
    return page.url === href || page.url.startsWith(href + '?') || (href !== '/admin/dashboard' && href !== '/student/dashboard' && page.url.startsWith(href + '/'));
}
function toggleGroup(key) {
    expandedGroups.value = expandedGroups.value.includes(key)
        ? expandedGroups.value.filter(item => item !== key)
        : [...expandedGroups.value, key];
}
function closeMobile() { mobileOpen.value = false; }
function logout() { router.post('/logout'); }
function switchLanguage(next) { router.get('/language/' + next); }
watch(() => page.url, closeMobile);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex min-h-16 max-w-[1600px] items-center justify-between gap-3 px-3 py-3 sm:px-5 lg:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 lg:hidden" @click="mobileOpen = !mobileOpen" :aria-label="mobileOpen ? 'Close navigation' : 'Open navigation'">
                        <X v-if="mobileOpen" :size="21" />
                        <Menu v-else :size="21" />
                    </button>
                    <a href="/" class="truncate text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">ExamTest<span class="text-indigo-600">Hub</span></a>
                    <span class="hidden rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 sm:inline-flex">{{ isAdmin ? 'ADMIN' : 'STUDENT' }}</span>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-3">
                    <span class="hidden max-w-40 truncate text-sm font-medium text-slate-600 md:inline">{{ user?.name }}</span>
                    <div class="flex items-center rounded-xl border border-slate-200 p-0.5">
                        <button type="button" @click="switchLanguage('en')" class="rounded-lg px-2 py-1.5 text-xs font-semibold sm:text-sm" :class="!isHindi ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500'">EN</button>
                        <button type="button" @click="switchLanguage('hi')" class="rounded-lg px-2 py-1.5 text-xs font-semibold sm:text-sm" :class="isHindi ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500'">हिंदी</button>
                    </div>
                    <button type="button" @click="logout" class="rounded-xl border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:px-3 sm:text-sm">{{ isHindi ? 'लॉग आउट' : 'Log out' }}</button>
                </div>
            </div>
        </header>

        <div class="mx-auto grid max-w-[1600px] lg:grid-cols-[260px_minmax(0,1fr)]">
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="closeMobile"></div>

            <aside
                class="fixed inset-y-16 left-0 z-50 w-[min(86vw,320px)] overflow-y-auto border-r border-slate-200 bg-white p-3 shadow-xl transition-transform duration-200 lg:sticky lg:top-16 lg:z-20 lg:h-[calc(100vh-4rem)] lg:w-auto lg:translate-x-0 lg:shadow-none"
                :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="mb-4 flex items-center justify-between px-3 pt-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ isAdmin ? (isHindi ? 'प्रशासन' : 'Administration') : (isHindi ? 'विद्यार्थी पोर्टल' : 'Student portal') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ user?.name }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="closeMobile" aria-label="Close navigation"><X :size="19" /></button>
                </div>

                <nav v-if="isAdmin" class="space-y-1" aria-label="Admin navigation">
                    <section v-for="group in visibleAdminGroups" :key="group.key" class="rounded-xl">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition"
                            :class="group.items.some(item => isActive(item.href)) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50'"
                            @click="toggleGroup(group.key)"
                            :aria-expanded="expandedGroups.includes(group.key)"
                        >
                            <component :is="iconMap[group.icon]" :size="18" class="shrink-0" />
                            <span class="min-w-0 flex-1">{{ group.label }}</span>
                            <ChevronDown :size="16" class="shrink-0 transition-transform" :class="expandedGroups.includes(group.key) ? 'rotate-180' : ''" />
                        </button>
                        <div v-if="expandedGroups.includes(group.key)" class="ml-5 mt-1 space-y-1 border-l border-slate-200 pl-3 pb-2">
                            <a v-for="item in group.items" :key="item.href" :href="item.href" class="block rounded-lg px-3 py-2 text-sm transition" :class="isActive(item.href) ? 'bg-indigo-600 font-semibold text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'">
                                {{ item.label }}
                            </a>
                        </div>
                    </section>
                </nav>

                <nav v-else class="space-y-1" aria-label="Student navigation">
                    <a v-for="item in visibleStudentItems" :key="item.href" :href="item.href" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition" :class="isActive(item.href) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'">
                        <component :is="iconMap[item.icon]" :size="18" />
                        {{ item.label }}
                    </a>
                </nav>
            </aside>

            <main class="portal-main min-w-0 px-3 py-5 sm:px-5 sm:py-7 lg:px-8 lg:py-8">
                <div class="mb-5 sm:mb-7">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 sm:text-sm">{{ isAdmin ? 'ExamTestHub Admin' : 'ExamTestHub Learning' }}</p>
                    <h1 class="mt-1 break-words text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ title }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base">{{ subtitle }}</p>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
