<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({ page: { type: Object, required: true } });
const pageState = usePage();
const locale = computed(() => pageState.props.locale || 'en');
const body = computed(() => props.page.content?.[locale.value] || props.page.content?.en || '');
</script>

<template>
    <Head :title="pageState.props.seo?.title || page.title">
        <meta head-key="description" name="description" :content="pageState.props.seo?.description || ''" />
        <link head-key="canonical" rel="canonical" :href="pageState.props.seo?.canonical || ''" />
    </Head>
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-5">
                <a href="/" class="text-xl font-extrabold">ExamTest<span class="text-indigo-600">Hub</span></a>
                <a href="/" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">{{ locale === 'hi' ? 'होम' : 'Home' }}</a>
            </div>
        </header>
        <article class="mx-auto max-w-4xl px-5 py-12 sm:py-16">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <h1 class="text-3xl font-black tracking-tight text-slate-950">{{ page.title }}</h1>
                <div class="mt-7 whitespace-pre-wrap text-base leading-8 text-slate-700">{{ body }}</div>
            </div>
        </article>
        <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">© {{ new Date().getFullYear() }} ExamTestHub</footer>
    </main>
</template>
