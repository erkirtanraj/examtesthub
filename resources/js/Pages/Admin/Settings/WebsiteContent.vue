<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ items: { type: Array, default: () => [] } });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const success = computed(() => page.props.flash?.success || '');
const blocks = ref([]);
const saving = ref(false);

const defaults = () => ({
    en: { title: '', description: '', cta_primary: '', cta_primary_url: '', cta_secondary: '', cta_secondary_url: '', image_url: '', html: '' },
    hi: { title: '', description: '', cta_primary: '', cta_primary_url: '', cta_secondary: '', cta_secondary_url: '', image_url: '', html: '' },
    section_type: 'custom',
});

function loadBlocks() {
    blocks.value = props.items
        .filter(item => item.type === 'home' && (item.key || '').startsWith('section.'))
        .map(item => ({
            ...item,
            content: {
                ...defaults(),
                ...(item.content || {}),
                en: { ...defaults().en, ...(item.content?.en || {}) },
                hi: { ...defaults().hi, ...(item.content?.hi || {}) },
            },
        }))
        .sort((a, b) => a.sort_order - b.sort_order || a.id - b.id);
}
watch(() => props.items, loadBlocks, { immediate: true, deep: true });

function move(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= blocks.value.length) return;
    const current = blocks.value[index];
    blocks.value[index] = blocks.value[target];
    blocks.value[target] = current;
    blocks.value.forEach((block, i) => block.sort_order = (i + 1) * 10);
}

function saveLayout() {
    saving.value = true;
    router.put('/admin/settings/website-content/layout', {
        blocks: blocks.value.map((block, index) => ({
            id: block.id,
            sort_order: (index + 1) * 10,
            is_published: !!block.is_published,
        })),
    }, { preserveScroll: true, onFinish: () => saving.value = false });
}

function saveBlock(block) {
    router.put('/admin/settings/website-content/' + block.id, {
        type: 'home',
        key: block.key,
        title: block.title,
        slug: null,
        content: block.content,
        is_published: !!block.is_published,
        sort_order: block.sort_order,
    }, { preserveScroll: true });
}

function addCustomHtml() {
    const key = 'section.custom.' + Date.now();
    router.post('/admin/settings/website-content', {
        type: 'home',
        key,
        title: hi.value ? 'नया HTML ब्लॉक' : 'New HTML block',
        slug: null,
        content: {
            ...defaults(),
            section_type: 'html',
            en: { ...defaults().en, title: 'New section', html: '<h2>New section</h2><p>Add your content here.</p>' },
            hi: { ...defaults().hi, title: 'नया सेक्शन', html: '<h2>नया सेक्शन</h2><p>यहाँ अपनी सामग्री जोड़ें।</p>' },
        },
        is_published: true,
        sort_order: (blocks.value.length + 1) * 10,
    }, { preserveScroll: true });
}

function deleteBlock(block) {
    if (confirm(hi.value ? 'क्या आप यह सेक्शन हटाना चाहते हैं?' : 'Delete this homepage section?')) {
        router.delete('/admin/settings/website-content/' + block.id, { preserveScroll: true });
    }
}

function resetLayout() {
    loadBlocks();
}
</script>

