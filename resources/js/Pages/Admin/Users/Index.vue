<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
import { Search, Download, Upload, UserPlus, Pencil, ShieldCheck, UserCheck, UserX } from 'lucide-vue-next';

const props = defineProps({ users: Object, roles: Array, filters: Object });
const page = usePage();
const hi = computed(() => page.props.locale === 'hi');
const editing = ref(null);
const form = useForm({ name: '', email: '', password: '', preferred_locale: 'en', roles: ['student'] });
const importForm = useForm({ file: null });
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const statusFilter = ref(props.filters?.status || '');
function submit() {
    if (editing.value) form.put('/admin/users/' + editing.value, { onSuccess: () => { editing.value = null; form.reset(); } });
    else form.post('/admin/users', { onSuccess: () => form.reset() });
}
function edit(user) {
    editing.value = user.id;
    form.name = user.name; form.email = user.email; form.password = '';
    form.preferred_locale = user.preferred_locale || 'en';
    form.roles = user.roles.map(role => role.slug);
}
function filter() { router.get('/admin/users', { search: search.value, role: roleFilter.value, status: statusFilter.value }, { preserveState: true, replace: true }); }
function toggle(user) {
    if (confirm((hi.value ? 'इस खाते की स्थिति बदलें?' : 'Change this account status?'))) router.patch('/admin/users/' + user.id + '/status');
}
function importCsv() { importForm.post('/admin/users/import', { forceFormData: true, onSuccess: () => importForm.reset() }); }
</script>

