<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ payments: Object, summary: Object, filters: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const days = ref(String(props.filters.days || 30));
const reasons = ref({});
const busyId = ref(null);
function applyFilters() {
    router.get('/admin/payments', { search: search.value || undefined, status: status.value || undefined, days: days.value }, { preserveState: true, replace: true });
}
function refund(payment) {
    const reason = (reasons.value[payment.id] || '').trim();
    if (reason.length < 5) return;
    if (!window.confirm(hi.value ? 'इस भुगतान का पूरा रिफंड शुरू करें?' : 'Start a full refund for this payment?')) return;
    busyId.value = payment.id;
    router.post('/admin/payments/' + payment.id + '/refund', { reason }, { preserveScroll: true, onFinish: () => { busyId.value = null; } });
}
const money = value => '₹' + (Number(value || 0) / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
<PortalLayout portal="admin" :title="hi?'भुगतान और राजस्व':'Payments & revenue'" :subtitle="hi?'भुगतान, रिफंड और शुद्ध राजस्व प्रबंधित करें।':'Review collections, refunds and net revenue.'">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{hi?'सकल संग्रह':'Gross collections'}}</p><p class="mt-2 text-2xl font-black">{{money(summary.gross_revenue_paise)}}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{hi?'रिफंड':'Refunded'}}</p><p class="mt-2 text-2xl font-black text-rose-600">{{money(summary.refunded_paise)}}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{hi?'शुद्ध राजस्व':'Net revenue'}}</p><p class="mt-2 text-2xl font-black text-emerald-700">{{money(summary.net_revenue_paise)}}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{hi?'विफल / लंबित रिफंड':'Failed / pending refunds'}}</p><p class="mt-2 text-2xl font-black">{{summary.failed_count}} / {{summary.pending_refunds}}</p></div>
    </div>
    <form @submit.prevent="applyFilters" class="mt-5 flex flex-wrap items-end gap-3 rounded-xl border bg-white p-4">
        <label class="min-w-52 flex-1 text-xs font-semibold text-slate-600">{{hi?'नाम / ईमेल / भुगतान आईडी':'Name / email / payment ID'}}<input v-model="search" class="mt-1 w-full rounded-lg border-slate-300 text-sm" /></label>
        <label class="text-xs font-semibold text-slate-600">{{hi?'स्थिति':'Status'}}<select v-model="status" class="mt-1 block rounded-lg border-slate-300 text-sm"><option value="">{{hi?'सभी':'All statuses'}}</option><option v-for="s in ['pending','captured','failed','refund_pending','refunded']" :key="s" :value="s">{{s.replace('_',' ')}}</option></select></label>
        <label class="text-xs font-semibold text-slate-600">{{hi?'अवधि':'Period'}}<select v-model="days" class="mt-1 block rounded-lg border-slate-300 text-sm"><option value="7">7 days</option><option value="30">30 days</option><option value="90">90 days</option><option value="365">365 days</option></select></label>
        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">{{hi?'फ़िल्टर लागू करें':'Apply filters'}}</button>
    </form>
    <div class="mt-5 overflow-x-auto rounded-xl border bg-white">
        <table class="w-full min-w-[980px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Student</th><th class="p-3">Course / test series</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Provider reference</th><th class="p-3">Date</th><th class="p-3">Refund action</th></tr></thead>
            <tbody>
                <tr v-for="p in payments.data" :key="p.id" class="border-t align-top">
                    <td class="p-3"><b>{{p.user?.name}}</b><p class="text-xs text-slate-500">{{p.user?.email}}</p></td>
                    <td class="p-3">{{p.course_enrollment?.course?.title||p.enrollment?.series?.title}}</td>
                    <td class="p-3 font-semibold">{{p.currency}} {{(p.amount_paise/100).toFixed(2)}}</td>
                    <td class="p-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold capitalize">{{p.status.replace('_',' ')}}</span><p v-if="p.refund_reason" class="mt-1 max-w-48 text-xs text-slate-500">{{p.refund_reason}}</p></td>
                    <td class="p-3 text-xs">{{p.provider_refund_id||p.provider_payment_id||p.provider_order_id||'—'}}</td>
                    <td class="p-3 whitespace-nowrap">{{new Date(p.created_at).toLocaleString()}}</td>
                    <td class="p-3">
                        <form v-if="p.status==='captured'" @submit.prevent="refund(p)" class="flex min-w-56 gap-2">
                            <input v-model="reasons[p.id]" required minlength="5" maxlength="500" class="min-w-0 flex-1 rounded-lg border-slate-300 text-xs" :placeholder="hi?'रिफंड का कारण':'Refund reason'" />
                            <button :disabled="busyId===p.id" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white disabled:opacity-50">{{busyId===p.id?'…':(hi?'रिफंड':'Refund')}}</button>
                        </form>
                        <span v-else class="text-xs text-slate-400">—</span>
                    </td>
                </tr>
                <tr v-if="!payments.data.length"><td colspan="7" class="p-8 text-center text-slate-500">{{hi?'कोई भुगतान नहीं मिला।':'No payment records found.'}}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap gap-2"><a v-for="link in payments.links" :key="link.label" :href="link.url||'#'" @click.prevent="link.url && router.visit(link.url)" class="rounded-lg border px-3 py-2 text-sm" :class="link.active?'border-indigo-600 bg-indigo-50 text-indigo-700':'bg-white text-slate-600'" v-html="link.label" /></div>
</PortalLayout>
</template>
