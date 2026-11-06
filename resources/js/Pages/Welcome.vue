<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, BookOpen, ClipboardCheck, FileText, GraduationCap,
    TrendingUp, ShieldCheck, Menu, X, Sparkles, CheckCircle2,
} from 'lucide-vue-next';

const locale = ref('en');
const mobileMenuOpen = ref(false);
const page = usePage();
const homepageSections = computed(() => page.props.homepageSections || []);
const sectionEnabled = key => homepageSections.value.some(section => section.key === ('section.' + key) && section.is_published);
const sectionContent = key => { const section = homepageSections.value.find(item => item.key === ('section.' + key)); return section?.content?.[locale.value] || section?.content?.en || {}; };
const customSections = computed(() => homepageSections.value.filter(section => ['custom', 'html'].includes(section.content?.section_type)).sort((a, b) => a.sort_order - b.sort_order));
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const dashboardUrl = computed(() => {
    const roles = page.props.auth?.user?.roles ?? [];
    return roles.includes('student') ? '/student/dashboard' : '/admin/dashboard';
});
const logout = () => router.post('/logout');

const copy = computed(() => {
    const defaults = locale.value === 'hi' ? {
    navHome: 'होम', navCourses: 'कोर्स', navTests: 'टेस्ट सीरीज़', navMaterials: 'अध्ययन सामग्री',
    studentLogin: 'लॉगिन', adminLogin: 'एडमिन', register: 'रजिस्टर करें',
    eyebrow: 'आपकी सफलता की तैयारी यहीं से शुरू होती है',
    title: 'सीखें बेहतर। अभ्यास करें। सफलता पाएं।',
    description: 'प्रतियोगी परीक्षाओं की तैयारी के लिए कोर्स, ऑनलाइन टेस्ट सीरीज़ और अध्ययन सामग्री—सब कुछ एक ही जगह।',
    explore: 'तैयारी शुरू करें', browse: 'टेस्ट सीरीज़ देखें',
    trust: 'सीखने और अभ्यास के लिए आपका एक भरोसेमंद प्लेटफॉर्म',
    sectionEyebrow: 'एक ही प्लेटफॉर्म',
    sectionTitle: 'परीक्षा की तैयारी के लिए जरूरी सब कुछ',
    sectionText: 'अपनी तैयारी को व्यवस्थित रखें और हर कदम पर अपनी प्रगति पर ध्यान दें।',
    courses: 'ऑनलाइन कोर्स', coursesText: 'व्यवस्थित सेक्शन, अध्याय और पाठ के साथ अपनी गति से सीखें।',
    tests: 'ऑनलाइन टेस्ट सीरीज़', testsText: 'समयबद्ध टेस्ट का अभ्यास करें और अपने प्रदर्शन को समझें।',
    materials: 'अध्ययन सामग्री', materialsText: 'नोट्स और PDF संसाधनों को एक ही जगह एक्सेस करें।',
    performance: 'प्रदर्शन विश्लेषण', performanceText: 'अपने परिणाम और तैयारी की प्रगति को ट्रैक करें।',
    howEyebrow: 'कैसे शुरू करें', howTitle: 'तीन आसान कदम',
    step1: 'अपना अकाउंट बनाएं', step1Text: 'स्टूडेंट अकाउंट के लिए रजिस्टर करें।',
    step2: 'अपना कोर्स या टेस्ट चुनें', step2Text: 'अपनी जरूरत के अनुसार सीखना या अभ्यास करना शुरू करें।',
    step3: 'प्रगति पर नजर रखें', step3Text: 'अपने टेस्ट और सीखने के परिणाम देखें।',
    ctaTitle: 'आज ही अपनी तैयारी शुरू करें',
    ctaText: 'लॉगिन करें या नया अकाउंट बनाकर अपनी सीखने की यात्रा शुरू करें।',
    createAccount: 'नया अकाउंट बनाएं', signIn: 'स्टूडेंट लॉगिन',
    footerLine: 'सीखें · अभ्यास करें · सफलता पाएं', privacy: 'प्राइवेसी',
    terms: 'नियम और शर्तें', contact: 'संपर्क',
    adminAccess: 'एडमिन एक्सेस',
} : {
    navHome: 'Home', navCourses: 'Courses', navTests: 'Test Series', navMaterials: 'Study Materials',
    studentLogin: 'Login', adminLogin: 'Admin', register: 'Register',
    eyebrow: 'YOUR EXAM PREPARATION PARTNER',
    title: 'Prepare smarter. Learn faster. Achieve more.',
    description: 'Courses, online test series and study materials for competitive exam preparation—all in one place.',
    explore: 'Start preparing', browse: 'Explore test series',
    trust: 'One focused platform for learning, practice and progress',
    sectionEyebrow: 'ONE PLATFORM',
    sectionTitle: 'Everything you need to prepare',
    sectionText: 'Build a consistent study routine with learning resources and practice tools in one place.',
    courses: 'Online Courses', coursesText: 'Learn at your pace with structured sections, chapters and lessons.',
    tests: 'Online Test Series', testsText: 'Practice with timed assessments and review your performance.',
    materials: 'Study Materials', materialsText: 'Access notes and PDF resources in one convenient library.',
    performance: 'Performance Insights', performanceText: 'Review your results and keep track of your preparation.',
    howEyebrow: 'GETTING STARTED', howTitle: 'Your preparation in three steps',
    step1: 'Create your account', step1Text: 'Register for your student account.',
    step2: 'Choose what to study', step2Text: 'Explore courses or find a test series for your goals.',
    step3: 'Track your progress', step3Text: 'Review your attempts and learning progress.',
    ctaTitle: 'Take the next step in your preparation',
    ctaText: 'Sign in or create an account to get started with ExamTestHub.',
    createAccount: 'Create an account', signIn: 'Student Login',
    footerLine: 'Learn · Practice · Achieve', privacy: 'Privacy',
    terms: 'Terms', contact: 'Contact',
    adminAccess: 'Admin access',
    };
    const cms = page.props.websiteContent || [];
    const overrides = Object.fromEntries(
        cms
            .filter(item => ['home', 'menu', 'footer'].includes(item.type) && item.key)
            .map(item => [item.key, item.content?.[locale.value] || item.content?.en || ''])
    );
    return {
        ...defaults,
        ...Object.fromEntries(Object.entries(overrides).filter(([, value]) => value !== '')),
    };
});

