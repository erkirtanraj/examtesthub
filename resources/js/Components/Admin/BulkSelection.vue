<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] },
    resource: { type: String, required: true },
    storageKey: { type: String, required: true },
    labelKey: { type: String, default: 'title' },
});
const selectedIds = ref([]);
const pageIds = computed(() => props.items.map(item => Number(item.id)).filter(id => Number.isInteger(id) && id > 0));
const allOnPage = computed(() => pageIds.value.length > 0 && pageIds.value.every(id => selectedIds.value.includes(id)));

onMounted(() => {
    try {
        const saved = JSON.parse(sessionStorage.getItem(props.storageKey) || '[]');
        selectedIds.value = Array.isArray(saved) ? [...new Set(saved.map(Number).filter(id => Number.isInteger(id) && id > 0))] : [];
    } catch { selectedIds.value = []; }
});
watch(selectedIds, value => {
    try { sessionStorage.setItem(props.storageKey, JSON.stringify(value)); } catch { /* Browser storage may be disabled. */ }
}, { deep: true });

function label(item) {
    return String(item?.[props.labelKey] ?? item?.title ?? item?.name ?? item?.prompt ?? ('Record #' + item.id)).replace(/\s+/g, ' ').slice(0, 100);
}
function toggle(id, checked) {
    const next = new Set(selectedIds.value);
    if (checked) next.add(Number(id)); else next.delete(Number(id));
    selectedIds.value = [...next];
}
function togglePage(checked) {
    const next = new Set(selectedIds.value);
    pageIds.value.forEach(id => checked ? next.add(id) : next.delete(id));
    selectedIds.value = [...next];
}
function clear() {
    selectedIds.value = [];
    try { sessionStorage.removeItem(props.storageKey); } catch { /* Browser storage may be disabled. */ }
}
function removeSelected() {
    if (!selectedIds.value.length) return;
    const count = selectedIds.value.length;
    if (!window.confirm('Permanently delete ' + count + ' selected record(s)? This cannot be undone. Records with active dependencies will be protected.')) return;
    router.post('/admin/bulk-delete/' + props.resource, { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: clear,
    });
}
</script>

<template>
    <section class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <input type="checkbox" :checked="allOnPage" :indeterminate="selectedIds.some(id => pageIds.includes(id)) && !allOnPage" :disabled="!pageIds.length" @change="togglePage($event.target.checked)" class="rounded border-slate-300 text-indigo-600">
                Select this page
            </label>
            <span class="text-xs text-slate-600">{{ selectedIds.length }} selected across pages</span>
            <button type="button" @click="removeSelected" :disabled="!selectedIds.length" class="ml-auto rounded-lg bg-rose-700 px-3 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Delete selected</button>
            <button v-if="selectedIds.length" type="button" @click="clear" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Clear selection</button>
        </div>
        <div v-if="items.length" class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            <label v-for="item in items" :key="item.id" class="flex min-w-0 items-start gap-2 rounded-lg border border-white bg-white/80 px-3 py-2 text-xs text-slate-700">
                <input type="checkbox" :checked="selectedIds.includes(Number(item.id))" @change="toggle(item.id, $event.target.checked)" class="mt-0.5 rounded border-slate-300 text-indigo-600">
                <span class="min-w-0 break-words">{{ label(item) }} <span class="text-slate-400">#{{ item.id }}</span></span>
            </label>
        </div>
        <p v-else class="mt-2 text-xs text-slate-500">No records on this page.</p>
        <p class="mt-2 text-[11px] text-slate-500">Selections are preserved while you move between pages in this browser tab. Only explicitly selected IDs are submitted.</p>
    </section>
</template>
