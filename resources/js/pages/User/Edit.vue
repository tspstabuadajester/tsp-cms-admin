<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAvatarUrl } from '@/composables/useAvatarUrl';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type BusinessOption, type RoleOption, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    user: Pick<User, 'id' | 'name' | 'email' | 'avatar' | 'status' | 'business_id'> & { role?: string | null };
    businesses: BusinessOption[];
    roles: RoleOption[];
    roleEditable: boolean;
}>();

const formatRoleName = (name: string) =>
    name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const selectClass =
    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/user',
    },
    {
        title: 'Edit',
        href: `/user/${props.user.id}/edit`,
    },
];

const { getInitials } = useInitials();
const { avatarUrl } = useAvatarUrl();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    business_id: (props.user.business_id ?? '') as string | number,
    role: props.roleEditable ? (props.user.role ?? '') : '',
    status: props.user.status ?? 'active',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('user.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="flex flex-col gap-6">
            <HeadingSmall title="Edit user" description="Update user details" />

            <div class="flex items-center gap-4">
                <Avatar class="size-16 overflow-hidden rounded-full">
                    <AvatarImage
                        v-if="avatarUrl(user.avatar)"
                        :src="avatarUrl(user.avatar)!"
                        :alt="user.name"
                    />
                    <AvatarFallback class="rounded-full text-lg">
                        {{ getInitials(form.name || user.name) }}
                    </AvatarFallback>
                </Avatar>
                <p class="text-sm text-muted-foreground">Avatar updates automatically when the name is changed.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Full name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="business_id">Business</Label>
                    <select id="business_id" v-model="form.business_id" :class="selectClass">
                        <option value="">No business</option>
                        <option v-for="business in businesses" :key="business.id" :value="business.id">
                            {{ business.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.business_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <select
                        v-if="roleEditable"
                        id="role"
                        v-model="form.role"
                        required
                        :class="selectClass"
                    >
                        <option value="" disabled>Select a role</option>
                        <option v-for="role in roles" :key="role.name" :value="role.name">
                            {{ formatRoleName(role.name) }}
                        </option>
                    </select>
                    <Input
                        v-else
                        id="role"
                        type="text"
                        :model-value="formatRoleName(user.role ?? 'super-admin')"
                        disabled
                    />
                    <p v-if="!roleEditable" class="text-sm text-muted-foreground">
                        Super admin role cannot be changed from this form.
                    </p>
                    <InputError :message="form.errors.role" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <select id="status" v-model="form.status" :class="selectClass">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Leave blank to keep current password"
                    />
                    <InputError :message="form.errors.password" />
                    <p class="text-sm text-muted-foreground">
                        Leave blank to keep the current password. Otherwise, must be at least 8 characters and include one number and one special character.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Update User
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('user')">Cancel</Link>
                    </Button>
                </div>
            </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
