<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Businesses',
        href: '/business',
    },
    {
        title: 'Create',
        href: '/business/create',
    },
];

const form = useForm({
    name: '',
    address: '',
    phone: '',
    email: '',
});

const submit = () => {
    form.post(route('business.store'));
};
</script>

<template>
    <Head title="Create Business" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="flex flex-col gap-6">
                <HeadingSmall title="Create business" description="Add a new business to the system" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            v-model="form.name"
                            placeholder="Business name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            placeholder="Business address"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" type="text" v-model="form.phone" placeholder="Phone number" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Create Business
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="route('business')">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
