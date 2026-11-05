<script setup>
import { computed, ref, reactive } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PortalLayout from '../../../Layouts/PortalLayout.vue';
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
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
<section class="space-y-4">
<div class="flex flex-wrap gap-2 rounded-xl border bg-white p-4">
<input v-model="search" @keyup.enter="filter" placeholder="Search name or email" class="min-w-48 flex-1 rounded-lg border px-3 py-2 text-sm">
<select v-model="roleFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All roles</option><option v-for="role in roles" :key="role.id" :value="role.slug">{{ role.name }}</option></select>
<select v-model="statusFilter" class="rounded-lg border px-3 py-2 text-sm"><option value="">All status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
<button @click="filter" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Filter</button>
<a href="/admin/users/export" class="rounded-lg border px-4 py-2 text-sm font-semibold">Export CSV</a>
</div>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">User</th><th class="p-3">Roles</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead>
<tbody><tr v-for="user in users.data" :key="user.id" class="border-t">
<td class="p-3"><a :href="'/admin/users/' + user.id" class="font-bold text-indigo-700 hover:underline">{{ user.name }}</a><div class="text-xs text-slate-500">{{ user.email }}</div></td>
<td class="p-3"><span v-for="role in user.roles" :key="role.id" class="mr-1 inline-block rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-700">{{ role.slug }}</span></td>
<td class="p-3"><span :class="user.is_active ? 'text-emerald-700' : 'text-rose-700'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
<td class="space-x-2 p-3"><button @click="edit(user)" class="font-semibold text-indigo-700">Edit</button><button @click="toggle(user)" :disabled="user.roles.some(r => r.slug === 'super-admin')" class="font-semibold text-rose-700 disabled:opacity-30">{{ user.is_active ? 'Deactivate' : 'Activate' }}</button></td>
</tr><tr v-if="!users.data.length"><td colspan="4" class="p-6 text-center text-slate-500">No users found.</td></tr></tbody></table>
</div>
<div class="flex flex-wrap items-center gap-2 text-sm"><span class="mr-auto text-slate-500">Page {{ users.current_page }} of {{ users.last_page }} · {{ users.total }} users</span><a v-for="link in users.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['rounded border px-3 py-1', link.active ? 'bg-indigo-600 text-white' : 'bg-white', !link.url ? 'pointer-events-none opacity-40' : '']"/></div>
<div class="rounded-xl border bg-white p-4"><h3 class="font-bold">CSV import</h3><p class="mt-1 text-xs text-slate-500">Headers: name,email,password,role,preferred_locale. Passwords must meet the 12-character policy. Super Admin cannot be imported.</p><form @submit.prevent="importCsv" class="mt-3 flex flex-wrap items-center gap-3"><input type="file" accept=".csv,.txt" @input="importForm.file = $event.target.files[0]" class="text-sm"><button :disabled="importForm.processing" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Import CSV</button></form><p v-if="importForm.errors.file" class="mt-2 text-sm text-rose-600">{{ importForm.errors.file }}</p></div>
</section>
<aside class="h-fit rounded-xl border bg-white p-5"><h2 class="text-lg font-extrabold">{{ editing ? 'Edit user' : 'Create user' }}</h2>
<form @submit.prevent="submit" class="mt-4 space-y-3">
<label class="block text-sm font-semibold">Name<input v-model="form.name" required maxlength="120" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
<label class="block text-sm font-semibold">Email<input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
<label v-if="!editing" class="block text-sm font-semibold">Temporary password<input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border px-3 py-2"><small class="text-slate-500">At least 12 characters, mixed case, number and symbol.</small></label>
<label class="block text-sm font-semibold">Language<select v-model="form.preferred_locale" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="en">English</option><option value="hi">Hindi</option></select></label>
<div><p class="mb-2 text-sm font-semibold">Roles</p><label v-for="role in roles" :key="role.id" class="mb-2 flex items-center gap-2 text-sm"><input v-model="form.roles" type="checkbox" :value="role.slug" :disabled="role.slug === 'super-admin' && !page.props.auth?.user?.roles?.includes('super-admin')">{{ role.name }}</label></div>
<div v-for="(error,key) in form.errors" :key="key" class="text-xs text-rose-600">{{ error }}</div>
<div class="flex gap-2"><button :disabled="form.processing" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 font-bold text-white">{{ editing ? 'Save changes' : 'Create user' }}</button><button v-if="editing" type="button" @click="editing=null;form.reset()" class="rounded-lg border px-3">Cancel</button></div>
</form></aside>
</div>
</PortalLayout>
</template>
