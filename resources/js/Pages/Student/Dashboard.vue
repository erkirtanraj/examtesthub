<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../Layouts/PortalLayout.vue';
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
</script>
<template>
    <PortalLayout portal="student" :title="hi ? 'विद्यार्थी डैशबोर्ड' : 'Student dashboard'" :subtitle="hi ? 'आपकी पढ़ाई और परीक्षा की तैयारी का व्यक्तिगत स्थान।' : 'Your personal space for learning and examination preparation.'">
        <div class="rounded-2xl bg-gradient-to-r from-indigo-700 to-violet-700 p-6 text-white">
            <p class="text-sm font-semibold text-indigo-100">{{ hi ? 'आपका लर्निंग स्पेस' : 'YOUR LEARNING SPACE' }}</p>
            <h2 class="mt-2 text-2xl font-extrabold">{{ hi ? 'नमस्ते' : 'Hello' }}, {{ page.props.auth?.user?.name }}!</h2>
            <p class="mt-2 text-sm text-indigo-100">{{ hi ? 'आपका डैशबोर्ड तैयार है। जल्द ही यहाँ आपके कोर्स और टेस्ट दिखाई देंगे।' : 'Your dashboard is ready. Your enrolled courses and available tests will appear here as those modules are released.' }}</p>
        </div>
        <div id="learning" class="mt-6 grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-indigo-600">{{ hi ? 'मेरे कोर्स' : 'MY COURSES' }}</div>
                <h3 class="mt-2 text-xl font-extrabold text-slate-900">{{ hi ? 'अभी कोई कोर्स नहीं' : 'No courses yet' }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ hi ? 'कोर्स एनरोलमेंट मॉड्यूल बनने के बाद आपके कोर्स यहाँ दिखेंगे।' : 'Your enrolled courses will appear here when course enrollment is implemented.' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-indigo-600">{{ hi ? 'मेरी टेस्ट सीरीज़' : 'MY TEST SERIES' }}</div>
                <h3 class="mt-2 text-xl font-extrabold text-slate-900">{{ hi ? 'अभी कोई टेस्ट नहीं' : 'No tests available yet' }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ hi ? 'टेस्ट सीरीज़ प्रकाशित होने पर यहाँ दिखाई देंगी।' : 'Published test series will appear here when the examination module is implemented.' }}</p>
            </article>
        </div>
    </PortalLayout>
</template>
