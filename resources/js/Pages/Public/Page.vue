<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';

const props = defineProps({ page: { type: Object, required: true } });
const locale = computed(() => usePage().props.locale || 'en');
const body = computed(() => props.page.content?.[locale.value] || props.page.content?.en || '');
</script>

<template>
    <PortalLayout portal="student" :title="page.title" :subtitle="''">
        <article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="prose prose-slate max-w-none whitespace-pre-wrap leading-8">{{ body }}</div>
        </article>
    </PortalLayout>
</template>
