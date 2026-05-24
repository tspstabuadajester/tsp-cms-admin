<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

defineProps<{
    users: User[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User',
        href: '/user',
    },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between gap-4">
                <HeadingSmall title="Users" description="Manage all users in the system" />
                <Button as-child>
                    <Link :href="route('user.create')">
                        <Plus class="size-4" />
                        Create User
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Verified</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.length === 0">
                            <td colspan="4" class="p-4 text-center text-muted-foreground">No users found.</td>
                        </tr>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">{{ user.name }}</td>
                            <td class="p-4 align-middle">{{ user.email }}</td>
                            <td class="p-4 align-middle">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        user.email_verified_at
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                                    ]"
                                >
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
