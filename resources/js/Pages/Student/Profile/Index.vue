<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { LockKeyhole, UserRound } from 'lucide-vue-next';

const props = defineProps({ user: Object, settings: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const canEditProfile = computed(() => props.settings.allow_profile_updates && (
    props.settings.allow_name_edit || props.settings.allow_email_edit || props.settings.allow_language_edit
));
const profile = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    preferred_locale: props.user.preferred_locale || 'en',
});
const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const success = computed(() => page.props.flash?.success || '');

function savePassword() {
    password.put('/student/profile/password', {
        onSuccess: () => password.reset(),
    });
}
</script>

<template>
    <PortalLayout
        portal="student"
        :title="hi ? 'मेरी प्रोफ़ाइल' : 'My Profile'"
        :subtitle="hi ? 'अपनी व्यक्तिगत जानकारी, भाषा और पासवर्ड प्रबंधित करें।' : 'Manage your personal details, preferred language and account password.'"
    >
        <div class="mx-auto max-w-4xl space-y-5">
            <div v-if="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ success }}
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 p-5">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><UserRound :size="21" /></div>
                    <div>
                        <h2 class="font-bold text-slate-900">{{ hi ? 'व्यक्तिगत जानकारी' : 'Personal information' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ hi ? 'आपकी प्रोफ़ाइल में दिखने वाली जानकारी।' : 'Information associated with your student account.' }}</p>
                    </div>
                </div>

                <div v-if="!settings.allow_profile_updates" class="m-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    {{ hi ? 'प्रशासक ने प्रोफ़ाइल अपडेट अस्थायी रूप से बंद किए हैं।' : 'Profile updates are currently disabled by the administrator.' }}
                </div>

                <form class="space-y-4 p-5" @submit.prevent="profile.put('/student/profile')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-slate-700">
                            {{ hi ? 'पूरा नाम' : 'Full name' }}
                            <input v-model="profile.name" :disabled="!settings.allow_profile_updates || !settings.allow_name_edit" required maxlength="120" autocomplete="name" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal disabled:bg-slate-50 disabled:text-slate-500">
                            <span v-if="profile.errors.name" class="mt-1 block text-xs text-rose-600">{{ profile.errors.name }}</span>
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">
                            {{ hi ? 'ईमेल पता' : 'Email address' }}
                            <input v-model="profile.email" :disabled="!settings.allow_profile_updates || !settings.allow_email_edit" type="email" required maxlength="255" autocomplete="email" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal disabled:bg-slate-50 disabled:text-slate-500">
                            <span v-if="profile.errors.email" class="mt-1 block text-xs text-rose-600">{{ profile.errors.email }}</span>
                        </label>
                    </div>

                    <label class="block max-w-sm text-sm font-semibold text-slate-700">
                        {{ hi ? 'पसंदीदा भाषा' : 'Preferred language' }}
                        <select v-model="profile.preferred_locale" :disabled="!settings.allow_profile_updates || !settings.allow_language_edit" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal disabled:bg-slate-50 disabled:text-slate-500">
                            <option value="en">English</option>
                            <option value="hi">हिंदी</option>
                        </select>
                        <span v-if="profile.errors.preferred_locale" class="mt-1 block text-xs text-rose-600">{{ profile.errors.preferred_locale }}</span>
                    </label>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <p class="text-xs text-slate-500">{{ hi ? 'केवल वे फ़ील्ड अपडेट होंगे जिन्हें प्रशासक ने अनुमति दी है।' : 'Only fields enabled by the administrator can be changed.' }}</p>
                        <button v-if="canEditProfile" type="submit" :disabled="profile.processing" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                            {{ profile.processing ? (hi ? 'सेव हो रहा है...' : 'Saving...') : (hi ? 'प्रोफ़ाइल सेव करें' : 'Save profile') }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="settings.allow_password_change" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 p-5">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700"><LockKeyhole :size="21" /></div>
                    <div>
                        <h2 class="font-bold text-slate-900">{{ hi ? 'पासवर्ड बदलें' : 'Change password' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ hi ? 'अपना वर्तमान पासवर्ड सत्यापित करके नया पासवर्ड सेट करें।' : 'Verify your current password before setting a new one.' }}</p>
                    </div>
                </div>
                <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="savePassword">
                    <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">
                        {{ hi ? 'वर्तमान पासवर्ड' : 'Current password' }}
                        <input v-model="password.current_password" type="password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal">
                        <span v-if="password.errors.current_password" class="mt-1 block text-xs text-rose-600">{{ password.errors.current_password }}</span>
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        {{ hi ? 'नया पासवर्ड' : 'New password' }}
                        <input v-model="password.password" type="password" required minlength="12" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal">
                        <span v-if="password.errors.password" class="mt-1 block text-xs text-rose-600">{{ password.errors.password }}</span>
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        {{ hi ? 'नया पासवर्ड दोबारा लिखें' : 'Confirm new password' }}
                        <input v-model="password.password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal">
                    </label>
                    <p class="text-xs leading-5 text-slate-500 sm:col-span-2">{{ hi ? 'कम से कम 12 अक्षर; बड़े और छोटे अक्षर, संख्या और प्रतीक शामिल करें।' : 'Use at least 12 characters with uppercase and lowercase letters, a number and a symbol.' }}</p>
                    <div class="sm:col-span-2">
                        <button type="submit" :disabled="password.processing" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-60">
                            {{ password.processing ? (hi ? 'अपडेट हो रहा है...' : 'Updating...') : (hi ? 'पासवर्ड अपडेट करें' : 'Update password') }}
                        </button>
                    </div>
                </form>
            </section>
            <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                {{ hi ? 'पासवर्ड बदलने का विकल्प प्रशासक द्वारा बंद किया गया है।' : 'Password changes are disabled by the administrator.' }}
            </div>
        </div>
    </PortalLayout>
</template>