<template>
    <PortalLayout portal="admin" :title="hi ? 'होमपेज लेआउट' : 'Home page layout'" :subtitle="hi ? 'सेक्शन का क्रम बदलें, दिखाएँ या छिपाएँ और कस्टम HTML ब्लॉक जोड़ें।' : 'Reorder with ↑ / ↓. Toggle blocks on/off. Add custom HTML sections for anything else.'">
        <div class="space-y-4">
            <div v-if="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ success }}</div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">{{ blocks.length }} {{ hi ? 'सेक्शन' : 'sections' }} · {{ blocks.filter(block => block.is_published).length }} {{ hi ? 'दिख रहे हैं' : 'visible' }}</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="addCustomHtml" class="rounded-lg bg-emerald-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-900">＋ {{ hi ? 'कस्टम HTML ब्लॉक जोड़ें' : 'Add custom HTML block' }}</button>
                    <button type="button" @click="saveLayout" :disabled="saving" class="rounded-lg bg-emerald-950 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60">{{ saving ? (hi ? 'सेव हो रहा है…' : 'Saving…') : (hi ? 'लेआउट सेव करें' : 'Save layout') }}</button>
                    <button type="button" @click="resetLayout" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ hi ? 'रीसेट' : 'Reset' }}</button>
                </div>
            </div>

            <div v-if="!blocks.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                {{ hi ? 'अभी कोई होमपेज सेक्शन नहीं है। कस्टम HTML ब्लॉक जोड़ें।' : 'No homepage sections found. Add a custom HTML block to get started.' }}
            </div>

            <article v-for="(block, index) in blocks" :key="block.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-start gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="flex w-7 shrink-0 flex-col items-center text-slate-500">
                        <button type="button" @click="move(index, -1)" :disabled="index === 0" class="text-lg leading-5 hover:text-slate-950 disabled:opacity-30" aria-label="Move up">▲</button>
                        <span class="py-1 text-xs">{{ index + 1 }}</span>
                        <button type="button" @click="move(index, 1)" :disabled="index === blocks.length - 1" class="text-lg leading-5 hover:text-slate-950 disabled:opacity-30" aria-label="Move down">▼</button>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-emerald-50 px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-900">{{ block.content.section_type || block.key.replace('section.', '') }}</span>
                            <h3 class="text-sm font-bold text-slate-900">{{ block.title || block.content.en.title || 'Untitled section' }}</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ block.content.en.description || (block.content.section_type === 'html' ? 'Custom HTML content block' : 'Homepage section') }}</p>
                    </div>
                    <label class="flex shrink-0 items-center gap-2 text-xs font-medium text-slate-700">
                        <input v-model="block.is_published" type="checkbox" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        {{ hi ? 'दिखाएँ' : 'Visible' }}
                    </label>
                </div>

                <div class="space-y-3 px-4 py-4 sm:px-11">
                    <template v-if="block.content.section_type === 'hero'">
                        <label class="block text-xs font-medium text-slate-600">{{ hi ? 'शीर्षक / हेडिंग' : 'Title / Heading' }}<input v-model="block.content.en.title" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                        <label class="block text-xs font-medium text-slate-600">{{ hi ? 'सबटाइटल / विवरण' : 'Subtitle / Description' }}<textarea v-model="block.content.en.description" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea></label>
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="block text-xs font-medium text-slate-600">Primary CTA label<input v-model="block.content.en.cta_primary" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block text-xs font-medium text-slate-600">Primary CTA URL<input v-model="block.content.en.cta_primary_url" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block text-xs font-medium text-slate-600">Secondary CTA label<input v-model="block.content.en.cta_secondary" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block text-xs font-medium text-slate-600">Secondary CTA URL<input v-model="block.content.en.cta_secondary_url" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                        </div>
                        <label class="block text-xs font-medium text-slate-600">Background image URL<input v-model="block.content.en.image_url" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                    </template>
                    <template v-else-if="block.content.section_type === 'html'">
                        <label class="block text-xs font-medium text-slate-600">Section heading<input v-model="block.content.en.title" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                        <label class="block text-xs font-medium text-slate-600">Custom HTML<textarea v-model="block.content.en.html" rows="8" spellcheck="false" class="mt-1 w-full rounded-md border-slate-300 font-mono text-xs"></textarea></label>
                        <p class="text-xs text-slate-500">Use semantic HTML such as headings, paragraphs, lists and links. Script tags and inline event handlers should not be added.</p>
                    </template>
                    <template v-else>
                        <label class="block text-xs font-medium text-slate-600">Title / Heading<input v-model="block.content.en.title" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                        <label class="block text-xs font-medium text-slate-600">Subtitle / Description<textarea v-model="block.content.en.description" rows="3" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea></label>
                    </template>

                    <details class="rounded-lg border border-slate-200 p-3">
                        <summary class="cursor-pointer text-xs font-bold text-slate-700">हिंदी सामग्री / Hindi content</summary>
                        <div class="mt-3 space-y-3">
                            <label class="block text-xs font-medium text-slate-600">शीर्षक<input v-model="block.content.hi.title" class="mt-1 w-full rounded-md border-slate-300 text-sm"></label>
                            <label v-if="block.content.section_type !== 'html'" class="block text-xs font-medium text-slate-600">विवरण<textarea v-model="block.content.hi.description" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea></label>
                            <label v-else class="block text-xs font-medium text-slate-600">HTML<textarea v-model="block.content.hi.html" rows="6" spellcheck="false" class="mt-1 w-full rounded-md border-slate-300 font-mono text-xs"></textarea></label>
                        </div>
                    </details>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <button type="button" @click="deleteBlock(block)" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">{{ hi ? 'हटाएँ' : 'Delete block' }}</button>
                        <span class="mr-auto text-xs text-slate-500">Section ID: {{ block.key }}</span>
                        <button type="button" @click="saveBlock(block)" class="rounded-lg border border-emerald-800 px-4 py-2 text-xs font-bold text-emerald-950 hover:bg-emerald-50">{{ hi ? 'सेक्शन सामग्री सेव करें' : 'Save section content' }}</button>
                    </div>
                </div>
            </article>
        </div>
    </PortalLayout>
</template>
