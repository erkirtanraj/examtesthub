 <script setup>
import { computed } from 'vue';
import { router,usePage } from '@inertiajs/vue3';
defineProps({portal:{type:String,required:true},title:{type:String,required:true},subtitle:{type:String,required:true}});
const page=usePage();const locale=computed(()=>page.props.locale||'en');const user=computed(()=>page.props.auth?.user||null);const isHindi=computed(()=>locale.value==='hi');
function logout(){router.post('/logout')}function switchLanguage(next){router.get('/language/'+next)}
</script>
<template>
<div class="min-h-screen bg-slate-50"><header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
<a href="/" class="text-xl font-extrabold tracking-tight text-slate-900">ExamTest<span class="text-indigo-600">Hub</span></a><div class="flex items-center gap-3"><span class="hidden text-sm text-slate-600 sm:inline">{{user?.name}}</span>
<button @click="switchLanguage('en')" class="rounded-lg px-2 py-1 text-sm" :class="!isHindi?'bg-indigo-100 font-bold text-indigo-700':'text-slate-500'">EN</button><button @click="switchLanguage('hi')" class="rounded-lg px-2 py-1 text-sm" :class="isHindi?'bg-indigo-100 font-bold text-indigo-700':'text-slate-500'">हिंदी</button>
<button @click="logout" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">{{isHindi?'लॉग आउट':'Log out'}}</button></div></div></header>
<div class="mx-auto grid max-w-7xl gap-6 px-5 py-8 lg:grid-cols-[240px_1fr]"><aside class="rounded-2xl border border-slate-200 bg-white p-4"><p class="px-3 pb-3 text-xs font-bold uppercase tracking-widest text-slate-400">{{portal==='admin'?(isHindi?'प्रशासन':'Administration'):(isHindi?'विद्यार्थी पोर्टल':'Student portal')}}</p><nav class="space-y-1">
<a :href="portal==='admin'?'/admin/dashboard':'/student/dashboard'" class="block rounded-xl bg-indigo-50 px-3 py-2.5 text-sm font-bold text-indigo-700">{{isHindi?'डैशबोर्ड':'Dashboard'}}</a>
<a v-if="portal==='student'" href="/student/test-series" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-50">{{isHindi?'टेस्ट सीरीज़':'Test series'}}</a>
<a v-if="portal==='student'" href="/student/my-test-series" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-50">{{isHindi?'मेरी सीरीज़':'My test series'}}</a>
<a v-if="portal==='student'" href="/student/payments" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-50">{{isHindi?'भुगतान इतिहास':'Payment history'}}</a>
<a v-if="portal==='student'" href="/student/exams" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-50">{{isHindi?'उपलब्ध परीक्षाएँ':'Available exams'}}</a>
<template v-if="portal==='admin'">
<a v-if="user?.permissions?.includes('users.view')" href="/admin/users" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'उपयोगकर्ता':'Users'}}</a>
<a v-if="user?.permissions?.includes('roles.manage')" href="/admin/roles" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'भूमिकाएँ':'Roles'}}</a>
<a v-if="user?.permissions?.includes('permissions.manage')" href="/admin/permissions" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'अनुमतियाँ':'Permissions'}}</a>
<a v-if="user?.permissions?.includes('questions.manage')" href="/admin/questions" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'प्रश्न बैंक':'Question bank'}}</a>
<a v-if="user?.permissions?.includes('questions.manage')" href="/admin/subjects" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'विषय और टॉपिक':'Subjects & topics'}}</a>
<a v-if="user?.permissions?.includes('tests.manage')" href="/admin/test-series" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'टेस्ट सीरीज़':'Test series'}}</a>
<a v-if="user?.permissions?.includes('tests.manage')" href="/admin/payments" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'भुगतान प्रबंधन':'Payments'}}</a>
<a v-if="user?.permissions?.includes('tests.manage')" href="/admin/exams" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'परीक्षा प्रबंधन':'Exam management'}}</a>
<a v-if="user?.permissions?.includes('audit.view')" href="/admin/activity" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'गतिविधि लॉग':'Audit activity'}}</a>
<a v-if="user?.permissions?.includes('profile.manage')" href="/admin/profile" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600">{{isHindi?'मेरी प्रोफ़ाइल':'My profile'}}</a>
</template></nav></aside><main><div class="mb-6"><p class="text-sm font-semibold text-indigo-600">{{portal==='admin'?'ExamTestHub Admin':'ExamTestHub Learning'}}</p><h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">{{title}}</h1><p class="mt-2 text-slate-500">{{subtitle}}</p></div><slot/></main></div></div>
</template>