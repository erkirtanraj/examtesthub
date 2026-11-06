<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { ArrowLeft, Download, Printer, BookOpen, Target, Trophy, Activity } from 'lucide-vue-next';
const props=defineProps({student:Object,summary:Object,recent_attempts:Array,subject_performance:Array,courses:Array,test_series:Array});
const page=usePage();const hi=computed(()=>page.props.locale==='hi');
function percent(v){return v===null||v===undefined?'—':Number(v).toFixed(1)+'%';}
function date(v){return v?new Date(v).toLocaleDateString(hi.value?'hi-IN':'en-IN',{day:'2-digit',month:'short',year:'numeric'}):'—';}
function print(){window.print();}
</script>
<template>
<PortalLayout portal="admin" :title="hi?'विद्यार्थी रिपोर्ट':'Student Report'" :subtitle="student.name+' · '+student.email">
<div class="space-y-5">
<div class="no-print flex flex-wrap justify-between gap-2"><a href="/admin/students" class="inline-flex items-center gap-2 rounded-xl border bg-white px-3 py-2 text-sm font-bold"><ArrowLeft :size="15"/> {{hi?'विद्यार्थियों पर वापस':'Back to students'}}</a><button @click="print" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white"><Printer :size="15"/> {{hi?'प्रिंट / PDF':'Print / Save PDF'}}</button></div>
<section id="admin-student-report" class="space-y-5 rounded-2xl border bg-white p-4 sm:p-6">
<header class="border-b pb-4"><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">ExamTestHub · Admin Report</p><h2 class="mt-1 text-2xl font-black">{{student.name}}</h2><p class="mt-1 text-sm text-slate-500">{{student.email}} · {{student.is_active?(hi?'सक्रिय खाता':'Active account'):(hi?'निष्क्रिय खाता':'Inactive account')}}</p><p class="mt-1 text-xs text-slate-500">{{hi?'पंजीकरण':'Registered'}}: {{date(student.created_at)}} · {{hi?'अंतिम परीक्षा':'Last exam'}}: {{date(summary.last_exam_at)}}</p></header>
<section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
<article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><BookOpen :size="15"/>{{hi?'परीक्षा प्रयास':'Exam attempts'}}</div><p class="mt-2 text-2xl font-black">{{summary.attempts}}</p></article>
<article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Target :size="15"/>{{hi?'औसत स्कोर':'Average score'}}</div><p class="mt-2 text-2xl font-black">{{percent(summary.average_percentage)}}</p></article>
<article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Trophy :size="15"/>{{hi?'सर्वश्रेष्ठ स्कोर':'Best score'}}</div><p class="mt-2 text-2xl font-black">{{percent(summary.best_percentage)}}</p></article>
<article class="rounded-xl border p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Activity :size="15"/>{{hi?'निष्क्रिय दिन':'Days since exam'}}</div><p class="mt-2 text-2xl font-black">{{summary.inactive_days??'—'}}</p></article>
</section>
<section><h3 class="font-extrabold">{{hi?'विषय-वार प्रदर्शन':'Subject performance'}}</h3><div v-if="subject_performance.length" class="mt-2 overflow-x-auto"><table class="w-full min-w-[480px] text-left text-sm"><thead class="border-b text-xs text-slate-500"><tr><th class="py-2">{{hi?'विषय':'Subject'}}</th><th class="py-2">{{hi?'उत्तर दिए':'Answered'}}</th><th class="py-2">{{hi?'सही':'Correct'}}</th><th class="py-2">{{hi?'सटीकता':'Accuracy'}}</th></tr></thead><tbody><tr v-for="s in subject_performance" :key="s.subject" class="border-b"><td class="py-2 font-semibold">{{s.subject}}</td><td class="py-2">{{s.answered}} / {{s.responses}}</td><td class="py-2">{{s.correct}}</td><td class="py-2 font-bold">{{percent(s.accuracy)}}</td></tr></tbody></table></div><p v-else class="mt-2 text-sm text-slate-500">{{hi?'कोई विषय डेटा उपलब्ध नहीं।':'No subject response data.'}}</p></section>
<section><h3 class="font-extrabold">{{hi?'हाल के परीक्षा परिणाम':'Recent exam results'}}</h3><div v-if="recent_attempts.length" class="mt-2 overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="border-b text-xs text-slate-500"><tr><th class="py-2">{{hi?'परीक्षा':'Exam'}}</th><th class="py-2">{{hi?'स्कोर':'Score'}}</th><th class="py-2">{{hi?'प्रतिशत':'Percentage'}}</th><th class="py-2">{{hi?'सही / गलत / खाली':'Correct / Incorrect / Blank'}}</th><th class="py-2">{{hi?'तिथि':'Date'}}</th></tr></thead><tbody><tr v-for="a in recent_attempts" :key="a.id" class="border-b"><td class="py-2 font-semibold">{{a.exam}}</td><td class="py-2">{{a.score}} / {{a.total_marks}}</td><td class="py-2 font-bold">{{percent(a.percentage)}}</td><td class="py-2">{{a.correct}} / {{a.incorrect}} / {{a.unanswered}}</td><td class="py-2">{{date(a.submitted_at)}}</td></tr></tbody></table></div><p v-else class="mt-2 text-sm text-slate-500">{{hi?'कोई सबमिट परीक्षा नहीं।':'No submitted exams.'}}</p></section>
<section><h3 class="font-extrabold">{{hi?'कोर्स नामांकन':'Course enrollments'}}</h3><div v-if="courses.length" class="mt-2 grid gap-2 sm:grid-cols-2"><article v-for="c in courses" :key="c.title" class="rounded-xl border p-3"><p class="font-semibold">{{c.title}}</p><p class="mt-1 text-xs text-slate-500">{{c.status}} · {{c.completed_lessons}} {{hi?'पूर्ण पाठ':'completed lessons'}}</p></article></div><p v-else class="mt-2 text-sm text-slate-500">{{hi?'कोई कोर्स नामांकन नहीं।':'No course enrollments.'}}</p></section>
<section><h3 class="font-extrabold">{{hi?'टेस्ट सीरीज़ नामांकन':'Test-series enrollments'}}</h3><div v-if="test_series.length" class="mt-2 grid gap-2 sm:grid-cols-2"><article v-for="s in test_series" :key="s.title" class="rounded-xl border p-3"><p class="font-semibold">{{s.title}}</p><p class="mt-1 text-xs text-slate-500">{{s.status}} · {{date(s.enrolled_at)}}</p></article></div><p v-else class="mt-2 text-sm text-slate-500">{{hi?'कोई टेस्ट सीरीज़ नामांकन नहीं।':'No test-series enrollments.'}}</p></section>
<footer class="border-t pt-3 text-xs text-slate-400">ExamTestHub · {{hi?'केवल प्रशासनिक उपयोग के लिए':'For authorized administrative use'}}</footer>
</section>
</div>
</PortalLayout>
</template>
<style>
@media print{@page{size:A4;margin:12mm}body *{visibility:hidden!important}#admin-student-report,#admin-student-report *{visibility:visible!important}#admin-student-report{position:absolute;inset:0 auto auto 0;width:100%;border:0!important;padding:0!important}.no-print{display:none!important}tr{page-break-inside:avoid}}
</style>
