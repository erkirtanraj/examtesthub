<script setup>
import {computed} from 'vue';import {usePage} from '@inertiajs/vue3';import PortalLayout from '../../../Layouts/PortalLayout.vue';
defineProps({exam:Object,result:Object,answers:Array});const page=usePage();const hi=computed(()=>page.props.locale==='hi');
</script>
<template><PortalLayout portal="student" :title="hi?'परीक्षा परिणाम':'Exam result'" :subtitle="exam.title">
<div class="grid gap-3 sm:grid-cols-4"><div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{hi?'स्कोर':'Score'}}</p><p class="mt-1 text-2xl font-black">{{result.score}} / {{result.total_marks}}</p></div>
<div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{hi?'सही':'Correct'}}</p><p class="mt-1 text-2xl font-black text-emerald-700">{{result.correct_count}}</p></div>
<div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{hi?'गलत':'Incorrect'}}</p><p class="mt-1 text-2xl font-black text-rose-700">{{result.incorrect_count}}</p></div>
<div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{hi?'अनुत्तरित':'Unanswered'}}</p><p class="mt-1 text-2xl font-black">{{result.unanswered_count}}</p></div></div>
<section class="mt-6 space-y-3"><h2 class="text-lg font-extrabold">{{hi?'उत्तर विश्लेषण':'Answer review'}}</h2><article v-for="(a,i) in answers" :key="i" class="rounded-xl border bg-white p-4">
<div class="flex justify-between gap-3"><h3 class="font-semibold">{{i+1}}. {{a.prompt}}</h3><span :class="a.is_correct?'text-emerald-700':a.is_correct===false?'text-rose-700':'text-slate-500'" class="text-xs font-bold">{{a.is_correct?(hi?'सही':'Correct'):a.is_correct===false?(hi?'गलत':'Incorrect'):(hi?'छोड़ा':'Skipped')}}</span></div>
<p class="mt-2 text-sm text-slate-600">{{hi?'आपका उत्तर':'Your answer'}}: {{(a.answer||[]).join(', ')||'—'}}</p><p class="mt-1 text-sm text-slate-600">{{hi?'सही उत्तर':'Correct answer'}}: {{(a.correct_answer||[]).join(', ')}}</p><p v-if="a.explanation" class="mt-2 rounded-lg bg-slate-50 p-3 text-sm">{{a.explanation}}</p>
</article></section><a href="/student/exams" class="mt-6 inline-block rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white">{{hi?'परीक्षाओं पर वापस जाएँ':'Back to exams'}}</a>
</PortalLayout></template>