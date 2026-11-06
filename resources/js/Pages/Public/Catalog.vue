<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BookOpen, ClipboardCheck, FileText, Search, GraduationCap, LogOut, LayoutDashboard } from 'lucide-vue-next';

const props = defineProps({ type: String, items: Object, search: String });
const page = usePage();
const query = ref(props.search || '');
const user = computed(() => page.props.auth?.user || null);
const dashboardHref = computed(() => (user.value?.roles || []).includes('student') ? '/student/dashboard' : '/admin/dashboard');
const actionHref = computed(() => !user.value ? '/student/login' : (props.type === 'courses' ? '/student/courses' : props.type === 'materials' ? '/student/materials' : '/student/test-series'));
const config = computed(() => ({
    courses: { title: 'Courses', subtitle: 'Explore structured courses for your learning goals.', singular: 'course', icon: BookOpen, empty: 'No courses are published yet.' },
    materials: { title: 'Study Materials', subtitle: 'Browse notes and PDF resources by subject and language.', singular: 'material', icon: FileText, empty: 'No study materials are published yet.' },
    'test-series': { title: 'Test Series', subtitle: 'Compare free and paid exam preparation packages.', singular: 'test series', icon: ClipboardCheck, empty: 'No test series are published yet.' },
}[props.type]));
const nav = [
    { label: 'Courses', href: '/courses', active: 'courses' },
    { label: 'Study Materials', href: '/study-materials', active: 'materials' },
    { label: 'Test Series', href: '/test-series', active: 'test-series' },
];
function submitSearch() {
    router.get('/' + (props.type === 'materials' ? 'study-materials' : props.type), { search: query.value }, { preserveState: true, replace: true });
}
function logout() { router.post('/logout'); }
function loginUrl() { return '/student/login'; }
function money(item) { return item.access_type === 'free' ? 'FREE' : (item.currency || 'INR') + ' ' + item.price; }
function formatSize(bytes) {
    if (!bytes) return 'PDF';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), 3);
    return (bytes / (1024 ** i)).toFixed(i ? 1 : 0) + ' ' + units[i];
}
</script>

<template>
<Head :title="page.props.seo?.title || config.title + ' | ExamTestHub'">
    <meta head-key="description" name="description" :content="page.props.seo?.description || ''" />
    <link head-key="canonical" rel="canonical" :href="page.props.seo?.canonical || ''" />