const features = computed(() => [
    { icon: GraduationCap, title: copy.value.courses, description: copy.value.coursesText, href: '/courses', tone: 'indigo' },
    { icon: ClipboardCheck, title: copy.value.tests, description: copy.value.testsText, href: '/test-series', tone: 'amber' },
    { icon: FileText, title: copy.value.materials, description: copy.value.materialsText, href: '/study-materials', tone: 'emerald' },
    { icon: TrendingUp, title: copy.value.performance, description: copy.value.performanceText, href: '/student/login', tone: 'violet' },
]);

const steps = computed(() => [
    { number: '01', title: copy.value.step1, description: copy.value.step1Text },
    { number: '02', title: copy.value.step2, description: copy.value.step2Text },
    { number: '03', title: copy.value.step3, description: copy.value.step3Text },
]);
</script>

<template>
    <Head :title="page.props.seo?.title || 'ExamTestHub'">
        <meta head-key="description" name="description" :content="page.props.seo?.description || ''" />
        <link head-key="canonical" rel="canonical" :href="page.props.seo?.canonical || ''" />
    </Head>
    <main class="min-h-screen bg-white text-slate-900">
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3.5 sm:px-8">
                <a href="/" class="flex shrink-0 items-center gap-3" aria-label="ExamTestHub home">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-lg font-extrabold text-white shadow-lg shadow-indigo-200">E</span>
                    <span class="text-xl font-extrabold tracking-tight text-slate-950">ExamTest<span class="text-brand-600">Hub</span></span>
                </a>

                <nav class="hidden items-center gap-7 lg:flex" aria-label="Main navigation">
                    <a href="#home" class="text-sm font-semibold text-slate-600 transition hover:text-brand-700">{{ copy.navHome }}</a>
                    <a href="/courses" class="text-sm font-semibold text-slate-600 transition hover:text-brand-700">{{ copy.navCourses }}</a>
                    <a href="/test-series" class="text-sm font-semibold text-slate-600 transition hover:text-brand-700">{{ copy.navTests }}</a>
                    <a href="/study-materials" class="text-sm font-semibold text-slate-600 transition hover:text-brand-700">{{ copy.navMaterials }}</a>
                </nav>

                <div class="hidden items-center gap-2 lg:flex">
                    <div class="mr-2 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                        <button type="button" @click="locale = 'en'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition" :class="locale === 'en' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500'">EN</button>
                        <button type="button" @click="locale = 'hi'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition" :class="locale === 'hi' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500'">हिंदी</button>
                    </div>
                    <template v-if="!isAuthenticated">
                        <a href="/student/login" class="rounded-xl px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-indigo-50">{{ copy.studentLogin }}</a>
                        <a href="/register" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-indigo-200 transition hover:bg-brand-700">{{ copy.register }}</a>
                    </template>
                    <template v-else>
                        <a :href="dashboardUrl" class="rounded-xl px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-indigo-50">Dashboard</a>
                        <button type="button" @click="logout" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700">Logout</button>
                    </template>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <button type="button" @click="locale = locale === 'en' ? 'hi' : 'en'" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-bold text-slate-700">{{ locale === 'en' ? 'हिंदी' : 'EN' }}</button>
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-700" :aria-label="mobileMenuOpen ? 'Close menu' : 'Open menu'">
                        <X v-if="mobileMenuOpen" :size="19" /><Menu v-else :size="19" />
                    </button>
                </div>
            </div>

            <div v-if="mobileMenuOpen" class="border-t border-slate-100 bg-white px-5 py-4 lg:hidden">
                <nav class="flex flex-col gap-1">
                    <a @click="mobileMenuOpen = false" href="#home" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700">{{ copy.navHome }}</a>
                    <a @click="mobileMenuOpen = false" href="/courses" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700">{{ copy.navCourses }}</a>
                    <a @click="mobileMenuOpen = false" href="/study-materials" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700">{{ copy.navMaterials }}</a>
                    <div class="mt-2 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                        <template v-if="!isAuthenticated">
                            <a href="/student/login" class="rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-bold text-slate-700">{{ copy.studentLogin }}</a>
                            <a href="/register" class="rounded-xl bg-brand-600 px-3 py-2.5 text-center text-sm font-bold text-white">{{ copy.register }}</a>
                        </template>
                        <template v-else>
                            <a :href="dashboardUrl" class="rounded-xl border border-slate-200 px-3 py-2.5 text-center text-sm font-bold text-slate-700">Dashboard</a>
                            <button type="button" @click="logout" class="rounded-xl bg-slate-900 px-3 py-2.5 text-center text-sm font-bold text-white">Logout</button>
                        </template>
                    </div>
                </nav>
            </div>
        </header>

        <section v-if="sectionEnabled('hero')" id="home" class="relative isolate overflow-hidden bg-slate-50">
            <div class="pointer-events-none absolute -left-40 top-10 -z-10 h-[30rem] w-[30rem] rounded-full bg-indigo-200/40 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-40 bottom-0 -z-10 h-[28rem] w-[28rem] rounded-full bg-violet-200/40 blur-3xl"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_.95fr] lg:gap-16 lg:py-28">
                <div class="relative">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white px-4 py-2 text-xs font-extrabold tracking-wide text-brand-700 shadow-sm">
                        <Sparkles :size="15" /> {{ copy.eyebrow }}
                    </div>
                    <h1 class="max-w-2xl text-4xl font-black leading-[1.12] tracking-tight text-slate-950 sm:text-5xl lg:text-[3.7rem]">{{ sectionContent('hero').title || copy.title }}</h1>
                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">{{ sectionContent('hero').description || copy.description }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a :href="sectionContent('hero').cta_primary_url || '/register'" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-indigo-200 transition hover:-translate-y-0.5 hover:bg-brand-700">
                            {{ sectionContent('hero').cta_primary || copy.explore }} <ArrowRight :size="17" />
                        </a>
                        <a :href="sectionContent('hero').cta_secondary_url || '/test-series'" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-extrabold text-slate-700 transition hover:border-indigo-200 hover:text-brand-700">{{ sectionContent('hero').cta_secondary || copy.browse }}</a>
                    </div>
                    <div class="mt-8 flex items-center gap-2 text-sm font-medium text-slate-500">
                        <ShieldCheck :size="17" class="text-emerald-600" /> {{ copy.trust }}
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -right-4 -top-5 h-28 w-28 rounded-3xl bg-amber-200/70"></div>
                    <div class="absolute -bottom-5 -left-5 h-32 w-32 rounded-full bg-indigo-200/70 blur-sm"></div>
                    <div class="relative rounded-[1.75rem] border border-white bg-white p-5 shadow-2xl shadow-slate-300/60 sm:p-7">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-600">ExamTestHub</p>
                                <h2 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">{{ sectionContent('features').title || copy.sectionTitle }}</h2>
                            </div>
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-brand-600"><GraduationCap :size="25" /></span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-indigo-50 p-4">
                                <BookOpen :size="21" class="text-indigo-700" />
                                <p class="mt-3 text-sm font-extrabold text-slate-900">{{ copy.courses }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ copy.coursesText }}</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-4">
                                <ClipboardCheck :size="21" class="text-amber-700" />
                                <p class="mt-3 text-sm font-extrabold text-slate-900">{{ copy.tests }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ copy.testsText }}</p>
                            </div>
                            <div class="col-span-2 rounded-2xl bg-slate-950 p-5 text-white">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-[.15em] text-indigo-200">{{ copy.navMaterials }}</span>
                                    <CheckCircle2 :size="19" class="text-emerald-300" />
                                </div>
                                <p class="mt-2 text-lg font-extrabold">{{ copy.materials }}</p>
                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/15"><div class="h-full w-4/5 rounded-full bg-indigo-300"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="sectionEnabled('features')" id="features" class="scroll-mt-24 py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mx-auto mb-10 max-w-2xl text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[.2em] text-brand-600">{{ copy.sectionEyebrow }}</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ copy.sectionTitle }}</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">{{ sectionContent('features').description || copy.sectionText }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="(item, index) in features" :key="item.title" class="group rounded-2xl border border-slate-200 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-950/5">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl transition group-hover:text-white"
                            :class="['bg-indigo-50 text-indigo-700 group-hover:bg-indigo-600','bg-amber-50 text-amber-700 group-hover:bg-amber-600','bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600','bg-violet-50 text-violet-700 group-hover:bg-violet-600'][index]">
                            <component :is="item.icon" :size="22" />
                        </span>
                        <h3 class="mt-5 text-lg font-extrabold text-slate-900">{{ item.title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.description }}</p>
                        <a :href="item.href" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-brand-700 hover:text-brand-800">{{ copy.explore }} <ArrowRight :size="15" /></a>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="sectionEnabled('steps')" class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mb-9 text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[.2em] text-brand-600">{{ copy.howEyebrow }}</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ sectionContent('steps').title || copy.howTitle }}</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <article v-for="step in steps" :key="step.number" class="rounded-2xl border border-slate-200 bg-white p-6">
                        <span class="text-sm font-black tracking-widest text-brand-600">{{ step.number }}</span>
                        <h3 class="mt-4 text-lg font-extrabold text-slate-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="sectionEnabled('cta')" class="px-5 py-16 sm:px-8 sm:py-20">
            <div class="mx-auto max-w-5xl rounded-[2rem] bg-gradient-to-br from-indigo-700 via-indigo-700 to-violet-800 px-6 py-12 text-center text-white shadow-xl shadow-indigo-900/15 sm:px-12">
                <p class="text-xs font-extrabold uppercase tracking-[.2em] text-indigo-200">ExamTestHub</p>
                <h2 class="mx-auto mt-4 max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">{{ sectionContent('cta').title || copy.ctaTitle }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-indigo-100 sm:text-base">{{ sectionContent('cta').description || copy.ctaText }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="/register" class="rounded-xl bg-white px-6 py-3.5 text-sm font-extrabold text-indigo-700 shadow-sm transition hover:bg-indigo-50">{{ copy.createAccount }}</a>
                    <a href="/student/login" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3.5 text-sm font-extrabold text-white transition hover:bg-white/20">{{ copy.signIn }}</a>
                </div>
            </div>
        </section>

        <section v-for="block in customSections" :key="block.id" class="border-y border-slate-100 bg-white px-5 py-14 sm:px-8">
            <div class="mx-auto max-w-5xl text-center">
                <h2 v-if="block.content?.[locale]?.title || block.title" class="text-3xl font-black tracking-tight text-slate-950">{{ block.content?.[locale]?.title || block.title }}</h2>
                <div v-if="block.content?.[locale]?.html" class="prose mx-auto mt-4 max-w-3xl text-left" v-html="block.content[locale].html"></div>
                <p v-else-if="block.content?.[locale]?.description" class="mx-auto mt-4 max-w-3xl text-base leading-7 text-slate-600">{{ block.content[locale].description }}</p>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-8 sm:px-8 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-sm font-extrabold text-white">E</span>
                    <div><p class="font-extrabold text-slate-900">ExamTestHub</p><p class="text-xs text-slate-500">{{ copy.footerLine }}</p></div>
                </div>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium text-slate-500">
                    <a href="/student/login" class="hover:text-brand-700">{{ copy.studentLogin }}</a>
                </div>
                <p class="text-xs text-slate-400">© {{ new Date().getFullYear() }} ExamTestHub. All rights reserved.</p>
            </div>
        </footer>
    </main>
</template>
