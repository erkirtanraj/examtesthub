<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ materials: Object, subjects: Array, filters: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const currentFilters = ref({
    search: props.filters?.search || '',
    subject_id: props.filters?.subject_id || '',
    language: props.filters?.language || '',
});

function applyFilters() {
    router.get('/student/materials', currentFilters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
function formatBytes(bytes) {
    if (!bytes) return 'PDF';
    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return (bytes / (1024 ** index)).toFixed(index ? 1 : 0) + ' ' + units[index];
}
function languageLabel(language) {
    return language === 'hi' ? 'हिंदी' : language === 'en' ? 'English' : 'English + Hindi';
}
</script>

<template>
    <PortalLayout
        portal="student"
        :title="hi ? 'PDF और अध्ययन सामग्री' : 'PDF & Study Materials'"
        :subtitle="hi ? 'विषय के अनुसार नोट्स और PDF खोजें। कोर्स से जुड़ी सामग्री के लिए संबंधित कोर्स में नामांकन आवश्यक है।' : 'Find notes and PDFs by subject. Course-linked materials require enrollment in the related course.'"
    >
        <div class="space-y-5">
            <form class="grid gap-2 rounded-2xl border bg-white p-4 sm:grid-cols-[minmax(0,1fr)_220px_180px_auto]" @submit.prevent="applyFilters">
                <input v-model="currentFilters.search" placeholder="Search notes, title or topic" class="min-w-0 rounded-lg border p-2 text-sm">
                <select v-model="currentFilters.subject_id" class="rounded-lg border p-2 text-sm">
                    <option value="">All subjects</option>
                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                </select>
                <select v-model="currentFilters.language" class="rounded-lg border p-2 text-sm">
                    <option value="">All languages</option>
                    <option value="en">English</option>
                    <option value="hi">Hindi</option>
                    <option value="both">Bilingual</option>
                </select>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Search</button>
            </form>

            <div class="flex items-center justify-between gap-3">
                <h2 class="font-extrabold">{{ hi ? 'उपलब्ध सामग्री' : 'Available materials' }}</h2>
                <span class="text-xs text-slate-500">{{ materials.total }} PDFs</span>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="material in materials.data" :key="material.id" class="flex min-w-0 flex-col rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700">
                            <span class="text-xs font-black">PDF</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="break-words font-extrabold">{{ hi && material.title_hi ? material.title_hi : material.title }}</h3>
                            <p v-if="hi && material.title_hi" class="mt-0.5 break-words text-xs text-slate-500">{{ material.title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ material.subject || 'General' }} · {{ languageLabel(material.language) }}</p>
                        </div>
                    </div>
                    <p class="mt-3 line-clamp-3 whitespace-pre-line break-words text-sm leading-6 text-slate-600">
                        {{ hi && material.description_hi ? material.description_hi : (material.description || (hi ? 'अध्ययन सामग्री डाउनलोड करें।' : 'Download this study material.')) }}
                    </p>
                    <div v-if="material.course" class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <span class="font-semibold">{{ hi && material.course.title_hi ? material.course.title_hi : material.course.title }}</span>
                        <span v-if="material.course.access_type === 'paid'"> · Course enrollment required</span>
                    </div>
                    <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                        <span class="text-xs text-slate-400">{{ formatBytes(material.file_size) }}</span>
                        <a v-if="material.can_download" :href="'/student/materials/' + material.id + '/download'" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">
                            {{ hi ? 'PDF डाउनलोड करें' : 'Download PDF' }}
                        </a>
                        <a v-else-if="material.course" :href="'/student/courses/' + material.course.slug" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-bold text-indigo-700">
                            {{ hi ? 'कोर्स देखें' : 'View course to enroll' }}
                        </a>
                        <span v-else class="text-xs font-semibold text-slate-400">Unavailable</span>
                    </div>
                </article>
            </div>

            <div v-if="!materials.data.length" class="rounded-2xl border bg-white p-10 text-center">
                <div class="text-lg font-extrabold">{{ hi ? 'कोई सामग्री नहीं मिली' : 'No materials found' }}</div>
                <p class="mt-1 text-sm text-slate-500">{{ hi ? 'खोज या फ़िल्टर बदलकर देखें।' : 'Try changing your search or filters.' }}</p>
            </div>

            <div class="flex items-center justify-between rounded-xl border bg-white p-3 text-sm">
                <a v-if="materials.prev_page_url" :href="materials.prev_page_url" class="font-semibold text-indigo-700">← Previous</a>
                <span v-else class="text-slate-300">← Previous</span>
                <span class="text-xs text-slate-500">Page {{ materials.current_page }} of {{ materials.last_page }}</span>
                <a v-if="materials.next_page_url" :href="materials.next_page_url" class="font-semibold text-indigo-700">Next →</a>
                <span v-else class="text-slate-300">Next →</span>
            </div>
        </div>
    </PortalLayout>
</template>
