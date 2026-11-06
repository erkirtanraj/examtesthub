<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({ notifications: Object, unreadCount: Number, preferences: Object });
const form = useForm({ ...props.preferences });
const isHindi = computed(() => document.documentElement.lang === 'hi');
function markRead(item) {
    if (!item.read_at) router.patch('/student/notifications/' + item.id + '/read', {}, { preserveScroll: true });
}
function markAll() { router.post('/student/notifications/read-all', {}, { preserveScroll: true }); }
function savePreferences() { form.put('/student/notification-preferences', { preserveScroll: true }); }
</script>

<template>
    <Head :title="isHindi ? 'सूचनाएँ' : 'Notifications'" />
    <PortalLayout portal="student" :title="isHindi ? 'सूचना केंद्र' : 'Notification Center'" :subtitle="isHindi ? 'महत्वपूर्ण अपडेट और घोषणाएँ देखें।' : 'View important updates, announcements and learning alerts.'">
        <div class="space-y-6">
            <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                <div><p class="text-sm font-bold text-indigo-950">{{ unreadCount }} {{ isHindi ? 'अपठित सूचनाएँ' : 'unread notifications' }}</p><p class="mt-1 text-xs text-indigo-700">{{ isHindi ? 'अपडेट से जुड़े रहें।' : 'Stay up to date with your learning.' }}</p></div>
                <button v-if="unreadCount" @click="markAll" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">{{ isHindi ? 'सभी पढ़ा हुआ करें' : 'Mark all as read' }}</button>
            </section>
            <section class="space-y-3">
                <article v-for="item in notifications.data" :key="item.id" class="rounded-2xl border p-4 transition" :class="item.read_at ? 'border-slate-200 bg-white' : 'border-indigo-200 bg-white shadow-sm'">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="item.read_at ? 'bg-slate-300' : 'bg-indigo-600'"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold text-slate-900">{{ item.title }}</h3><time class="text-xs text-slate-400">{{ item.created_at ? new Date(item.created_at).toLocaleString() : '' }}</time></div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ item.message }}</p>
                            <div class="mt-3 flex gap-3"><a :href="item.url" @click="markRead(item)" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">{{ isHindi ? 'खोलें' : 'Open' }}</a><button v-if="!item.read_at" @click="markRead(item)" class="text-sm font-semibold text-slate-500 hover:text-slate-800">{{ isHindi ? 'पढ़ा हुआ करें' : 'Mark as read' }}</button></div>
                        </div>
                    </div>
                </article>
                <div v-if="!notifications.data.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="font-bold text-slate-700">{{ isHindi ? 'अभी कोई सूचना नहीं है' : 'You are all caught up' }}</p><p class="mt-1 text-sm text-slate-500">{{ isHindi ? 'नई सूचनाएँ यहाँ दिखाई देंगी।' : 'New notifications will appear here.' }}</p></div>
                <div v-if="notifications.links" class="flex flex-wrap gap-2"><a v-for="link in notifications.links" :key="link.label" :href="link.url || '#'" class="rounded-lg border px-3 py-2 text-sm" :class="link.active ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600'" v-html="link.label"></a></div>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-900">{{ isHindi ? 'सूचना प्राथमिकताएँ' : 'Notification preferences' }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ isHindi ? 'चुनें कि कौन से अपडेट प्राप्त करना चाहते हैं।' : 'Choose which updates you want to receive.' }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label v-for="field in [{key:'email',label:'Email notifications'},{key:'announcements',label:'Announcements'},{key:'exam_results',label:'Exam results'},{key:'course_updates',label:'Course updates'}]" :key="field.key" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm font-medium text-slate-700"><input v-model="form[field.key]" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"/>{{ field.label }}</label>
                </div>
                <button @click="savePreferences" :disabled="form.processing" class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">{{ isHindi ? 'सेटिंग सहेजें' : 'Save preferences' }}</button>
            </section>
        </div>
    </PortalLayout>
</template>
