<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { StatusBadge } from '@/components/StatusBadge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useAvatarUrl } from '@/composables/useAvatarUrl';
import { useInitials } from '@/composables/useInitials';
import { usePermissions } from '@/composables/usePermissions';
import { formatUserStatus, userStatusVariant } from '@/composables/useUserStatus';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { type BreadcrumbItem, type Paginated, type User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';

defineProps<{
    users: Paginated<User>;
}>();

const { getInitials } = useInitials();
const { avatarUrl } = useAvatarUrl();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
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
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout full-width>
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <HeadingSmall title="Users" description="Manage all users in the system" />
                    <Button v-if="can('settings.manage')" as-child>
                        <Link :href="route('users.create')">
                            <Plus class="size-4" />
                            Create User
                        </Link>
                    </Button>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="h-10 w-14 px-4 text-left align-middle font-medium text-muted-foreground">Avatar</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Created</th>
                                <th class="h-10 w-24 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="p-4 text-center text-muted-foreground">No users found.</td>
                            </tr>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    <Avatar class="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            v-if="avatarUrl(user.avatar)"
                                            :src="avatarUrl(user.avatar)!"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback class="rounded-full text-xs">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </td>
                                <td class="p-4 align-middle font-medium">{{ user.name }}</td>
                                <td class="p-4 align-middle">{{ user.email }}</td>
                                <td class="p-4 align-middle">
                                    <StatusBadge :variant="userStatusVariant(user.status)">
                                        {{ formatUserStatus(user.status) }}
                                    </StatusBadge>
                                </td>
                                <td class="p-4 align-middle text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                            <td class="p-4 align-middle text-right">
                                <Button v-if="can('settings.manage')" variant="outline" size="sm" as-child>
                                    <Link :href="route('users.edit', user.id)">
                                        <Pencil class="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                            </td>
                            </tr>
                        </tbody>
                    </table>
                    <PaginationLinks :pagination="users" />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
