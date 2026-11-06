<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import BulkSelection from '../../../Components/Admin/BulkSelection.vue';

const props = defineProps({
    materials: Object,
    subjects: Array,
    courses: Array,
    filters: Object,
});
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const editing = ref(null);
const currentFilters = ref({
    search: props.filters?.search || '',
    subject_id: props.filters?.subject_id || '',
    course_id: props.filters?.course_id || '',
    status: props.filters?.status || '',
});
const blank = () => ({
    title: '',
    title_hi: '',
    description: '',
    description_hi: '',
    subject_id: '',
    course_id: '',
    language: 'both',
    status: 'draft',
    file: null,
});
const form = useForm(blank());

function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function submit() {
    const url = editing.value ? '/admin/materials/' + editing.value : '/admin/materials';
    form.transform(data => ({
        ...data,
        subject_id: data.subject_id || null,
        course_id: data.course_id || null,
    })).post(url, {
        forceFormData: true,
        onSuccess: reset,
    });
}
function edit(material) {
    editing.value = material.id;
    Object.assign(form, {
        title: material.title,
        title_hi: material.title_hi || '',
        description: material.description || '',
        description_hi: material.description_hi || '',
        subject_id: material.subject_id || '',
        course_id: material.course_id || '',
        language: material.language,
        status: material.status,
        file: null,
    });
    form.clearErrors();
}
function applyFilters() {
    router.get('/admin/materials', currentFilters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
function changeStatus(material, status) {
    if (confirm('Change material status to ' + status + '?')) {
        router.patch('/admin/materials/' + material.id + '/status', { status });
    }
}
function remove(material) {
    if (confirm('Delete "' + material.title + '" and its stored PDF? This cannot be undone.')) {
        router.delete('/admin/materials/' + material.id);
    }
}
function formatBytes(bytes) {
    if (!bytes) return '0 KB';
    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return (bytes / (1024 ** index)).toFixed(index ? 1 : 0) + ' ' + units[index];
}
</script>

<template>
    <PortalLayout
        portal="admin"
        :title="hi ? 'PDF और अध्ययन सामग्री' : 'PDF & Study Materials'"
        :subtitle="hi ? 'PDF अपलोड करें, विषय या कोर्स से जोड़ें और प्रकाशन नियंत्रित करें।' : 'Upload PDFs, organize them by subject or course, and control student access.'"
    >
        <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
            <section class="min-w-0 space-y-3">
                <form class="grid gap-2 rounded-2xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="applyFilters">
                    <input v-model="currentFilters.search" placeholder="Search title or filename" class="min-w-0 rounded-lg border p-2 text-sm">
                    <select v-model="currentFilters.subject_id" class="min-w-0 rounded-lg border p-2 text-sm">
                        <option value="">All subjects</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                    </select>
                    <select v-model="currentFilters.course_id" class="min-w-0 rounded-lg border p-2 text-sm">
                        <option value="">All courses</option>
                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                    </select>
                    <div class="flex gap-2">
                        <select v-model="currentFilters.status" class="min-w-0 flex-1 rounded-lg border p-2 text-sm">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white">Filter</button>
                    </div>
                </form>

<BulkSelection :items="materials.data" resource="materials" storage-key="admin-bulk-materials" label-key="title" />
                <div class="overflow-hidden rounded-2xl border bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b p-4">
                        <h2 class="font-extrabold">Material library</h2>
                        <span class="text-xs text-slate-500">{{ materials.total }} records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="p-3">Material</th>
                                    <th class="p-3">Subject / Course</th>
                                    <th class="p-3">Language</th>
                                    <th class="p-3">File size</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="material in materials.data" :key="material.id" class="border-t align-top">
                                    <td class="max-w-xs p-3">
                                        <div class="font-bold">{{ material.title }}</div>
                                        <div v-if="material.title_hi" class="text-xs text-slate-500">{{ material.title_hi }}</div>
                                        <div class="mt-1 break-all text-xs text-slate-400">{{ material.original_name }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div>{{ material.subject?.name || '—' }}</div>
                                        <div class="text-xs text-slate-500">{{ material.course?.title || 'Free library' }}</div>
                                    </td>
                                    <td class="p-3 uppercase">{{ material.language }}</td>
                                    <td class="p-3 whitespace-nowrap">{{ formatBytes(material.file_size) }}</td>
                                    <td class="p-3 capitalize">{{ material.status }}</td>
                                    <td class="p-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button class="font-semibold text-indigo-700" @click="edit(material)">Edit</button>
                                            <select :value="material.status" class="rounded border p-1 text-xs" @change="changeStatus(material, $event.target.value)">
                                                <option value="draft">Draft</option>
                                                <option value="published">Published</option>
                                                <option value="archived">Archived</option>
                                            </select>
                                            <button class="font-semibold text-rose-700" @click="remove(material)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!materials.data.length">
                                    <td colspan="6" class="p-8 text-center text-slate-500">No study materials found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between border-t p-3 text-sm">
                        <a v-if="materials.prev_page_url" :href="materials.prev_page_url" class="font-semibold text-indigo-700">← Previous</a>
                        <span v-else class="text-slate-300">← Previous</span>
                        <span class="text-xs text-slate-500">Page {{ materials.current_page }} of {{ materials.last_page }}</span>
                        <a v-if="materials.next_page_url" :href="materials.next_page_url" class="font-semibold text-indigo-700">Next →</a>
                        <span v-else class="text-slate-300">Next →</span>
                    </div>
                </div>
            </section>

            <aside class="h-fit rounded-2xl border bg-white p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-lg font-extrabold">{{ editing ? 'Edit study material' : 'Upload PDF material' }}</h2>
                    <button v-if="editing" type="button" @click="reset" class="text-xs font-bold text-indigo-700">New upload</button>
                </div>
                <form class="mt-4 space-y-3" @submit.prevent="submit">
                    <label class="block text-sm font-semibold">Title (English)
                        <input v-model="form.title" required maxlength="180" class="mt-1 w-full rounded-lg border p-2">
                    </label>
                    <label class="block text-sm font-semibold">शीर्षक (Hindi)
                        <input v-model="form.title_hi" maxlength="180" class="mt-1 w-full rounded-lg border p-2">
                    </label>
                    <label class="block text-sm font-semibold">Description
                        <textarea v-model="form.description" rows="2" maxlength="10000" class="mt-1 w-full rounded-lg border p-2"/>
                    </label>
                    <label class="block text-sm font-semibold">विवरण (Hindi)
                        <textarea v-model="form.description_hi" rows="2" maxlength="10000" class="mt-1 w-full rounded-lg border p-2"/>
                    </label>
                    <label class="block text-sm font-semibold">Subject
                        <select v-model="form.subject_id" class="mt-1 w-full rounded-lg border p-2">
                            <option value="">No subject</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Course access
                        <select v-model="form.course_id" class="mt-1 w-full rounded-lg border p-2">
                            <option value="">Free library (no course enrollment required)</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                        </select>
                        <span class="mt-1 block text-xs font-normal text-slate-500">Linking a course means only students enrolled in that course can download this PDF.</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-sm font-semibold">Language
                            <select v-model="form.language" class="mt-1 w-full rounded-lg border p-2">
                                <option value="en">English</option>
                                <option value="hi">Hindi</option>
                                <option value="both">Both</option>
                            </select>
                        </label>
                        <label class="text-sm font-semibold">Status
                            <select v-model="form.status" class="mt-1 w-full rounded-lg border p-2">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </label>
                    </div>
                    <label class="block text-sm font-semibold">PDF file {{ editing ? '(optional when replacing)' : '(required)' }}
                        <input type="file" accept=".pdf,application/pdf" :required="!editing" class="mt-1 w-full text-sm" @change="form.file = $event.target.files?.[0] || null">
                        <span class="mt-1 block text-xs font-normal text-slate-500">PDF only · maximum 50 MB. Uploaded files remain private.</span>
                    </label>
                    <div v-for="(error, key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</div>
                    <div class="flex gap-2">
                        <button :disabled="form.processing" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Upload material') }}
                        </button>
                        <button v-if="editing" type="button" @click="reset" class="rounded-lg border px-3">Cancel</button>
                    </div>
                </form>
            </aside>
        </div>
    </PortalLayout>
</template>
