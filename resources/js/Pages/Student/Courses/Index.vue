<script setup>
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
const props=defineProps({courses:Object,mine:{type:Boolean,default:false}});
const page=usePage(); const hi=computed(()=>page.props.locale==='hi'); const couponCodes=reactive({});
function enroll(course){if(course.access_type==='paid'){const coupon=(couponCodes[course.id]||'').trim();router.post('/student/courses/'+course.slug+'/checkout',coupon?{coupon_code:coupon}:{});return;}router.post('/student/courses/'+course.slug+'/enroll');}
</script>
<template>
<PortalLayout portal="student" :title="mine?(hi?'मेरे कोर्स':'My courses'):(hi?'कोर्स कैटलॉग':'Course catalogue')" :subtitle="mine?(hi?'अपने नामांकित कोर्स और प्रगति देखें।':'Continue your enrolled courses and learning progress.'):(hi?'अपने कौशल और परीक्षा की तैयारी के लिए कोर्स चुनें।':'Explore structured courses, lessons and learning resources.')">
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
<article v-for="course in courses.data" :key="course.id" class="flex min-w-0 flex-col rounded-2xl border bg-white p-5">
<div class="flex items-start justify-between gap-3"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">{{course.category||'Course'}}</span><span class="text-sm font-extrabold">{{course.access_type==='paid'?'₹ '+course.price:(hi?'मुफ्त':'FREE')}}</span></div>
<h2 class="mt-3 text-lg font-extrabold">{{hi&&course.title_hi?course.title_hi:course.title}}</h2><p class="mt-2 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{hi&&course.description_hi?course.description_hi:course.description}}</p>
<p class="mt-3 text-xs text-slate-500">{{course.sections_count}} {{hi?'सेक्शन':'sections'}}</p>
<div class="mt-4 border-t pt-3"><label v-if="course.access_type==='paid' && !['active','completed'].includes(course.enrollment?.status)" class="mb-2 block text-xs font-semibold text-slate-600">{{hi?'कूपन कोड (वैकल्पिक)':'Coupon code (optional)'}}<input v-model="couponCodes[course.id]" maxlength="40" autocomplete="off" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal uppercase outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" :placeholder="hi?'कूपन कोड दर्ज करें':'Enter coupon code'" /></label><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-xs font-semibold capitalize text-slate-600">{{course.enrollment?.status||'Not enrolled'}}</span><a v-if="course.enrollment?.status==='active'||course.enrollment?.status==='completed'" :href="'/student/courses/'+course.slug" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">Continue</a><button v-else @click="enroll(course)" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">{{course.access_type==='paid'?'Buy course':'Enroll free'}}</button></div>
</article>
<p v-if="!courses.data.length" class="rounded-xl border bg-white p-8 text-center text-slate-500 sm:col-span-2 xl:col-span-3">No courses are available right now.</p>
</div>
</PortalLayout>
</template>
