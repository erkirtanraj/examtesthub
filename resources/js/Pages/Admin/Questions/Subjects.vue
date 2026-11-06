<script setup>
import { router, useForm } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import BulkSelection from '../../../Components/Admin/BulkSelection.vue';
const props = defineProps({ subjects: Array });
const subjectForm = useForm({ name: '', description: '' });
const topicForms = Object.fromEntries(props.subjects.map(s => [s.id, useForm({ name: '', description: '' })]));
function createSubject() { subjectForm.post('/admin/subjects', { onSuccess: () => subjectForm.reset() }); }
function createTopic(subject) { topicForms[subject.id].post('/admin/subjects/' + subject.id + '/topics', { onSuccess: () => topicForms[subject.id].reset() }); }
function toggle(subject) {
    if (confirm('Change subject availability?')) {
        router.put('/admin/subjects/' + subject.id, { name: subject.name, description: subject.description || '', is_active: !subject.is_active });
    }
}
</script>
<template>
<PortalLayout portal="admin" title="Subjects & topics" subtitle="Organize questions into subjects and topic hierarchies.">
<div class="mb-4"><a href="/admin/questions" class="text-sm font-semibold text-indigo-700">← Back to question bank</a></div>
<div class="grid gap-5 lg:grid-cols-[340px_1fr]">
<section class="h-fit rounded-xl border bg-white p-5"><h2 class="font-bold">Create subject</h2><form @submit.prevent="createSubject" class="mt-3 space-y-3"><input v-model="subjectForm.name" required placeholder="Subject name" class="w-full rounded-lg border p-2"><textarea v-model="subjectForm.description" placeholder="Description" rows="3" class="w-full rounded-lg border p-2"/><button class="rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white">Add subject</button><p v-if="subjectForm.errors.name" class="text-sm text-rose-600">{{ subjectForm.errors.name }}</p></form></section>
<BulkSelection :items="subjects" resource="subjects" storage-key="admin-bulk-subjects" label-key="name" />
<section class="space-y-4">
<article v-for="subject in subjects" :key="subject.id" class="rounded-xl border bg-white p-5">
<div class="flex flex-wrap items-center justify-between gap-2"><div><h3 class="text-lg font-bold">{{ subject.name }}</h3><p class="text-xs text-slate-500">{{ subject.questions_count }} questions · {{ subject.topics_count }} topics · {{ subject.is_active ? 'Active' : 'Inactive' }}</p></div><div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ subject.slug }}</span><button @click="toggle(subject)" class="text-xs font-semibold text-indigo-700">{{ subject.is_active ? 'Deactivate' : 'Activate' }}</button></div></div>
<div class="mt-4 flex flex-wrap gap-2"><span v-for="topic in subject.topics" :key="topic.id" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-sm text-indigo-800">{{ topic.name }}</span><span v-if="!subject.topics.length" class="text-sm text-slate-400">No topics yet.</span></div>
<form @submit.prevent="createTopic(subject)" class="mt-4 flex flex-wrap gap-2"><input v-model="topicForms[subject.id].name" required placeholder="New topic name" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm"><button class="rounded-lg border px-4 py-2 text-sm font-bold">Add topic</button></form>
</article>
<p v-if="!subjects.length" class="rounded-xl border bg-white p-8 text-center text-slate-500">Create your first subject to start organizing questions.</p>
</section>
</div>
</PortalLayout>
</template>
