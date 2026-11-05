<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const locale = computed(() => usePage().props.locale || 'en');
const hi = computed(() => locale.value === 'hi');
const form = useForm({ email: '', password: '', remember: false });
function submit() { form.post('/student/login'); }
</script>
<template>
    <main class="grid min-h-screen bg-indigo-700 lg:grid-cols-2">
        <section class="flex flex-col justify-between p-8 text-white sm:p-12">
            <a href="/" class="text-2xl font-extrabold">ExamTest<span class="text-indigo-200">Hub</span></a>
            <div class="max-w-xl py-16">
                <p class="text-sm font-bold uppercase tracking-[0.25em] text-indigo-200">STUDENT LEARNING PORTAL</p>
                <h1 class="mt-5 text-4xl font-extrabold leading-tight sm:text-5xl">{{ hi ? 'अपनी तैयारी को अगले स्तर पर ले जाएँ।' : 'Your learning journey, organized in one place.' }}</h1>
                <p class="mt-5 text-lg leading-8 text-indigo-100">{{ hi ? 'कोर्स सीखें, टेस्ट दें और अपनी प्रगति देखें।' : 'Access your courses, practice tests and learning progress.' }}</p>
            </div>
            <p class="text-sm text-indigo-200">© {{ new Date().getFullYear() }} ExamTestHub</p>
        </section>
        <section class="flex items-center justify-center bg-slate-50 px-5 py-12">
            <form @submit.prevent="submit" class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
                <p class="text-sm font-bold text-indigo-600">{{ hi ? 'विद्यार्थी पोर्टल' : 'Student portal' }}</p>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ hi ? 'स्वागत है' : 'Welcome back' }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ hi ? 'अपने खाते में लॉगिन करें।' : 'Sign in to continue your preparation.' }}</p>
                <label class="mt-7 block text-sm font-semibold text-slate-700">{{ hi ? 'ईमेल' : 'Email address' }}</label>
                <input v-model="form.email" type="email" autocomplete="username" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                <label class="mt-5 block text-sm font-semibold text-slate-700">{{ hi ? 'पासवर्ड' : 'Password' }}</label>
                <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                <label class="mt-4 flex items-center gap-2 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="rounded border-slate-300"> {{ hi ? 'मुझे याद रखें' : 'Remember me' }}</label>
                <button :disabled="form.processing" class="mt-7 w-full rounded-xl bg-indigo-600 px-5 py-3.5 font-bold text-white hover:bg-indigo-700 disabled:opacity-60">{{ form.processing ? '…' : (hi ? 'लॉगिन करें' : 'Sign in') }}</button>
                <p class="mt-6 text-center text-sm text-slate-500">{{ hi ? 'नया खाता?' : 'New to ExamTestHub?' }} <a href="/register" class="font-bold text-indigo-600 hover:text-indigo-800">{{ hi ? 'रजिस्टर करें' : 'Create an account' }}</a></p>
                <a href="/admin/login" class="mt-4 block text-center text-xs font-semibold text-slate-400 hover:text-indigo-600">{{ hi ? 'एडमिन लॉगिन' : 'Administrator sign in' }}</a>
            </form>
        </section>
    </main>
</template>
