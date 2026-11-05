<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ questions: Object, subjects: Array, filters: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const editing = ref(null);
const search = ref(props.filters?.search || '');
const subjectFilter = ref(props.filters?.subject_id || '');
const difficultyFilter = ref(props.filters?.difficulty || '');
const statusFilter = ref(props.filters?.status || '');
const languageFilter = ref(props.filters?.language || '');
const blank = () => ({ subject_id: '', topic_id: '', type: 'single_choice', prompt: '', options: { A: '', B: '', C: '', D: '' }, correct_answer: ['A'], answer_text: '', explanation: '', difficulty: 'medium', language: 'en', marks: 1, negative_marks: 0, status: 'draft' });
const form = useForm(blank());
const importForm = useForm({ file: null });

const selectedSubject = computed(() => props.subjects.find(s => String(s.id) === String(form.subject_id)));
function applyFilters() {
    router.get('/admin/questions', { search: search.value, subject_id: subjectFilter.value, difficulty: difficultyFilter.value, status: statusFilter.value, language: languageFilter.value }, { preserveState: true, replace: true });
}
function submit() {
    const payload = { ...form.data(), correct_answer: form.type === 'short_answer' ? [form.answer_text] : form.type === 'true_false' ? form.correct_answer : form.correct_answer };
    if (editing.value) router.put('/admin/questions/' + editing.value, payload, { onSuccess: reset });
    else router.post('/admin/questions', payload, { onSuccess: reset });
}
function edit(question) {
    editing.value = question.id;
    form.subject_id = String(question.subject_id);
    form.topic_id = question.topic_id ? String(question.topic_id) : '';
    form.type = question.type; form.prompt = question.prompt;
    form.options = { A: question.options?.A || '', B: question.options?.B || '', C: question.options?.C || '', D: question.options?.D || '' };
    form.correct_answer = question.correct_answer || ['A'];
    form.answer_text = question.correct_answer?.[0] || '';
    form.explanation = question.explanation || ''; form.difficulty = question.difficulty;
    form.language = question.language; form.marks = question.marks; form.negative_marks = question.negative_marks; form.status = question.status;
}
function reset() { editing.value = null; form.reset(); Object.assign(form, blank()); }
function remove(question) {
    if (confirm(hi.value ? 'क्या आप इस प्रश्न को हटाना चाहते हैं?' : 'Delete this question?')) router.delete('/admin/questions/' + question.id);
}
function importCsv() { importForm.post('/admin/questions/import', { forceFormData: true, onSuccess: () => importForm.reset() }); }
</script>

<template>
<PortalLayout portal="admin" :title="hi ? 'प्रश्न बैंक' : 'Question bank'" :subtitle="hi ? 'प्रश्न बनाएं, संपादित करें, फ़िल्टर करें और CSV से आयात/निर्यात करें।' : 'Create, maintain, filter and bulk-transfer reusable exam questions.'">
<div class="mb-5 flex flex-wrap gap-2">
    <a href="/admin/subjects" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold">{{ hi ? 'विषय और टॉपिक' : 'Manage subjects & topics' }}</a>
    <a href="/admin/questions/export" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold">{{ hi ? 'CSV निर्यात' : 'Export CSV' }}</a>
</div>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
<section class="space-y-4">
    <div class="grid gap-2 rounded-xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-3">
        <input v-model="search" @keyup.enter="applyFilters" placeholder="Search question text" class="rounded-lg border px-3 py-2 text-sm">
        <select v-model="subjectFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All subjects</option><option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option></select>
        <select v-model="difficultyFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All difficulty</option><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select>
        <select v-model="statusFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All status</option><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select>
        <select v-model="languageFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All languages</option><option value="en">English</option><option value="hi">Hindi</option></select>
        <button @click="applyFilters" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Apply filters</button>
    </div>
    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Question</th><th class="p-3">Subject / Topic</th><th class="p-3">Level</th><th class="p-3">Status</th><th class="p-3">Action</th></tr></thead>
            <tbody>
                <tr v-for="q in questions.data" :key="q.id" class="border-t align-top">
                    <td class="max-w-md p-3"><p class="line-clamp-3 font-semibold text-slate-800">{{ q.prompt }}</p><p class="mt-1 text-xs text-slate-400">{{ q.type }} · {{ q.language }} · +{{ q.marks }} / -{{ q.negative_marks }}</p></td>
                    <td class="p-3">{{ q.subject?.name }}<div class="text-xs text-slate-500">{{ q.topic?.name || '—' }}</div></td>
                    <td class="p-3 capitalize">{{ q.difficulty }}</td>
                    <td class="p-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ q.status }}</span></td>
                    <td class="whitespace-nowrap p-3"><button @click="edit(q)" class="mr-2 font-semibold text-indigo-700">Edit</button><button @click="remove(q)" class="font-semibold text-rose-700">Delete</button></td>
                </tr>
                <tr v-if="!questions.data.length"><td colspan="5" class="p-8 text-center text-slate-500">No questions match these filters.</td></tr>
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center gap-2 text-sm"><span class="mr-auto text-slate-500">{{ questions.total }} questions · page {{ questions.current_page }} of {{ questions.last_page }}</span><a v-for="link in questions.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['rounded border px-3 py-1', link.active ? 'bg-indigo-600 text-white' : 'bg-white', !link.url ? 'pointer-events-none opacity-40' : '']"/></div>
    <div class="rounded-xl border bg-white p-4">
        <h3 class="font-bold">Bulk CSV import</h3>
        <p class="mt-1 text-xs leading-5 text-slate-500">Headers: subject,topic,type,prompt,options_json,correct_answer_json,explanation,difficulty,language,marks,negative_marks. Imported questions are saved as drafts. JSON columns use objects/arrays.</p>
        <form @submit.prevent="importCsv" class="mt-3 flex flex-wrap items-center gap-3"><input type="file" accept=".csv,.txt" @input="importForm.file = $event.target.files[0]" class="text-sm"><button :disabled="importForm.processing" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Import CSV</button></form>
        <p v-if="importForm.errors.file" class="mt-2 text-sm text-rose-600">{{ importForm.errors.file }}</p>
    </div>
