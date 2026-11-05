<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const locale = computed(() => usePage().props.locale || 'en');
const hi = computed(() => locale.value === 'hi');
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', preferred_locale: locale.value });
function submit() { form.post('/register'); }
</script>
<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-5 py-12">
        <form @submit.prevent="submit" class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 shadow-xl sm:p-10">
            <a href="/" class="text-xl font-extrabold text-slate-900">ExamTest<span class="text-indigo-600">Hub</span></a>
            <h1 class="mt-8 text-3xl font-extrabold text-slate-900">{{ hi ? 'विद्यार्थी खाता बनाएँ' : 'Create your student account' }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ hi ? 'अपनी परीक्षा की तैयारी शुरू करें।' : 'Join ExamTestHub and start preparing.' }}</p>
            <label class="mt-6 block text-sm font-semibold">{{ hi ? 'पूरा नाम' : 'Full name' }}</label>
            <input v-model="form.name" required maxlength="120" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            <label class="mt-4 block text-sm font-semibold">Email</label>
            <input v-model="form.email" type="email" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            <label class="mt-4 block text-sm font-semibold">{{ hi ? 'पासवर्ड (कम से कम 12 अक्षर)' : 'Password (minimum 12 characters)' }}</label>
            <input v-model="form.password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            <label class="mt-4 block text-sm font-semibold">{{ hi ? 'पासवर्ड दोबारा लिखें' : 'Confirm password' }}</label>
            <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
            <label class="mt-4 block text-sm font-semibold">{{ hi ? 'भाषा' : 'Preferred language' }}</label>
            <select v-model="form.preferred_locale" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3"><option value="en">English</option><option value="hi">हिन्दी</option></select>
            <button :disabled="form.processing" class="mt-7 w-full rounded-xl bg-indigo-600 px-5 py-3.5 font-bold text-white hover:bg-indigo-700 disabled:opacity-60">{{ hi ? 'खाता बनाएँ' : 'Create account' }}</button>
            <p class="mt-6 text-center text-sm text-slate-500">{{ hi ? 'पहले से खाता है?' : 'Already registered?' }} <a href="/student/login" class="font-bold text-indigo-600">{{ hi ? 'लॉगिन करें' : 'Sign in' }}</a></p>
        </form>
    </main>
</template>
