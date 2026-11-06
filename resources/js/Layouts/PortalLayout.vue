<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    Activity, BookOpen, Brain, ChevronDown, ClipboardList, CreditCard, Bell,
    GraduationCap, LayoutDashboard, Library, Menu, ShieldCheck, Users,
    X, UserRound, Settings, FileText, BarChart3, Layers, Mail, LogOut, ChevronRight,
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
const profileMenuOpen = ref(false);
const expandedGroups = ref(['Dashboard']);
const profileHref = computed(() => isAdmin.value ? '/admin/profile' : '/student/profile');
const profileLabel = computed(() => isHindi.value ? 'मेरी प्रोफ़ाइल' : 'My Profile');
const accountLabel = computed(() => isHindi.value ? 'खाता' : 'Account');
const userInitials = computed(() => (user.value?.name || 'U').trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join(''));

const iconMap = {
    dashboard: LayoutDashboard, users: Users, academic: Library, questions: Brain,
    exams: ClipboardList, series: Layers, payments: CreditCard, activity: Activity,
    profile: UserRound, courses: BookOpen, materials: FileText, reports: BarChart3,
    security: ShieldCheck, settings: Settings, learning: GraduationCap, notifications: Bell,
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
            { label: isHindi.value ? 'विद्यार्थी प्रबंधन' : 'Student Management', href: '/admin/students', permission: 'users.view' },
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
            { label: isHindi.value ? 'भुगतान और राजस्व' : 'Payments & Revenue', href: '/admin/payments', permission: 'payments.view' },
        ],
    },
    {
        key: 'Reporting', label: isHindi.value ? 'रिपोर्ट और एनालिटिक्स' : 'Reports & Analytics', icon: 'reports',
        items: [
            { label: isHindi.value ? 'प्रदर्शन डैशबोर्ड' : 'Performance dashboard', href: '/admin/reports', permission: 'reports.view' },
            { label: isHindi.value ? 'LMS कोर्स एनालिटिक्स' : 'LMS Course Analytics', href: '/admin/course-analytics', permission: 'reports.view' },
            { label: isHindi.value ? 'परीक्षा एनालिटिक्स' : 'Exam Analytics', href: '/admin/exam-analytics', permission: 'reports.view' },
        ],
    },
    {
        key: 'Administration', label: isHindi.value ? 'प्रशासन और सुरक्षा' : 'Administration & Security', icon: 'security',
        items: [
            { label: isHindi.value ? 'गतिविधि लॉग' : 'Audit Activity', href: '/admin/activity', permission: 'audit.view' },
            { label: isHindi.value ? 'मेरी प्रोफ़ाइल' : 'My Profile', href: '/admin/profile', permission: 'profile.manage' },
            { label: isHindi.value ? 'घोषणाएँ' : 'Announcements', href: '/admin/announcements', permission: 'settings.manage' },
        ],
    },
    {
        key: 'Settings', label: isHindi.value ? 'सेटिंग्स' : 'Settings', icon: 'settings',
        items: [
            { label: isHindi.value ? 'विद्यार्थी प्रोफ़ाइल सेटिंग्स' : 'Student Profile Settings', href: '/admin/settings/student-profile', permission: 'settings.manage' },
            { label: isHindi.value ? 'वेबसाइट कंटेंट मैनेजर' : 'Website Content Manager', href: '/admin/settings/website-content', permission: 'settings.manage' },
        ],
    },
]);

