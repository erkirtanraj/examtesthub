<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
const props=defineProps({course:Object,enrollment:Object,progress:Object,resume_lesson_id:Number});
const page=usePage();const hi=computed(()=>page.props.locale==='hi');
function enroll(){if(props.course.access_type==='paid'){const coupon=(window.prompt(hi.value?'कूपन कोड (वैकल्पिक):':'Coupon code (optional):')||'').trim();router.post('/student/courses/'+props.course.slug+'/checkout',coupon?{coupon_code:coupon}:{});}else router.post('/student/courses/'+props.course.slug+'/enroll');}
</script>
<template>
<PortalLayout portal="student" :title="hi ? (course.title_hi || course.title) : course.title" :subtitle="hi ? (course.description_hi || course.description || 'Course curriculum and lesson progress.') : (course.description || 'Course curriculum and lesson progress.')">
<div class="space-y-5">
<section v-if="!enrollment||!['active','completed'].includes(enrollment.status)" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4"><p class="text-sm text-slate-600">Enroll to unlock the complete course. Preview lessons remain available.</p><button @click="enroll" class="rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white">{{course.access_type==='paid'?'Buy course':'Enroll free'}}</button></section>
<section v-else class="rounded-2xl border bg-white p-4"><div class="flex justify-between gap-3 text-sm font-semibold"><span>Course progress</span><span>{{progress.completed}} / {{progress.total}} lessons</span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600" :style="{width:(progress.total?progress.completed/progress.total*100:0)+'%'}"/></div><a v-if="resume_lesson_id" :href="'/student/courses/'+course.slug+'/lessons/'+resume_lesson_id" class="mt-3 inline-flex rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">Resume learning</a></section>
<section v-if="enrollment?.status==='completed'" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><p class="font-semibold text-emerald-800">Course completed successfully.</p><a :href="'/student/courses/'+course.slug+'/certificate'" class="mt-3 inline-flex rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white">View certificate</a></section>
<div v-for="section in course.sections" :key="section.id" class="rounded-2xl border bg-white p-4 sm:p-5"><h2 class="font-extrabold">{{hi ? (section.title_hi || section.title) : section.title}}</h2><div v-for="chapter in section.chapters" :key="chapter.id" class="mt-4"><h3 class="text-sm font-bold text-slate-700">{{hi ? (chapter.title_hi || chapter.title) : chapter.title}}</h3><div class="mt-2 divide-y rounded-xl border"><div v-for="lesson in chapter.lessons" :key="lesson.id" class="flex flex-wrap items-center justify-between gap-3 p-3"><div class="min-w-0"><p class="break-words text-sm font-semibold">{{hi ? (lesson.title_hi || lesson.title) : lesson.title}}</p><p class="text-xs text-slate-500">{{lesson.type}} · {{lesson.duration_minutes}} min {{lesson.is_preview?'· Preview':''}}</p></div><a v-if="!lesson.is_locked" :href="'/student/courses/'+course.slug+'/lessons/'+lesson.id" class="rounded-lg border px-3 py-1.5 text-sm font-bold text-indigo-700">Open lesson</a><span v-else class="text-xs font-semibold text-slate-400">Locked</span></div></div></div></div>
</div>
</PortalLayout>
</template>
