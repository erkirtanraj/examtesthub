<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({ studentCount: Number });
const form = useForm({ title: '', message: '', audience: 'all', student_ids: '', send_email: false });
const isHindi = computed(() => document.documentElement.lang === 'hi');
function submit() {
    const ids = form.student_ids.split(',').map(value => Number(value.trim())).filter(value => Number.isInteger(value) && value > 0);
    form.transform(data => ({ ...data, student_ids: data.audience === 'selected' ? ids : [] })).post('/admin/announcements', { preserveScroll: true });
}
</script>

<template>
    <Head :title="isHindi ? 'घोषणाएँ' : 'Announcements'" />
    <PortalLayout portal="admin" :title="isHindi ? 'विद्यार्थी घोषणाएँ' : 'Student Announcements'" :subtitle="isHindi ? 'विद्यार्थियों को महत्वपूर्ण संदेश भेजें।' : 'Compose and deliver announcements to active student accounts.'">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div><label class="mb-2 block text-sm font-bold text-slate-700">{{ isHindi ? 'शीर्षक' : 'Announcement title' }}</label><input v-model="form.title" maxlength="160" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"/><p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">{{ isHindi ? 'संदेश' : 'Message' }}</label><textarea v-model="form.message" rows="7" maxlength="5000" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea><p v-if="form.errors.message" class="mt-1 text-xs text-rose-600">{{ form.errors.message }}</p></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">{{ isHindi ? 'प्राप्तकर्ता' : 'Recipients' }}</label><select v-model="form.audience" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="all">All active students</option><option value="selected">Selected student IDs</option></select></div>
                <div v-if="form.audience === 'selected'"><label class="mb-2 block text-sm font-bold text-slate-700">Student IDs (comma separated)</label><input v-model="form.student_ids" placeholder="12, 18, 25" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"/><p v-if="form.errors.student_ids" class="mt-1 text-xs text-rose-600">{{ form.errors.student_ids }}</p></div>
                <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-4"><input v-model="form.send_email" type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600"/><span><span class="block text-sm font-bold text-slate-800">Also send email</span><span class="mt-1 block text-xs text-slate-500">Queues email delivery for this announcement. Your Laravel mail and queue workers must be configured.</span></span></label>
                <button :disabled="form.processing" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-50">{{ form.processing ? 'Sending…' : 'Send announcement' }}</button>
            </form>
            <aside class="h-fit rounded-2xl border border-indigo-100 bg-indigo-50 p-5"><p class="text-xs font-bold uppercase tracking-wider text-indigo-700">Audience</p><p class="mt-2 text-3xl font-black text-indigo-950">{{ studentCount.toLocaleString() }}</p><p class="mt-1 text-sm text-indigo-800">Active student accounts</p><p class="mt-4 text-xs leading-5 text-indigo-700">Students can manage announcement and email preferences from their Notification Center. Email delivery requires a configured mail transport and running queue worker.</p></aside>
        </div>
    </PortalLayout>
</template>