</section>

<aside class="h-fit rounded-xl border bg-white p-5">
    <h2 class="text-lg font-extrabold">{{ editing ? 'Edit question' : 'Create question' }}</h2>
    <form @submit.prevent="submit" class="mt-4 space-y-3">
        <label class="block text-sm font-semibold">Subject<select v-model="form.subject_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Select subject</option><option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option></select></label>
        <label class="block text-sm font-semibold">Topic<select v-model="form.topic_id" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">No topic</option><option v-for="t in selectedSubject?.topics || []" :key="t.id" :value="String(t.id)">{{ t.name }}</option></select></label>
        <label class="block text-sm font-semibold">Question type<select v-model="form.type" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="single_choice">Single choice</option><option value="multiple_choice">Multiple choice</option><option value="true_false">True / False</option><option value="short_answer">Short answer</option></select></label>
        <label class="block text-sm font-semibold">Question text<textarea v-model="form.prompt" required rows="4" class="mt-1 w-full rounded-lg border px-3 py-2"/></label>
        <div v-if="['single_choice','multiple_choice'].includes(form.type)" class="space-y-2">
            <p class="text-sm font-semibold">Options and correct answer</p>
            <div v-for="key in ['A','B','C','D']" :key="key" class="flex items-center gap-2"><span class="w-5 text-sm font-bold">{{ key }}</span><input v-model="form.options[key]" :placeholder="'Option '+key" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm"><input v-model="form.correct_answer" type="checkbox" :value="key" :title="'Correct answer '+key"></div>
            <p class="text-xs text-slate-500">Tick the correct option(s). Single choice allows exactly one.</p>
        </div>
        <div v-else-if="form.type === 'true_false'"><p class="mb-2 text-sm font-semibold">Correct answer</p><select v-model="form.correct_answer[0]" class="w-full rounded-lg border px-3 py-2"><option value="true">True</option><option value="false">False</option></select></div>
        <label v-else class="block text-sm font-semibold">Accepted answer<input v-model="form.answer_text" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        <label class="block text-sm font-semibold">Explanation<textarea v-model="form.explanation" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2"/></label>
        <div class="grid grid-cols-2 gap-2">
            <label class="text-sm font-semibold">Difficulty<select v-model="form.difficulty" class="mt-1 w-full rounded-lg border p-2"><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></label>
            <label class="text-sm font-semibold">Language<select v-model="form.language" class="mt-1 w-full rounded-lg border p-2"><option value="en">English</option><option value="hi">Hindi</option></select></label>
            <label class="text-sm font-semibold">Marks<input v-model.number="form.marks" type="number" min="0.01" step="0.25" class="mt-1 w-full rounded-lg border p-2"></label>
            <label class="text-sm font-semibold">Negative marks<input v-model.number="form.negative_marks" type="number" min="0" step="0.25" class="mt-1 w-full rounded-lg border p-2"></label>
        </div>
        <label class="block text-sm font-semibold">Status<select v-model="form.status" class="mt-1 w-full rounded-lg border p-2"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
        <div v-for="(error,key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</div>
        <div class="flex gap-2"><button :disabled="form.processing" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 font-bold text-white">{{ editing ? 'Save changes' : 'Create question' }}</button><button v-if="editing" type="button" @click="reset" class="rounded-lg border px-3">Cancel</button></div>
    </form>
</aside>
</div>
</PortalLayout>
</template>