const studentItems = computed(() => [
    { label: isHindi.value ? 'डैशबोर्ड' : 'Dashboard', href: '/student/dashboard', icon: 'dashboard' },
    { label: isHindi.value ? 'सूचनाएँ' : 'Notifications', href: '/student/notifications', icon: 'notifications' },
    { label: isHindi.value ? 'मेरी प्रोफ़ाइल' : 'My Profile', href: '/student/profile', icon: 'profile', permission: 'profile.manage' },
    { label: isHindi.value ? 'कोर्स कैटलॉग' : 'Course Catalogue', href: '/student/courses', icon: 'courses' },
    { label: isHindi.value ? 'मेरे कोर्स' : 'My Courses', href: '/student/my-courses', icon: 'learning' },
    { label: isHindi.value ? 'मेरा प्रदर्शन' : 'My Performance', href: '/student/performance', icon: 'reports', permission: 'results.view-own' },
    { label: isHindi.value ? 'विस्तृत रिपोर्ट' : 'Detailed Report', href: '/student/reports', icon: 'reports', permission: 'results.view-own' },
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
function closeProfileMenu() { profileMenuOpen.value = false; }
function logout() { closeProfileMenu(); router.post('/logout'); }
function switchLanguage(next) { router.get('/language/' + next); }
watch(() => page.url, () => { closeMobile(); closeProfileMenu(); });
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-sm shadow-slate-900/[0.03] backdrop-blur-xl">
            <div class="mx-auto flex min-h-[76px] max-w-[1600px] items-center justify-between gap-2 px-3 py-3 sm:gap-4 sm:px-5 lg:px-6">
                <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 lg:hidden" @click="mobileOpen = !mobileOpen" :aria-label="mobileOpen ? 'Close navigation' : 'Open navigation'" :aria-expanded="mobileOpen">
                        <X v-if="mobileOpen" :size="20" />
                        <Menu v-else :size="20" />
                    </button>
                    <a href="/" class="group flex min-w-0 items-center gap-2.5" aria-label="ExamTestHub home">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-black tracking-tight text-white shadow-md shadow-indigo-600/20 sm:h-11 sm:w-11">ET</span>
                        <span class="min-w-0 leading-tight"><span class="block truncate text-base font-extrabold tracking-tight text-slate-950 sm:text-lg">ExamTest<span class="text-indigo-600">Hub</span></span><span class="hidden text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400 sm:block">Learn · Practice · Succeed</span></span>
                    </a>
                    <span class="hidden rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-[10px] font-bold tracking-[0.12em] text-indigo-700 sm:inline-flex">{{ isAdmin ? (isHindi ? 'एडमिन पोर्टल' : 'ADMIN PORTAL') : (isHindi ? 'स्टूडेंट पोर्टल' : 'STUDENT PORTAL') }}</span>
                </div>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
                    <div class="hidden items-center rounded-xl border border-slate-200 bg-slate-50/80 p-1 sm:flex">
                        <button type="button" @click="switchLanguage('en')" class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition sm:px-3" :class="!isHindi ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">EN</button>
                        <button type="button" @click="switchLanguage('hi')" class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition sm:px-3" :class="isHindi ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">हिंदी</button>
                    </div>
                    <a v-if="!isAdmin || hasPermission('settings.manage')" :href="isAdmin ? '/admin/announcements' : '/student/notifications'" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:h-11 sm:w-11" aria-label="Notifications" title="Notifications"><Bell :size="19" /><span v-if="!isAdmin && page.props.unreadNotificationsCount" class="absolute -right-1 -top-1 min-w-5 rounded-full bg-rose-600 px-1 text-center text-[10px] font-bold leading-5 text-white">{{ page.props.unreadNotificationsCount > 99 ? '99+' : page.props.unreadNotificationsCount }}</span></a>
                    <a v-if="hasPermission('profile.manage')" :href="profileHref" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 sm:h-11 sm:w-11" :aria-label="profileLabel" :title="profileLabel"><UserRound :size="19" /></a>
                    <div v-if="user" class="relative">
                        <button type="button" class="flex max-w-[min(48vw,240px)] items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-2 shadow-sm transition hover:border-indigo-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 sm:gap-2.5 sm:pl-2 sm:pr-3" @click="profileMenuOpen = !profileMenuOpen" :aria-expanded="profileMenuOpen" aria-haspopup="menu">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold text-white sm:h-9 sm:w-9">{{ userInitials }}</span>
                            <span class="hidden min-w-0 text-left sm:block"><span class="block max-w-28 truncate text-xs font-bold text-slate-800 sm:max-w-36 sm:text-sm">{{ user.name }}</span><span class="block text-[10px] font-medium text-slate-400">{{ accountLabel }}</span></span>
                            <ChevronDown :size="15" class="shrink-0 text-slate-400 transition-transform" :class="profileMenuOpen ? 'rotate-180' : ''" />
                        </button>
                        <div v-if="profileMenuOpen" class="absolute right-0 top-[calc(100%+10px)] z-[60] w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10" role="menu">
                            <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                                <p class="truncate text-sm font-bold text-slate-900">{{ user.name }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-slate-500"><Mail :size="12" class="shrink-0" />{{ user.email }}</p>
                            </div>
                            <div class="p-2">
                                <a v-if="hasPermission('profile.manage')" :href="profileHref" role="menuitem" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700" @click="closeProfileMenu"><UserRound :size="17" />{{ profileLabel }}<ChevronRight :size="15" class="ml-auto text-slate-400" /></a>
                                <button type="button" role="menuitem" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 transition hover:bg-rose-50 hover:text-rose-700" @click="logout"><LogOut :size="17" />{{ isHindi ? 'लॉग आउट' : 'Sign out' }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center rounded-xl border border-slate-200 bg-slate-50/80 p-0.5 sm:hidden">
                        <button type="button" @click="switchLanguage('en')" class="rounded-lg px-2 py-1.5 text-[11px] font-bold" :class="!isHindi ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'">EN</button>
                        <button type="button" @click="switchLanguage('hi')" class="rounded-lg px-2 py-1.5 text-[11px] font-bold" :class="isHindi ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'">हि</button>
                    </div>
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
