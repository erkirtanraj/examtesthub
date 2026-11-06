<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import BulkSelection from '../../../Components/Admin/BulkSelection.vue';

const props = defineProps({ exams: Object, questions: Array });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const editing = ref(null);
const blank = () => ({ title: '', description: '', instructions: '', duration_minutes: 60, max_attempts: 1, starts_at: '', ends_at: '', shuffle_questions: false, shuffle_options: false, status: 'draft', question_ids: [] });
const form = useForm(blank());
function reset() { editing.value = null; form.reset(); form.clearErrors(); }
function submit() {
    if (editing.value) form.put('/admin/exams/' + editing.value, { onSuccess: reset });
    else form.post('/admin/exams', { onSuccess: reset });
}
function edit(exam) {
    editing.value = exam.id;
    form.title = exam.title; form.description = exam.description || ''; form.instructions = exam.instructions || '';
    form.duration_minutes = exam.duration_minutes; form.max_attempts = exam.max_attempts;
    form.starts_at = exam.starts_at ? exam.starts_at.replace(' ', 'T').slice(0, 16) : '';
    form.ends_at = exam.ends_at ? exam.ends_at.replace(' ', 'T').slice(0, 16) : '';
    form.shuffle_questions = exam.shuffle_questions; form.shuffle_options = exam.shuffle_options;
    form.status = exam.status; form.question_ids = (exam.questions || []).map(q => String(q.id));
}
function changeStatus(exam, status) {
    if (confirm('Change exam status to ' + status + '?')) router.patch('/admin/exams/' + exam.id + '/status', { status });
}
</script>

<template>
<PortalLayout portal="admin" :title="hi ? 'परीक्षा प्रबंधन' : 'Exam management'" :subtitle="hi ? 'प्रश्न चुनें, समय निर्धारित करें और परीक्षा प्रकाशित करें।' : 'Build exams from approved questions, configure timing and manage publication.'">
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
<BulkSelection :items="exams.data" resource="exams" storage-key="admin-bulk-exams" label-key="title" />
<section class="space-y-4">
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="w-full text-left text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Exam</th><th class="p-3">Questions</th><th class="p-3">Duration</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead>
<tbody>
<tr v-for="exam in exams.data" :key="exam.id" class="border-t">
<td class="p-3"><p class="font-bold">{{ exam.title }}</p><p class="text-xs text-slate-400">{{ exam.slug }}</p></td>
<td class="p-3">{{ exam.questions_count }}</td>
<td class="p-3">{{ exam.duration_minutes }} min</td>
<td class="p-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs capitalize">{{ exam.status }}</span></td>
<td class="space-y-1 p-3"><button @click="edit(exam)" class="mr-2 font-semibold text-indigo-700">Edit</button><select :value="exam.status" @change="changeStatus(exam, $event.target.value)" class="rounded border p-1 text-xs"><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="published">Published</option><option value="archived">Archived</option></select></td>
</tr>
<tr v-if="!exams.data.length"><td colspan="5" class="p-8 text-center text-slate-500">No exams created yet.</td></tr>
</tbody></table></div>
<div class="flex flex-wrap items-center gap-2 text-sm"><span class="mr-auto text-slate-500">{{ exams.total }} exams</span><a v-for="link in exams.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['rounded border px-3 py-1', link.active ? 'bg-indigo-600 text-white' : 'bg-white', !link.url ? 'pointer-events-none opacity-40' : '']"/></div>
<div class="rounded-xl border bg-white p-4"><h3 class="font-bold">Exam publishing rules</h3><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600"><li>Only published questions can be selected.</li><li>Scheduled exams require both start and end time.</li><li>Exam end time must be later than start time.</li><li>At least one question is required before scheduling or publishing.</li><li>Archived exams are read-only.</li></ul></div>
</section>

<aside class="h-fit rounded-xl border bg-white p-5">
<h2 class="text-lg font-extrabold">{{ editing ? 'Edit exam' : 'Create exam' }}</h2>
<form @submit.prevent="submit" class="mt-4 space-y-3">
<label class="block text-sm font-semibold">Exam title<input v-model="form.title" required maxlength="180" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
<label class="block text-sm font-semibold">Description<textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2"/></label>
<label class="block text-sm font-semibold">Instructions<textarea v-model="form.instructions" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2"/></label>
<div class="grid grid-cols-2 gap-2">
<label class="text-sm font-semibold">Duration (minutes)<input v-model.number="form.duration_minutes" type="number" min="1" max="1440" required class="mt-1 w-full rounded-lg border p-2"></label>
<label class="text-sm font-semibold">Max attempts<input v-model.number="form.max_attempts" type="number" min="1" max="100" required class="mt-1 w-full rounded-lg border p-2"></label>
<label class="text-sm font-semibold">Starts at<input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded-lg border p-2"></label>
<label class="text-sm font-semibold">Ends at<input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded-lg border p-2"></label>
</div>
<div class="space-y-2 text-sm"><label class="flex items-center gap-2"><input v-model="form.shuffle_questions" type="checkbox"> Shuffle question order</label><label class="flex items-center gap-2"><input v-model="form.shuffle_options" type="checkbox"> Shuffle answer options</label></div>
<label class="block text-sm font-semibold">Initial status<select v-model="form.status" class="mt-1 w-full rounded-lg border p-2"><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
<label class="block text-sm font-semibold">Select published questions <span class="font-normal text-slate-500">({{ form.question_ids.length }} selected)</span><select v-model="form.question_ids" multiple size="9" required class="mt-1 w-full rounded-lg border p-2 text-sm"><option v-for="q in questions" :key="q.id" :value="String(q.id)">#{{ q.id }} · {{ q.subject?.name }} · {{ q.prompt.slice(0, 95) }}</option></select><span class="mt-1 block text-xs font-normal text-slate-500">Hold Ctrl (Windows) to select multiple questions. Order selected in the browser determines initial order.</span></label>
<div v-for="(error,key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</div>
<div class="flex gap-2"><button :disabled="form.processing" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 font-bold text-white">{{ editing ? 'Save exam' : 'Create exam' }}</button><button v-if="editing" type="button" @click="reset" class="rounded-lg border px-3">Cancel</button></div>
</form>
</aside>
</div>
</PortalLayout>
</template>
