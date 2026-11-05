<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Settings2, UserRound, LockKeyhole } from 'lucide-vue-next';

const props = defineProps({ settings: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const settings = useForm({
    allow_profile_updates: !!props.settings.allow_profile_updates,
    allow_name_edit: !!props.settings.allow_name_edit,
    allow_email_edit: !!props.settings.allow_email_edit,
    allow_language_edit: !!props.settings.allow_language_edit,
    allow_password_change: !!props.settings.allow_password_change,
});
const success = computed(() => page.props.flash?.success || '');
</script>

<template>
    <PortalLayout
        portal="admin"
        :title="hi ? 'विद्यार्थी प्रोफ़ाइल सेटिंग्स' : 'Student Profile Settings'"
        :subtitle="hi ? 'नियंत्रित करें कि विद्यार्थी अपनी प्रोफ़ाइल में कौन-सी जानकारी बदल सकते हैं।' : 'Control which student account details can be updated from the student portal.'"
    >
        <div class="mx-auto max-w-3xl space-y-5">
            <div v-if="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ success }}</div>
            <form @submit.prevent="settings.put('/admin/settings/student-profile')" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 p-5">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><Settings2 :size="21" /></div>
                    <div>
                        <h2 class="font-bold text-slate-900">{{ hi ? 'प्रोफ़ाइल अपडेट नियंत्रण' : 'Profile update controls' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ hi ? 'ये सेटिंग्स सभी विद्यार्थी खातों पर लागू होती हैं।' : 'These controls apply to every student account.' }}</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                        <span><span class="block text-sm font-bold text-slate-800">{{ hi ? 'विद्यार्थी प्रोफ़ाइल अपडेट सक्षम करें' : 'Allow student profile updates' }}</span><span class="mt-1 block text-xs leading-5 text-slate-500">{{ hi ? 'बंद करने पर विद्यार्थी व्यक्तिगत जानकारी सेव नहीं कर पाएँगे।' : 'When disabled, students can view their profile but cannot save personal details.' }}</span></span>
                        <input v-model="settings.allow_profile_updates" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                    <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                        <span><span class="block text-sm font-bold text-slate-800">{{ hi ? 'नाम बदलने की अनुमति' : 'Allow name changes' }}</span><span class="mt-1 block text-xs text-slate-500">{{ hi ? 'विद्यार्थी अपना प्रदर्शित नाम बदल सकते हैं।' : 'Students can edit the name displayed in the portal.' }}</span></span>
                        <input v-model="settings.allow_name_edit" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                    <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                        <span><span class="block text-sm font-bold text-slate-800">{{ hi ? 'ईमेल बदलने की अनुमति' : 'Allow email changes' }}</span><span class="mt-1 block text-xs text-slate-500">{{ hi ? 'नया ईमेल अद्वितीय और मान्य होना चाहिए।' : 'The new email address must be valid and unique.' }}</span></span>
                        <input v-model="settings.allow_email_edit" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                    <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                        <span><span class="block text-sm font-bold text-slate-800">{{ hi ? 'भाषा बदलने की अनुमति' : 'Allow preferred language changes' }}</span><span class="mt-1 block text-xs text-slate-500">{{ hi ? 'विद्यार्थी अंग्रेज़ी और हिंदी के बीच चयन कर सकते हैं।' : 'Students can choose English or Hindi as their portal language.' }}</span></span>
                        <input v-model="settings.allow_language_edit" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                    <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                        <span><span class="block text-sm font-bold text-slate-800">{{ hi ? 'पासवर्ड बदलने की अनुमति' : 'Allow password changes' }}</span><span class="mt-1 block text-xs text-slate-500">{{ hi ? 'विद्यार्थी वर्तमान पासवर्ड सत्यापित करके अपना पासवर्ड बदल सकते हैं।' : 'Students can change their password after verifying the current password.' }}</span></span>
                        <input v-model="settings.allow_password_change" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 p-5">
                    <p class="text-xs text-slate-500">{{ hi ? 'परिवर्तन सभी विद्यार्थी खातों पर तुरंत लागू होंगे।' : 'Changes apply to all student accounts immediately.' }}</p>
                    <button type="submit" :disabled="settings.processing" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-60">
                        {{ settings.processing ? (hi ? 'सेव हो रहा है...' : 'Saving...') : (hi ? 'सेटिंग्स सेव करें' : 'Save settings') }}
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