</Head>
<main class="min-h-screen bg-slate-50 text-slate-900">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-3 sm:px-8">
            <a href="/" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-lg font-black text-white">E</span><span class="text-xl font-extrabold tracking-tight">ExamTest<span class="text-indigo-600">Hub</span></span></a>
            <nav class="order-3 flex w-full items-center gap-5 overflow-x-auto pb-1 text-sm font-semibold sm:order-none sm:w-auto sm:pb-0">
                <a v-for="link in nav" :key="link.active" :href="link.href" class="whitespace-nowrap transition hover:text-indigo-700" :class="type === link.active ? 'text-indigo-700' : 'text-slate-600'">{{ link.label }}</a>
            </nav>
            <div class="flex items-center gap-2">
                <template v-if="user">
                    <a :href="dashboardHref" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50"><LayoutDashboard :size="16"/> Dashboard</a>
                    <button @click="logout" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-700">Logout</button>
                </template>
                <template v-else>
                    <a :href="loginUrl()" class="rounded-xl px-3 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Login</a>
                    <a href="/register" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Sign up</a>
                </template>
            </div>
        </div>
    </header>

    <section class="bg-gradient-to-br from-indigo-950 via-indigo-900 to-violet-900 text-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
            <div class="flex items-center gap-3 text-indigo-200"><component :is="config.icon" :size="22"/><span class="text-xs font-extrabold uppercase tracking-[.2em]">ExamTestHub Catalogue</span></div>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-5xl">{{ config.title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-indigo-100 sm:text-base">{{ config.subtitle }}</p>
            <form @submit.prevent="submitSearch" class="mt-7 flex max-w-2xl gap-2 rounded-2xl bg-white p-2 shadow-xl">
                <Search class="ml-2 mt-2.5 shrink-0 text-slate-400" :size="19"/>
                <input v-model="query" class="min-w-0 flex-1 border-0 px-2 py-2 text-sm text-slate-900 outline-none ring-0 focus:ring-0" :placeholder="'Search ' + config.title.toLowerCase() + '...'" />
                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Search</button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div><p class="text-xs font-extrabold uppercase tracking-[.18em] text-indigo-600">Explore</p><h2 class="mt-2 text-2xl font-black">{{ config.title }} catalogue</h2></div>
            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ items.total }} results</span>
        </div>

        <div v-if="items.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="item in items.data" :key="item.id" class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg">
                <template v-if="type === 'courses'">
                    <div class="flex items-center justify-between gap-3"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">{{ item.category || 'Course' }}</span><span class="text-sm font-extrabold">{{ money(item) }}</span></div>
                    <h3 class="mt-4 text-lg font-extrabold">{{ item.title }}</h3>
                    <p class="mt-2 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ item.description || 'Explore this structured learning course.' }}</p>
                    <p class="mt-4 text-xs text-slate-500">{{ item.sections_count }} sections</p>
                </template>
                <template v-else-if="type === 'materials'">
                    <div class="flex items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-rose-50 text-xs font-black text-rose-700">PDF</span><div class="min-w-0"><h3 class="font-extrabold">{{ item.title }}</h3><p class="mt-1 text-xs text-slate-500">{{ item.subject || 'General' }} · {{ item.language === 'hi' ? 'Hindi' : item.language === 'both' ? 'Bilingual' : 'English' }}</p></div></div>
                    <p class="mt-3 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ item.description || 'Study notes and learning resources.' }}</p>
                    <div v-if="item.course" class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">Course: {{ item.course.title }} · {{ item.course.access_type === 'paid' ? 'Enrollment required' : 'Course linked' }}</div>
                    <p class="mt-4 text-xs text-slate-400">{{ formatSize(item.file_size) }} · Login required to download</p>
                </template>
                <template v-else>
                    <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><ClipboardCheck :size="21"/></span><span class="rounded-full px-3 py-1.5 text-xs font-extrabold" :class="item.access_type === 'paid' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'">{{ money(item) }}</span></div>
                    <h3 class="mt-4 text-lg font-extrabold">{{ item.title }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">{{ item.description || 'A structured package to support your exam preparation.' }}</p>
                    <p class="mt-4 text-xs text-slate-500">{{ item.exams_count }} exams included</p>
                </template>
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <a :href="actionHref" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                        {{ type === 'materials' ? 'Login to access' : type === 'courses' ? 'Login to enroll' : 'Login to enroll / purchase' }}
                    </a>
                </div>
            </article>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><component :is="config.icon" class="mx-auto text-slate-300" :size="34"/><h3 class="mt-3 font-extrabold">{{ config.empty }}</h3><p class="mt-1 text-sm text-slate-500">Please check back later.</p></div>

        <div v-if="items.last_page > 1" class="mt-8 flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 text-sm">
            <a v-if="items.prev_page_url" :href="items.prev_page_url" class="font-bold text-indigo-700">← Previous</a><span v-else class="text-slate-300">← Previous</span>
            <span class="text-xs text-slate-500">Page {{ items.current_page }} of {{ items.last_page }}</span>
            <a v-if="items.next_page_url" :href="items.next_page_url" class="font-bold text-indigo-700">Next →</a><span v-else class="text-slate-300">Next →</span>
        </div>
    </section>
    <footer class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-7 text-sm text-slate-500 sm:px-8"><a href="/" class="font-extrabold text-slate-800">ExamTestHub</a><span>Learn · Practice · Achieve</span><a href="/student/login" class="font-semibold text-indigo-700">Student Login</a></div></footer>
</main>
</template>
