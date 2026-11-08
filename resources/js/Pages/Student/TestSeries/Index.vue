<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { ArrowUpRight, BookOpenCheck, CheckCircle2, CreditCard, Sparkles } from 'lucide-vue-next';

defineProps({ series: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
function enroll(id, paid = false) {
    const coupon = paid ? (window.prompt(hi.value ? 'कूपन कोड (वैकल्पिक):' : 'Coupon code (optional):') || '').trim() : '';
    if (confirm(paid ? (hi.value ? 'सुरक्षित भुगतान शुरू करें?' : 'Continue to secure payment?') : (hi.value ? 'इस टेस्ट सीरीज़ में शामिल हों?' : 'Enroll in this test series?'))) {
        router.post(paid ? ('/student/test-series/' + id + '/checkout') : ('/student/test-series/' + id + '/enroll'), coupon ? { coupon_code: coupon } : {});
    }
}
</script>

<template>
<PortalLayout portal="student" :title="hi ? 'टेस्ट सीरीज़' : 'Test series'" :subtitle="hi ? 'अपनी तैयारी के लिए फ्री और पेड टेस्ट पैकेज चुनें।' : 'Explore curated free and paid examination packages.'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4"><div><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ hi ? 'अपनी तैयारी चुनें' : 'CHOOSE YOUR PREPARATION' }}</p><p class="mt-1 text-sm text-slate-600">{{ hi ? 'नामांकन के बाद संबंधित परीक्षाएँ आपकी सूची में दिखेंगी।' : 'Enrolled packages will appear in your My Test Series section.' }}</p></div><span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ series.total }} {{ hi ? 'पैकेज' : 'packages' }}</span></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="s in series.data" :key="s.id" class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg">
            <div class="h-1.5" :class="s.access_type === 'paid' ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-gradient-to-r from-emerald-400 to-teal-500'"></div>
            <div class="flex flex-1 flex-col p-5">
                <div class="flex items-start justify-between gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><BookOpenCheck :size="20"/></span><span class="rounded-full px-3 py-1.5 text-xs font-extrabold" :class="s.access_type === 'paid' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'">{{ s.access_type === 'paid' ? s.currency + ' ' + s.price : (hi ? 'मुफ्त' : 'FREE') }}</span></div>
                <h2 class="mt-4 break-words text-lg font-extrabold leading-snug text-slate-900">{{ s.title }}</h2>
                <p class="mt-2 min-h-10 flex-1 text-sm leading-6 text-slate-500">{{ s.description || (hi ? 'अपनी परीक्षा की तैयारी को व्यवस्थित करें।' : 'A structured package to support your exam preparation.') }}</p>
                <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5"><span class="flex items-center gap-2 text-xs font-semibold text-slate-600"><BookOpenCheck :size="15"/>{{ s.exams_count }} {{ hi ? 'परीक्षाएँ' : 'exams included' }}</span><span v-if="s.enrollment?.status === 'active'" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700"><CheckCircle2 :size="14"/>{{ hi ? 'नामांकित' : 'Enrolled' }}</span><span v-else-if="s.enrollment?.status === 'pending_payment'" class="text-xs font-bold text-amber-700">{{ hi ? 'भुगतान लंबित' : 'Payment pending' }}</span><span v-else class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700"><Sparkles :size="13"/>{{ hi ? 'उपलब्ध' : 'Available' }}</span></div>
                <div class="mt-4 border-t border-slate-100 pt-4"><button v-if="s.enrollment?.status !== 'active'" @click="enroll(s.id, s.access_type === 'paid')" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">{{ s.access_type === 'paid' ? (hi ? 'भुगतान करें और जुड़ें' : 'Pay & enroll') : (hi ? 'मुफ्त में जुड़ें' : 'Enroll for free') }}<component :is="s.access_type === 'paid' ? CreditCard : ArrowUpRight" :size="16"/></button><a v-else href="/student/my-test-series" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 hover:bg-indigo-100">{{ hi ? 'मेरी सीरीज़ खोलें' : 'Open my series' }}<ArrowUpRight :size="16"/></a></div>
            </div>
        </article>
        <p v-if="!series.data.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">{{ hi ? 'अभी कोई टेस्ट सीरीज़ उपलब्ध नहीं है।' : 'No test series available right now.' }}</p>
    </div>
</PortalLayout>
</template>