<template>
<PortalLayout portal="admin" :title="hi ? 'उपयोगकर्ता प्रबंधन' : 'User management'" :subtitle="hi ? 'खाते बनाएं, संपादित करें, भूमिकाएं दें और आयात/निर्यात करें।' : 'Create, edit, deactivate, search and transfer user accounts.'">
    <div class="space-y-5">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ hi ? 'खाता निर्देशिका' : 'ACCOUNT DIRECTORY' }}</p><h2 class="mt-1 text-lg font-extrabold text-slate-900">{{ hi ? 'उपयोगकर्ता खोजें' : 'Find and manage users' }}</h2></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ users.total }} {{ hi ? 'खाते' : 'accounts' }}</span></div>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_180px_160px_auto_auto]">
                <label class="relative block sm:col-span-2 xl:col-span-1"><Search :size="17" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/><input v-model="search" @keyup.enter="filter" :placeholder="hi ? 'नाम या ईमेल खोजें' : 'Search name or email'" class="w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm"></label>
                <select v-model="roleFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"><option value="">All roles</option><option v-for="role in roles" :key="role.id" :value="role.slug">{{ role.name }}</option></select>
                <select v-model="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"><option value="">All status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
                <button @click="filter" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">{{ hi ? 'फ़िल्टर' : 'Apply filters' }}</button>
                <a href="/admin/users/export" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><Download :size="16"/>Export CSV</a>
            </div>
        </section>

        <div class="grid min-w-0 gap-5 2xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="min-w-0 space-y-4">
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                    <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody><tr v-for="user in users.data" :key="user.id" class="border-t border-slate-100">
                            <td class="px-4 py-3.5"><div class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xs font-extrabold text-indigo-700">{{ user.name.split(/\s+/).slice(0,2).map(part=>part[0]).join('').toUpperCase() }}</span><span class="min-w-0"><a :href="'/admin/users/' + user.id" class="block truncate font-bold text-slate-900 hover:text-indigo-700">{{ user.name }}</a><span class="block truncate text-xs text-slate-500">{{ user.email }}</span></span></div></td>
                            <td class="px-4 py-3"><span v-for="role in user.roles" :key="role.id" class="mr-1 inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">{{ role.slug }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"><i class="h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"/>{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-4 py-3 text-right"><button @click="edit(user)" class="mr-3 inline-flex items-center gap-1.5 font-semibold text-indigo-700 hover:text-indigo-900"><Pencil :size="14"/>Edit</button><button @click="toggle(user)" :disabled="user.roles.some(r => r.slug === 'super-admin')" class="inline-flex items-center gap-1.5 font-semibold text-rose-700 disabled:opacity-30"><component :is="user.is_active ? UserX : UserCheck" :size="14"/>{{ user.is_active ? 'Deactivate' : 'Activate' }}</button></td>
                        </tr><tr v-if="!users.data.length"><td colspan="4" class="p-10 text-center text-slate-500">No users found.</td></tr></tbody>
                    </table></div>
                </div>

                <div class="grid gap-3 md:hidden">
                    <article v-for="user in users.data" :key="user.id" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-extrabold text-indigo-700">{{ user.name.split(/\s+/).slice(0,2).map(part=>part[0]).join('').toUpperCase() }}</span><span class="min-w-0 flex-1"><a :href="'/admin/users/' + user.id" class="block break-words font-bold text-slate-900">{{ user.name }}</a><span class="mt-0.5 block break-all text-xs text-slate-500">{{ user.email }}</span></span><span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></div>
                        <div class="mt-3 flex flex-wrap gap-1.5"><span v-for="role in user.roles" :key="role.id" class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">{{ role.slug }}</span></div>
                        <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3"><button @click="edit(user)" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-50 px-3 py-2.5 text-sm font-bold text-indigo-700"><Pencil :size="15"/>Edit</button><button @click="toggle(user)" :disabled="user.roles.some(r => r.slug === 'super-admin')" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-700 disabled:opacity-30"><component :is="user.is_active ? UserX : UserCheck" :size="15"/>{{ user.is_active ? 'Deactivate' : 'Activate' }}</button></div>
                    </article>
                    <p v-if="!users.data.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No users found.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 text-sm"><span class="mr-auto text-xs text-slate-500">Page {{ users.current_page }} of {{ users.last_page }} · {{ users.total }} users</span><a v-for="link in users.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['rounded-lg border px-3 py-2 font-semibold', link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600', !link.url ? 'pointer-events-none opacity-40' : '']"/></div>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700"><Upload :size="18"/></span><div><h3 class="font-extrabold text-slate-900">CSV import</h3><p class="mt-0.5 text-xs text-slate-500">Bulk-create accounts using the documented CSV headers.</p></div></div><p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">Headers: name,email,password,role,preferred_locale. Passwords must meet the 12-character policy. Super Admin cannot be imported.</p><form @submit.prevent="importCsv" class="mt-3 flex flex-wrap items-center gap-3"><input type="file" accept=".csv,.txt" @input="importForm.file = $event.target.files[0]" class="min-w-0 flex-1 text-sm"><button :disabled="importForm.processing" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50"><Upload :size="15"/>Import CSV</button></form><p v-if="importForm.errors.file" class="mt-2 text-sm text-rose-600">{{ importForm.errors.file }}</p></section>
            </section>

            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-24">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><UserPlus :size="19"/></span><div><p class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ editing ? 'EDIT ACCOUNT' : 'NEW ACCOUNT' }}</p><h2 class="text-lg font-extrabold text-slate-900">{{ editing ? 'Edit user' : 'Create user' }}</h2></div></div>
                <form @submit.prevent="submit" class="mt-5 space-y-3.5">
                    <label class="block text-sm font-semibold text-slate-700">Name<input v-model="form.name" required maxlength="120" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal"></label>
                    <label class="block text-sm font-semibold text-slate-700">Email<input v-model="form.email" type="email" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal"></label>
                    <label v-if="!editing" class="block text-sm font-semibold text-slate-700">Temporary password<input v-model="form.password" type="password" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal"><small class="font-normal text-slate-500">At least 12 characters, mixed case, number and symbol.</small></label>
                    <label class="block text-sm font-semibold text-slate-700">Language<select v-model="form.preferred_locale" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal"><option value="en">English</option><option value="hi">Hindi</option></select></label>
                    <div><p class="mb-2 text-sm font-semibold text-slate-700">Roles</p><div class="max-h-48 space-y-2 overflow-y-auto rounded-xl bg-slate-50 p-3"><label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm"><input v-model="form.roles" type="checkbox" :value="role.slug" :disabled="role.slug === 'super-admin' && !page.props.auth?.user?.roles?.includes('super-admin')">{{ role.name }}</label></div></div>
                    <div v-for="(error,key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</div>
                    <div class="flex gap-2"><button :disabled="form.processing" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 font-bold text-white disabled:opacity-50">{{ form.processing ? 'Saving…' : editing ? 'Save changes' : 'Create user' }}</button><button v-if="editing" type="button" @click="editing=null;form.reset()" class="rounded-xl border border-slate-200 px-3 font-semibold text-slate-600">Cancel</button></div>
                </form>
            </aside>
        </div>
    </div>
</PortalLayout>
</template>
