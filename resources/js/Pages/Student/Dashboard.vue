<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
import { ArrowUpRight, BookOpen, ClipboardCheck, GraduationCap, ChartNoAxesCombined, FileText, CreditCard, UserRound, Sparkles } from 'lucide-vue-next';

const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const name = computed(() => page.props.auth?.user?.name || '');
const actions = computed(() => hi.value ? [
    { title: 'टेस्ट सीरीज़ देखें', detail: 'अपनी तैयारी के लिए सही पैकेज चुनें', href: '/student/test-series', icon: GraduationCap, tone: 'indigo' },
    { title: 'मेरी टेस्ट सीरीज़', detail: 'अपने नामांकन और उपलब्ध टेस्ट देखें', href: '/student/my-test-series', icon: ClipboardCheck, tone: 'violet' },
    { title: 'उपलब्ध परीक्षाएँ', detail: 'अपनी अगली परीक्षा शुरू करें', href: '/student/exams', icon: BookOpen, tone: 'sky' },
    { title: 'कोर्स कैटलॉग', detail: 'वीडियो और पाठ आधारित अध्ययन करें', href: '/student/courses', icon: GraduationCap, tone: 'emerald' },
    { title: 'मेरा प्रदर्शन', detail: 'अपने परीक्षा परिणाम और प्रगति देखें', href: '/student/performance', icon: ChartNoAxesCombined, tone: 'amber' },
    { title: 'अध्ययन सामग्री', detail: 'नोट्स और PDF संसाधन खोलें', href: '/student/materials', icon: FileText, tone: 'rose' },
    { title: 'भुगतान इतिहास', detail: 'अपने लेनदेन और रसीदें देखें', href: '/student/payments', icon: CreditCard, tone: 'slate' },
] : [
    { title: 'Explore test series', detail: 'Choose a free or paid package for your preparation', href: '/student/test-series', icon: GraduationCap, tone: 'indigo' },
    { title: 'My test series', detail: 'Continue with your enrolled tests and packages', href: '/student/my-test-series', icon: ClipboardCheck, tone: 'violet' },
    { title: 'Available exams', detail: 'Find and start your next assessment', href: '/student/exams', icon: BookOpen, tone: 'sky' },
    { title: 'Course catalogue', detail: 'Learn with structured lessons and resources', href: '/student/courses', icon: GraduationCap, tone: 'emerald' },
    { title: 'My performance', detail: 'Review exam results and learning progress', href: '/student/performance', icon: ChartNoAxesCombined, tone: 'amber' },
    { title: 'Study materials', detail: 'Open notes and downloadable PDFs', href: '/student/materials', icon: FileText, tone: 'rose' },
    { title: 'Payment history', detail: 'Review your transactions and receipts', href: '/student/payments', icon: CreditCard, tone: 'slate' },
]);
const toneClasses = {
    indigo: 'bg-indigo-50 text-indigo-700 ring-indigo-100 group-hover:bg-indigo-600',
    violet: 'bg-violet-50 text-violet-700 ring-violet-100 group-hover:bg-violet-600',
    sky: 'bg-sky-50 text-sky-700 ring-sky-100 group-hover:bg-sky-600',
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-100 group-hover:bg-emerald-600',
    amber: 'bg-amber-50 text-amber-700 ring-amber-100 group-hover:bg-amber-600',
    rose: 'bg-rose-50 text-rose-700 ring-rose-100 group-hover:bg-rose-600',
    slate: 'bg-slate-100 text-slate-700 ring-slate-200 group-hover:bg-slate-800',
};
</script>

<template>
    <PortalLayout portal="student" :title="hi ? 'विद्यार्थी डैशबोर्ड' : 'Student dashboard'" :subtitle="hi ? 'अपनी टेस्ट सीरीज़, कोर्स और परीक्षा की तैयारी एक ही जगह से जारी रखें।' : 'Your personal workspace for courses, test series, examinations and results.'">
        <section class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-indigo-700 to-violet-800 p-5 text-white shadow-xl shadow-indigo-900/10 sm:p-8">
            <div class="absolute -right-16 -top-24 -z-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-28 left-1/3 -z-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-[11px] font-bold tracking-[.13em] text-indigo-50"><Sparkles :size="14" />{{ hi ? 'आपका लर्निंग स्पेस' : 'YOUR LEARNING SPACE' }}</span>
            <h2 class="mt-5 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ hi ? 'नमस्ते' : 'Welcome' }}, {{ name }}!</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100 sm:text-base">{{ hi ? 'आज अपनी तैयारी के लिए एक छोटा कदम बढ़ाएँ। कोई टेस्ट चुनें या अपने कोर्स से सीखना जारी रखें।' : 'Take the next step in your preparation. Start an assessment or pick up where you left off in your courses.' }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="/student/exams" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-50">{{ hi ? 'परीक्षा शुरू करें' : 'Find an exam' }}<ArrowUpRight :size="16" /></a>
                <a href="/student/test-series" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">{{ hi ? 'टेस्ट सीरीज़ देखें' : 'Browse test series' }}</a>
            </div>
        </section>

        <section class="mt-7">
            <div class="mb-4"><p class="text-xs font-bold uppercase tracking-[.14em] text-indigo-600">{{ hi ? 'आपके शॉर्टकट' : 'YOUR SHORTCUTS' }}</p><h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">{{ hi ? 'सीखना जारी रखें' : 'Continue learning' }}</h2><p class="mt-1 text-sm text-slate-500">{{ hi ? 'अपने जरूरी सेक्शन जल्दी खोलें।' : 'Jump straight to the section you need.' }}</p></div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <a v-for="item in actions" :key="item.href" :href="item.href" class="group flex min-w-0 items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[.02] transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/[.05] sm:p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 transition group-hover:text-white" :class="toneClasses[item.tone]"><component :is="item.icon" :size="21" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-900 sm:text-base">{{ item.title }}</span><span class="mt-1 block text-sm leading-5 text-slate-500">{{ item.detail }}</span></span>
                    <ArrowUpRight :size="17" class="shrink-0 text-slate-300 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-indigo-600" />
                </a>
            </div>
        </section>

        <section v-if="page.props.auth?.user?.permissions?.includes('profile.manage')" class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700"><UserRound :size="19" /></span><span><strong class="block text-sm text-slate-900">{{ hi ? 'अपना खाता अपडेट रखें' : 'Keep your account up to date' }}</strong><span class="mt-0.5 block text-xs text-slate-500">{{ hi ? 'नाम, ईमेल और पासवर्ड सेटिंग्स देखें।' : 'Review your personal details and password settings.' }}</span></span></div>
            <a href="/student/profile" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">{{ hi ? 'प्रोफ़ाइल खोलें' : 'Open profile' }}<ArrowUpRight :size="15" /></a>
        </section>
    </PortalLayout>
</template>
