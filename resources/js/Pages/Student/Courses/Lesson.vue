<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
const props=defineProps({course:Object,lesson:Object,progress:Object,can_complete:Boolean});
const page=usePage();const hi=computed(()=>page.props.locale==='hi');
function complete(){router.post('/student/courses/'+props.course.slug+'/lessons/'+props.lesson.id+'/complete');}
</script>
<template>
<PortalLayout portal="student" :title="hi&&lesson.title_hi?lesson.title_hi:lesson.title" :subtitle="hi&&lesson.description_hi?lesson.description_hi:(lesson.description||course.title)">
<div class="mx-auto max-w-4xl space-y-5">
<a :href="'/student/courses/'+course.slug" class="text-sm font-bold text-indigo-700">← Back to course</a>
<section v-if="lesson.type==='video'" class="overflow-hidden rounded-2xl border bg-slate-950"><div class="aspect-video"><iframe :src="lesson.embed_url || lesson.video_url" title="Course lesson video" class="h-full w-full" allow="autoplay; fullscreen; picture-in-picture" sandbox="allow-scripts allow-same-origin allow-presentation" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"/></div></section>
<section v-else class="rounded-2xl border bg-white p-5 sm:p-8"><div class="whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{hi&&lesson.content_hi?lesson.content_hi:lesson.content}}</div></section>
<section v-if="lesson.resources?.length" class="rounded-2xl border bg-white p-5"><h2 class="font-extrabold">Lesson resources</h2><ul class="mt-3 space-y-2"><li v-for="resource in lesson.resources" :key="resource.id"><a :href="resource.type==='file'?('/student/courses/'+course.slug+'/lessons/'+lesson.id+'/resources/'+resource.id):resource.url" :target="resource.type==='link'?'_blank':null" rel="noopener noreferrer" class="break-all text-sm font-semibold text-indigo-700 underline">{{hi&&resource.title_hi?resource.title_hi:resource.title}} <span class="text-xs text-slate-500">· {{resource.type==='file'?'Download':'Open link'}}</span></a></li></ul></section>
<div v-if="can_complete" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4"><span class="text-sm font-semibold capitalize">{{progress?.status||'In progress'}}</span><button v-if="progress?.status!=='completed'" @click="complete" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Mark as complete</button><span v-else class="text-sm font-bold text-emerald-700">Completed</span></div>
</div>
</PortalLayout>
</template>
