<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
defineProps({ courses: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const editing = ref(null);
const blank = () => ({ title:'', title_hi:'', description:'', description_hi:'', category:'', access_type:'free', price:0, currency:'INR', status:'draft', starts_at:'', ends_at:'' });
const form = useForm(blank());
function reset(){ editing.value=null; form.reset(); form.clearErrors(); }
function submit(){ const options={onSuccess:reset}; editing.value ? form.put('/admin/courses/'+editing.value,options) : form.post('/admin/courses',options); }
function edit(course){ editing.value=course.id; Object.assign(form,{title:course.title,title_hi:course.title_hi||'',description:course.description||'',description_hi:course.description_hi||'',category:course.category||'',access_type:course.access_type,price:Number(course.price),currency:course.currency,status:course.status,starts_at:course.starts_at?course.starts_at.replace(' ','T').slice(0,16):'',ends_at:course.ends_at?course.ends_at.replace(' ','T').slice(0,16):''}); }
function status(course,value){ if(confirm('Change course status to '+value+'?')) router.patch('/admin/courses/'+course.id+'/status',{status:value}); }
</script>
<template>
<PortalLayout portal="admin" :title="hi?'LMS कोर्स प्रबंधन':'LMS Course Management'" :subtitle="hi?'कोर्स, मूल्य, प्रकाशन और पाठ्यक्रम संरचना प्रबंधित करें।':'Create and manage bilingual courses, pricing, publication and learning content.'">
<div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
<section class="min-w-0 overflow-x-auto rounded-2xl border bg-white">
<table class="w-full min-w-[680px] text-left text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Course</th><th class="p-3">Access</th><th class="p-3">Sections</th><th class="p-3">Students</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead>
<tbody><tr v-for="course in courses.data" :key="course.id" class="border-t">
<td class="p-3"><div class="font-bold">{{course.title}}</div><div class="text-xs text-slate-500">{{course.category}}</div></td>
<td class="p-3">{{course.access_type==='paid'?'₹ '+course.price:'Free'}}</td><td class="p-3">{{course.sections_count}}</td><td class="p-3">{{course.enrollments_count}}</td>
<td class="p-3 capitalize">{{course.status}}</td><td class="p-3"><div class="flex flex-wrap gap-3"><button class="font-semibold text-indigo-700" @click="edit(course)">Edit</button><a class="font-semibold text-indigo-700" :href="'/admin/courses/'+course.id+'/builder'">Build content</a><a class="font-semibold text-indigo-700" :href="'/admin/courses/'+course.id+'/enrollments'">Enrollments</a><select :value="course.status" class="rounded border p-1" @change="status(course,$event.target.value)"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></div></td>
</tr><tr v-if="!courses.data.length"><td colspan="6" class="p-8 text-center text-slate-500">No courses created yet.</td></tr></tbody></table>
</section>
<aside class="h-fit rounded-2xl border bg-white p-5">
<h2 class="text-lg font-extrabold">{{editing?'Edit course':'Create course'}}</h2>
<form class="mt-4 space-y-3" @submit.prevent="submit">
<label class="block text-sm font-semibold">Title (English)<input v-model="form.title" required maxlength="180" class="mt-1 w-full rounded-lg border p-2"></label>
<label class="block text-sm font-semibold">शीर्षक (Hindi)<input v-model="form.title_hi" maxlength="180" class="mt-1 w-full rounded-lg border p-2"></label>
<label class="block text-sm font-semibold">Description<textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border p-2"/></label>
<label class="block text-sm font-semibold">विवरण (Hindi)<textarea v-model="form.description_hi" rows="3" class="mt-1 w-full rounded-lg border p-2"/></label>
<label class="block text-sm font-semibold">Category<input v-model="form.category" maxlength="120" class="mt-1 w-full rounded-lg border p-2"></label>
<div class="grid grid-cols-2 gap-2"><label class="text-sm font-semibold">Access<select v-model="form.access_type" class="mt-1 w-full rounded border p-2"><option value="free">Free</option><option value="paid">Paid</option></select></label><label v-if="form.access_type==='paid'" class="text-sm font-semibold">Price (INR)<input v-model.number="form.price" type="number" min="1" step=".01" required class="mt-1 w-full rounded border p-2"></label></div>
<div class="grid grid-cols-2 gap-2"><label class="text-sm font-semibold">Starts at<input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Ends at<input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded border p-2"></label></div>
<div v-if="!editing" class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">New courses are created as Draft. Add at least one published lesson, then publish the course from the course builder.</div>
<div v-for="(error,key) in form.errors" :key="key" class="text-xs text-rose-600">{{error}}</div>
<div class="flex gap-2"><button :disabled="form.processing" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white disabled:opacity-50">{{editing?'Save changes':'Create course'}}</button><button v-if="editing" type="button" @click="reset" class="rounded-lg border px-3">Cancel</button></div>
</form>
</aside>
</div>
</PortalLayout>
</template>
