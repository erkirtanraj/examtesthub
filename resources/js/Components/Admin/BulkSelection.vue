<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] },
    resource: { type: String, required: true },
    storageKey: { type: String, required: true },
});
const selectedIds = ref([]);
const pageIds = computed(() => props.items.map(item => Number(item.id)).filter(id => Number.isInteger(id) && id > 0));
const allOnPage = computed(() => pageIds.value.length > 0 && pageIds.value.every(id => selectedIds.value.includes(id)));
const someOnPage = computed(() => pageIds.value.some(id => selectedIds.value.includes(id)));

onMounted(() => {
    try {
        const saved = JSON.parse(sessionStorage.getItem(props.storageKey) || '[]');
        selectedIds.value = Array.isArray(saved) ? [...new Set(saved.map(Number).filter(id => Number.isInteger(id) && id > 0))] : [];
    } catch { selectedIds.value = []; }
});
watch(selectedIds, value => {
    try { sessionStorage.setItem(props.storageKey, JSON.stringify(value)); } catch { /* Browser storage may be disabled. */ }
}, { deep: true });

function isSelected(id) { return selectedIds.value.includes(Number(id)); }
function toggle(id, checked) {
    const numericId = Number(id);
    if (!Number.isInteger(numericId) || numericId <= 0) return;
    const next = new Set(selectedIds.value);
    if (checked) next.add(numericId); else next.delete(numericId);
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
defineExpose({ selectedIds, isSelected, toggle, allOnPage, someOnPage, togglePage, clear });
</script>

<template>
    <div class="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50/70 px-3 py-2">
        <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <input type="checkbox" :checked="allOnPage" :indeterminate="someOnPage && !allOnPage" :disabled="!pageIds.length" @change="togglePage($event.target.checked)" class="rounded border-slate-300 text-indigo-600">
            Select page
        </label>
        <span class="text-xs text-slate-600">{{ selectedIds.length }} selected across pages</span>
        <button type="button" @click="removeSelected" :disabled="!selectedIds.length" class="ml-auto rounded-md bg-rose-700 px-3 py-1.5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Delete selected</button>
        <button v-if="selectedIds.length" type="button" @click="clear" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">Clear</button>
    </div>
</template>
