<script setup>
import { computed } from 'vue';import {router,usePage} from '@inertiajs/vue3';import PortalLayout from '../../../Layouts/PortalLayout.vue';
defineProps({exams:Object});const page=usePage();const hi=computed(()=>page.props.locale==='hi');
function start(id){if(confirm(hi.value?'परीक्षा शुरू करें? टाइमर तुरंत शुरू होगा।':'Start this exam? The timer starts immediately.'))router.post('/student/exams/'+id+'/start')}
</script>
<template><PortalLayout portal="student" :title="hi?'उपलब्ध परीक्षाएँ':'Available exams'" :subtitle="hi?'परीक्षा चुनें और अपनी तैयारी जाँचें।':'Choose an exam and test your preparation.'">
<div class="grid gap-4 md:grid-cols-2"><article v-for="e in exams.data" :key="e.id" class="rounded-2xl border bg-white p-5">
<div class="flex justify-between gap-3"><h2 class="text-lg font-extrabold">{{e.title}}</h2><span class="rounded-full bg-indigo-50 px-2 py-1 text-xs">{{e.questions_count}} {{hi?'प्रश्न':'questions'}}</span></div>
<p class="mt-2 min-h-10 text-sm text-slate-600">{{e.description}}</p><div class="mt-3 text-xs text-slate-500">{{e.duration_minutes}} min · {{hi?'प्रयास':'Attempts'}} {{e.attempts_used}}/{{e.max_attempts}}</div>
<div class="mt-4 flex justify-between border-t pt-3"><span class="text-sm text-slate-500">{{e.latest_attempt?.status==='submitted'?(hi?'पिछला स्कोर':'Last score')+': '+e.latest_attempt.score:(hi?'शुरू करने के लिए तैयार':'Ready to start')}}</span>
<button @click="start(e.id)" :disabled="e.attempts_used>=e.max_attempts && e.latest_attempt?.status!=='in_progress'" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-40">{{hi?'शुरू करें':'Start exam'}}</button></div></article>
<p v-if="!exams.data.length" class="rounded-xl border bg-white p-8 text-center text-slate-500 md:col-span-2">{{hi?'अभी कोई परीक्षा उपलब्ध नहीं है।':'No exams are available right now.'}}</p></div>
</PortalLayout></template>