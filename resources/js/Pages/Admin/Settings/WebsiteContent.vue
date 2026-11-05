<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';

const props = defineProps({ items: { type: Array, default: () => [] } });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const activeType = ref('home');
const editingId = ref(null);
const blank = () => ({ type: activeType.value, key: '', title: '', slug: '', content: { en: '', hi: '' }, is_published: true, sort_order: 0 });
const form = useForm(blank());
const types = [
    { value: 'home', en: 'Homepage text', hi: 'होमपेज टेक्स्ट' },
    { value: 'menu', en: 'Menu links', hi: 'मेन्यू लिंक' },
    { value: 'footer', en: 'Footer content', hi: 'फुटर सामग्री' },
    { value: 'contact', en: 'Contact options', hi: 'संपर्क विकल्प' },
    { value: 'legal', en: 'Legal / T&C', hi: 'कानूनी / नियम एवं शर्तें' },
    { value: 'page', en: 'Custom pages', hi: 'कस्टम पेज' },
];
const filtered = computed(() => props.items.filter(item => item.type === activeType.value));
const success = computed(() => page.props.flash?.success || '');
function reset() { editingId.value = null; form.defaults(blank()); form.reset(); }
function edit(item) {
    editingId.value = item.id;
    form.type = item.type; form.key = item.key || ''; form.title = item.title || '';
    form.slug = item.slug || ''; form.content = item.content || { en: '', hi: '' };
    form.is_published = !!item.is_published; form.sort_order = item.sort_order || 0;
}
function save() {
    const url = editingId.value ? '/admin/settings/website-content/' + editingId.value : '/admin/settings/website-content';
    form.transform(data => ({ ...data, type: activeType.value })).submit(editingId.value ? 'put' : 'post', url, { preserveScroll: true, onSuccess: reset });
}
function remove(item) {
    if (confirm(hi.value ? 'क्या आप यह सामग्री हटाना चाहते हैं?' : 'Delete this content item?')) {
        router.delete('/admin/settings/website-content/' + item.id, { preserveScroll: true });
    }
}
</script>

<template>
    <PortalLayout portal="admin" :title="hi ? 'वेबसाइट कंटेंट मैनेजर' : 'Website Content Manager'" :subtitle="hi ? 'होमपेज, मेन्यू, फुटर, पेज, कानूनी और संपर्क सामग्री प्रबंधित करें।' : 'Manage homepage copy, navigation, footer, custom pages, legal text and contact options.'">
        <div class="space-y-5">
            <div v-if="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ success }}</div>
            <div class="flex flex-wrap gap-2">
                <button v-for="type in types" :key="type.value" @click="activeType = type.value; reset()" class="rounded-xl border px-4 py-2 text-sm font-semibold" :class="activeType === type.value ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">{{ hi ? type.hi : type.en }}</button>
            </div>
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,.85fr)]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3"><h2 class="font-bold text-slate-900">{{ hi ? 'मौजूदा सामग्री' : 'Existing content' }}</h2><span class="text-xs text-slate-500">{{ filtered.length }} {{ hi ? 'आइटम' : 'items' }}</span></div>
                    <div v-if="!filtered.length" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">{{ hi ? 'इस सेक्शन में अभी कोई सामग्री नहीं है। दाईं ओर से जोड़ें।' : 'No content in this section yet. Add your first item using the editor.' }}</div>
                    <div v-else class="divide-y divide-slate-100">
                        <article v-for="item in filtered" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0">
                            <div class="min-w-0"><p class="font-semibold text-slate-800">{{ item.title || item.key || item.slug }}</p><p class="mt-1 text-xs text-slate-500">{{ item.key || item.slug || item.type }} · {{ item.is_published ? (hi ? 'प्रकाशित' : 'Published') : (hi ? 'ड्राफ्ट' : 'Draft') }}</p></div>
                            <div class="flex gap-2"><button @click="edit(item)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-50">{{ hi ? 'एडिट' : 'Edit' }}</button><button @click="remove(item)" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">{{ hi ? 'हटाएँ' : 'Delete' }}</button></div>
                        </article>
                    </div>
                </section>
                <form @submit.prevent="save" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="font-bold text-slate-900">{{ editingId ? (hi ? 'सामग्री अपडेट करें' : 'Edit content') : (hi ? 'नई सामग्री जोड़ें' : 'Add content') }}</h2><button v-if="editingId" type="button" @click="reset" class="text-xs font-semibold text-slate-500 hover:text-slate-900">{{ hi ? 'रद्द करें' : 'Cancel edit' }}</button></div>
                    <label class="block text-sm font-semibold text-slate-700">{{ hi ? 'आंतरिक की (Key)' : 'Internal key' }}<input v-model="form.key" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="hero.title / footer.contact"></label>
                    <label v-if="['page','legal'].includes(activeType)" class="block text-sm font-semibold text-slate-700">{{ hi ? 'पेज शीर्षक' : 'Page title' }}<input v-model="form.title" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
                    <label v-if="['page','legal'].includes(activeType)" class="block text-sm font-semibold text-slate-700">URL slug<input v-model="form.slug" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="about-us"></label>
                    <label class="block text-sm font-semibold text-slate-700">English<textarea v-model="form.content.en" rows="4" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="English content"></textarea></label>
                    <label class="block text-sm font-semibold text-slate-700">हिंदी<textarea v-model="form.content.hi" rows="4" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="हिंदी सामग्री"></textarea></label>
                    <label class="block text-sm font-semibold text-slate-700">{{ hi ? 'क्रम' : 'Display order' }}<input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input v-model="form.is_published" type="checkbox" class="rounded border-slate-300 text-indigo-600"> {{ hi ? 'सार्वजनिक रूप से प्रकाशित करें' : 'Publish publicly' }}</label>
                    <button :disabled="form.processing" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-60">{{ form.processing ? (hi ? 'सेव हो रहा है...' : 'Saving...') : (hi ? 'सेव करें' : 'Save content') }}</button>
                    <p v-if="Object.keys(form.errors).length" class="text-xs text-rose-600">{{ Object.values(form.errors).join(' ') }}</p>
                </form>
            </div>
        </div>
    </PortalLayout>
</template>
