<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const cards = computed(() => hi.value ? [
    { label: 'उपयोगकर्ता प्रबंधन', detail: 'विद्यार्थी और स्टाफ खाते', status: 'उपलब्ध', href: '/admin/users', permission: 'users.view' },
    { label: 'भूमिका प्रबंधन', detail: 'भूमिकाएं और अनुमतियां', status: 'उपलब्ध', href: '/admin/roles', permission: 'roles.manage' },
    { label: 'गतिविधि लॉग', detail: 'प्रशासनिक बदलावों का रिकॉर्ड', status: 'उपलब्ध', href: '/admin/activity', permission: 'audit.view' },
    { label: 'मेरी प्रोफ़ाइल', detail: 'प्रोफ़ाइल और पासवर्ड सुरक्षा', status: 'उपलब्ध', href: '/admin/profile', permission: 'profile.manage' },
] : [
    { label: 'User management', detail: 'Student and staff accounts', status: 'Available', href: '/admin/users', permission: 'users.view' },
    { label: 'Role management', detail: 'Roles and granular permissions', status: 'Available', href: '/admin/roles', permission: 'roles.manage' },
    { label: 'Audit activity', detail: 'Administrative change history', status: 'Available', href: '/admin/activity', permission: 'audit.view' },
    { label: 'My profile', detail: 'Profile and password security', status: 'Available', href: '/admin/profile', permission: 'profile.manage' },
]);
</script>
<template>
    <PortalLayout portal="admin" :title="hi ? 'एडमिन डैशबोर्ड' : 'Admin dashboard'" :subtitle="hi ? 'ExamTestHub संचालन और प्रबंधन का केंद्रीय स्थान।' : 'Your central workspace for ExamTestHub operations and management.'">
        <div class="rounded-2xl bg-slate-900 p-6 text-white">
            <p class="text-sm font-semibold text-indigo-200">{{ hi ? 'सुरक्षित प्रशासनिक क्षेत्र' : 'SECURE ADMINISTRATOR WORKSPACE' }}</p>
            <h2 class="mt-2 text-2xl font-extrabold">{{ hi ? 'स्वागत है' : 'Welcome to ExamTestHub' }}, {{ page.props.auth?.user?.name }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ hi ? 'आपकी भूमिका और अनुमतियों के अनुसार प्रशासनिक सुविधाएँ उपलब्ध होंगी।' : 'Your available administration capabilities are controlled by your assigned role and permissions.' }}</p>
        </div>
        <div id="operations" class="mt-6 grid gap-4 sm:grid-cols-2">
            <a v-for="card in cards.filter(item => page.props.auth?.user?.permissions?.includes(item.permission))" :key="card.label" :href="card.href || '/admin/dashboard'" class="block rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-300 hover:shadow-sm">
                <p class="text-lg font-bold text-slate-900">{{ card.label }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ card.detail }}</p>
                <span class="mt-5 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ card.status }}</span>
            </a>
        </div>
    </PortalLayout>
</template>
