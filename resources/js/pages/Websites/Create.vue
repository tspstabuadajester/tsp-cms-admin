<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type BusinessOption } from '@/types';
import { slugFromName } from '@/lib/slug';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

defineProps<{
    businesses: BusinessOption[];
    showBusinessField: boolean;
}>();

const selectClass =
    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Websites',
        href: '/websites',
    },
    {
        title: 'Create',
        href: '/websites/create',
    },
];

const logoPreview = ref<string | null>(null);

const form = useForm({
    name: '',
    slug: '',
    primary_domain: '',
    business_id: '' as string | number,
    status: 'active',
    logo: null as File | null,
});

watch(
    () => form.name,
    (name) => {
        form.slug = slugFromName(name);
    },
);

const revokePreview = () => {
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }
};

const onLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        revokePreview();
        form.logo = null;
        return;
    }

    revokePreview();
    form.clearErrors('logo');

    if (!file.type.startsWith('image/')) {
        form.setError('logo', 'The logo must be an image file.');
        form.logo = null;
        input.value = '';
        return;
    }

    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const submit = () => {
    form.post(route('websites.store'), {
        forceFormData: true,
    });
};

onBeforeUnmount(revokePreview);
</script>

<template>
    <Head title="Create Website" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:max-w-2xl">
            <HeadingSmall title="Create website" description="Add a new website to the system" />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        v-model="form.name"
                        placeholder="Website name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        type="text"
                        readonly
                        v-model="form.slug"
                        placeholder="my-website"
                        class="cursor-default bg-muted"
                    />
                    <p class="text-sm text-muted-foreground">Generated automatically from the name.</p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="primary_domain">Primary domain</Label>
                    <Input
                        id="primary_domain"
                        type="text"
                        v-model="form.primary_domain"
                        placeholder="www.example.com"
                    />
                    <InputError :message="form.errors.primary_domain" />
                </div>

                <div class="grid gap-2">
                    <Label for="logo">Logo</Label>
                    <Input
                        id="logo"
                        type="file"
                        accept="image/*"
                        class="cursor-pointer file:cursor-pointer"
                        @change="onLogoChange"
                    />
                    <p class="text-sm text-muted-foreground">PNG, JPG, GIF, or WebP. Max 2MB.</p>
                    <InputError :message="form.errors.logo" />

                    <div v-if="logoPreview" class="w-fit">
                        <img
                            :src="logoPreview"
                            alt="Logo preview"
                            class="max-h-40 max-w-full rounded-md border border-input object-contain"
                        />
                    </div>
                </div>

                <div v-if="showBusinessField" class="grid gap-2">
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
                    <Label for="status">Status</Label>
                    <select id="status" v-model="form.status" required :class="selectClass">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Create Website
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('websites')">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
