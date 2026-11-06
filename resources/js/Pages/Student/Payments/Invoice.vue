<script setup>
import PortalLayout from '../../../Layouts/PortalLayout.vue';
defineProps({payment:Object});
</script>
<template>
<PortalLayout portal="student" title="Payment receipt" subtitle="Your ExamTestHub payment record">
    <div class="mx-auto max-w-3xl rounded-2xl border bg-white p-6 shadow-sm sm:p-10 print:max-w-none print:border-0 print:p-0 print:shadow-none">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-6">
            <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">ExamTestHub</p><h1 class="mt-2 text-2xl font-black text-slate-900">Payment receipt</h1><p class="mt-1 text-sm text-slate-500">Receipt #{{payment.receipt}}</p></div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase text-emerald-700">{{payment.status.replace('_',' ')}}</span>
        </div>
        <div class="grid gap-6 py-7 sm:grid-cols-2">
            <div><p class="text-xs font-semibold uppercase text-slate-400">Billed to</p><p class="mt-2 font-bold text-slate-900">{{payment.student}}</p><p class="text-sm text-slate-500">{{payment.email}}</p></div>
            <div><p class="text-xs font-semibold uppercase text-slate-400">Payment details</p><p class="mt-2 text-sm text-slate-700">Date: {{payment.paid_at?new Date(payment.paid_at).toLocaleString():'—'}}</p><p class="text-sm text-slate-700">Payment ID: {{payment.payment_id||'—'}}</p></div>
        </div>
        <div class="rounded-xl border">
            <div class="flex justify-between gap-4 p-4"><span class="font-semibold text-slate-700">{{payment.item}}</span><span class="font-bold">{{payment.currency}} {{Number(payment.amount).toFixed(2)}}</span></div>
            <div class="flex justify-between border-t bg-slate-50 p-4 text-lg font-black"><span>Total paid</span><span>{{payment.currency}} {{Number(payment.amount).toFixed(2)}}</span></div>
        </div>
        <p v-if="payment.refunded_at" class="mt-4 text-sm font-semibold text-rose-700">Refund completed: {{new Date(payment.refunded_at).toLocaleString()}}</p>
        <p class="mt-7 text-xs leading-5 text-slate-500">This is a payment receipt generated from the ExamTestHub payment record. It is not a GST tax invoice. For tax invoices or billing corrections, contact the platform administrator.</p>
        <div class="mt-8 flex gap-3 print:hidden"><button @click="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Print / Save as PDF</button><a href="/student/payments" class="rounded-lg border px-4 py-2.5 text-sm font-bold text-slate-700">Back to payments</a></div>
    </div>
</PortalLayout>
</template>
