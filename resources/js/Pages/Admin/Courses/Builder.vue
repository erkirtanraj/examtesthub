<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
const props=defineProps({course:Object});
const page=usePage(); const hi=computed(()=>page.props.locale==='hi');
const sectionForm=useForm({title:'',title_hi:'',description:'',description_hi:''});
const chapterForm=useForm({title:'',title_hi:''});
const lessonForm=useForm({title:'',title_hi:'',description:'',description_hi:'',type:'text',video_url:'',content:'',content_hi:'',is_preview:false,duration_minutes:0,status:'draft'});
const resourceForm=useForm({title:'',title_hi:'',url:''});
const activeSection=ref(null), activeChapter=ref(null), activeLesson=ref(null);
function addSection(){sectionForm.post('/admin/courses/'+props.course.id+'/sections',{onSuccess:()=>sectionForm.reset()});}
function addChapter(){chapterForm.post('/admin/courses/'+props.course.id+'/sections/'+activeSection.value+'/chapters',{onSuccess:()=>chapterForm.reset()});}
function addLesson(){lessonForm.post('/admin/courses/'+props.course.id+'/chapters/'+activeChapter.value+'/lessons',{onSuccess:()=>lessonForm.reset()});}
function addResource(){resourceForm.post('/admin/courses/'+props.course.id+'/lessons/'+activeLesson.value+'/resources',{onSuccess:()=>resourceForm.reset()});}
function updateLesson(lesson){const f=useForm({...lesson,is_preview:!!lesson.is_preview});f.put('/admin/courses/'+props.course.id+'/lessons/'+lesson.id);}
function publish(){if(confirm('Publish this course? It must contain at least one published lesson.'))router.patch('/admin/courses/'+props.course.id+'/status',{status:'published'});}
</script>
<template>
<PortalLayout portal="admin" :title="hi?'कोर्स कंटेंट बिल्डर':'Course content builder'" :subtitle="course.title+' · '+(course.category||'Course curriculum')">
<div class="space-y-5">
<div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4"><div><h2 class="font-extrabold">{{course.title}}</h2><p class="text-sm text-slate-500">{{course.title_hi}}</p></div><div class="flex gap-2"><a href="/admin/courses" class="rounded-lg border px-3 py-2 text-sm font-semibold">Back to courses</a><button @click="publish" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">Publish course</button></div></div>
<div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
<section class="space-y-3">
<article v-for="section in course.sections" :key="section.id" class="rounded-2xl border bg-white p-4">
<div class="flex flex-wrap items-start justify-between gap-2"><div><h3 class="font-extrabold">{{section.position}}. {{section.title}}</h3><p class="text-sm text-slate-500">{{section.title_hi}}</p></div><button @click="activeSection=section.id" class="rounded-lg border px-3 py-1.5 text-xs font-bold">Add chapter</button></div>
<div v-for="chapter in section.chapters" :key="chapter.id" class="mt-3 rounded-xl bg-slate-50 p-3">
<div class="flex flex-wrap items-center justify-between gap-2"><div class="font-bold text-sm">{{chapter.position}}. {{chapter.title}} <span class="font-normal text-slate-500">{{chapter.title_hi}}</span></div><button @click="activeChapter=chapter.id" class="text-xs font-bold text-indigo-700">Add lesson</button></div>
<div v-for="lesson in chapter.lessons" :key="lesson.id" class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border bg-white p-3 text-sm"><div><div class="font-semibold">{{lesson.title}} <span class="text-slate-500">· {{lesson.type}}</span></div><div class="text-xs text-slate-500">{{lesson.status}} · {{lesson.duration_minutes}} min · {{lesson.is_preview?'Preview':''}}</div></div><button @click="activeLesson=lesson.id" class="text-xs font-bold text-indigo-700">Add resource</button></div>
</div>
</article>
<p v-if="!course.sections.length" class="rounded-xl border bg-white p-8 text-center text-slate-500">Start by adding a course section.</p>
</section>
<aside class="space-y-4">
<form @submit.prevent="addSection" class="rounded-2xl border bg-white p-4 space-y-2"><h3 class="font-extrabold">Add section</h3><input v-model="sectionForm.title" required placeholder="Section title (English)" class="w-full rounded-lg border p-2"><input v-model="sectionForm.title_hi" placeholder="Section title (Hindi)" class="w-full rounded-lg border p-2"><textarea v-model="sectionForm.description" placeholder="Description" class="w-full rounded-lg border p-2"/><button class="w-full rounded-lg bg-indigo-600 p-2 font-bold text-white">Add section</button></form>
<form v-if="activeSection" @submit.prevent="addChapter" class="rounded-2xl border bg-white p-4 space-y-2"><h3 class="font-extrabold">Add chapter</h3><input v-model="chapterForm.title" required placeholder="Chapter title (English)" class="w-full rounded-lg border p-2"><input v-model="chapterForm.title_hi" placeholder="Chapter title (Hindi)" class="w-full rounded-lg border p-2"><button class="w-full rounded-lg bg-indigo-600 p-2 font-bold text-white">Save chapter</button></form>
<form v-if="activeChapter" @submit.prevent="addLesson" class="rounded-2xl border bg-white p-4 space-y-2"><h3 class="font-extrabold">Add lesson</h3><input v-model="lessonForm.title" required placeholder="Lesson title (English)" class="w-full rounded-lg border p-2"><input v-model="lessonForm.title_hi" placeholder="Lesson title (Hindi)" class="w-full rounded-lg border p-2"><select v-model="lessonForm.type" class="w-full rounded-lg border p-2"><option value="text">Text lesson</option><option value="video">Video lesson</option></select><input v-if="lessonForm.type==='video'" v-model="lessonForm.video_url" required type="url" placeholder="HTTPS video URL" class="w-full rounded-lg border p-2"><textarea v-if="lessonForm.type==='text'" v-model="lessonForm.content" required rows="5" placeholder="Lesson content (English)" class="w-full rounded-lg border p-2"/><textarea v-if="lessonForm.type==='text'" v-model="lessonForm.content_hi" rows="5" placeholder="पाठ सामग्री (Hindi)" class="w-full rounded-lg border p-2"/><textarea v-model="lessonForm.description" placeholder="Short description" class="w-full rounded-lg border p-2"/><input v-model.number="lessonForm.duration_minutes" type="number" min="0" placeholder="Duration in minutes" class="w-full rounded-lg border p-2"><select v-model="lessonForm.status" class="w-full rounded-lg border p-2"><option value="draft">Draft</option><option value="published">Published</option></select><label class="flex items-center gap-2 text-sm"><input v-model="lessonForm.is_preview" type="checkbox"> Free preview lesson</label><button class="w-full rounded-lg bg-indigo-600 p-2 font-bold text-white">Save lesson</button></form>
<form v-if="activeLesson" @submit.prevent="addResource" class="rounded-2xl border bg-white p-4 space-y-2"><h3 class="font-extrabold">Add resource link</h3><input v-model="resourceForm.title" required placeholder="Resource title" class="w-full rounded-lg border p-2"><input v-model="resourceForm.title_hi" placeholder="शीर्षक (Hindi)" class="w-full rounded-lg border p-2"><input v-model="resourceForm.url" required type="url" placeholder="HTTPS resource URL" class="w-full rounded-lg border p-2"><button class="w-full rounded-lg bg-indigo-600 p-2 font-bold text-white">Add link</button></form>
</aside></div></div>
</PortalLayout>
</template>
