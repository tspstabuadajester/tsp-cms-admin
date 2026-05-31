<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
const appName = page.props.name as string;
const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="relative flex min-h-dvh items-center justify-center overflow-hidden bg-[#dceaf8] px-4 py-10 sm:px-6">
        <!-- Background wave pattern -->
        <div
            class="pointer-events-none absolute inset-0 opacity-40"
            style="
                background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22 viewBox=%220 0 100 100%22%3E%3Cpath d=%22M0 50 Q25 30 50 50 T100 50%22 fill=%22none%22 stroke=%22%23ffffff%22 stroke-width=%221.5%22/%3E%3C/svg%3E');
                background-size: 120px 120px;
            "
        />
        <div class="pointer-events-none absolute -left-24 top-1/4 h-72 w-72 rounded-full bg-white/30 blur-3xl" />
        <div class="pointer-events-none absolute -right-24 bottom-1/4 h-72 w-72 rounded-full bg-[#579fd4]/20 blur-3xl" />

        <div class="relative w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/60 bg-white/40 shadow-[0_25px_60px_-15px_rgba(55,120,180,0.35)] backdrop-blur-sm">
            <div class="grid lg:grid-cols-[42%_1fr]">
                <!-- Left panel -->
                <div class="relative hidden flex-col justify-between overflow-hidden bg-[#579fd4] p-10 text-white lg:flex">
                    <div class="relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="relative flex h-9 w-9 items-center justify-center">
                                <span class="absolute h-7 w-7 rounded-full bg-white/30" />
                                <span class="relative h-7 w-7 rounded-full bg-white" />
                            </div>
                            <span class="text-xl font-semibold tracking-wide">{{ appName }}</span>
                        </div>

                        <div class="mt-16 space-y-4">
                            <h2 class="text-3xl font-bold leading-tight">Manage your websites the best way</h2>
                            <p class="max-w-xs text-sm leading-relaxed text-white/85">
                                Sign in to manage content, templates, and publish updates across all your sites.
                            </p>
                        </div>
                    </div>

                    <div class="relative z-10 flex justify-center pt-8">
                        <img
                            src="/images/login.png"
                            alt=""
                            class="max-h-56 w-auto object-contain drop-shadow-lg"
                        />
                    </div>

                    <div class="pointer-events-none absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10" />
                    <div class="pointer-events-none absolute -right-6 top-10 h-24 w-24 rounded-full bg-white/10" />
                </div>

                <!-- Right panel -->
                <div class="flex flex-col justify-center bg-white px-8 py-10 sm:px-12 sm:py-12">
                    <div class="mb-8 lg:hidden">
                        <div class="flex items-center gap-3">
                            <div class="relative flex h-8 w-8 items-center justify-center">
                                <span class="absolute h-6 w-6 rounded-full bg-[#579fd4]/30" />
                                <span class="relative h-6 w-6 rounded-full bg-[#579fd4]" />
                            </div>
                            <span class="text-lg font-semibold text-[#579fd4]">{{ appName }}</span>
                        </div>
                    </div>

                    <h1 class="text-3xl font-bold text-slate-800">Login</h1>

                    <div v-if="status" class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="mt-8 flex flex-col gap-5">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium text-slate-500">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                tabindex="1"
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/80 px-4 text-sm text-slate-800 shadow-inner outline-none transition focus:border-[#579fd4] focus:bg-white focus:ring-2 focus:ring-[#579fd4]/20"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <label for="password" class="text-sm font-medium text-slate-500">Password</label>
                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    tabindex="2"
                                    autocomplete="current-password"
                                    placeholder="Password"
                                    class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/80 px-4 pr-12 text-sm text-slate-800 shadow-inner outline-none transition focus:border-[#579fd4] focus:bg-white focus:ring-2 focus:ring-[#579fd4]/20"
                                />
                                <button
                                    type="button"
                                    tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-600"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="h-5 w-5" />
                                    <Eye v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label for="remember" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                                <Checkbox
                                    id="remember"
                                    v-model:checked="form.remember"
                                    tabindex="3"
                                    class="size-[18px] rounded border-slate-300 data-[state=checked]:border-[#579fd4] data-[state=checked]:bg-[#579fd4] data-[state=checked]:text-white"
                                />
                                Remember me
                            </label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-[#579fd4] no-underline hover:text-[#4689bd]"
                                tabindex="5"
                            >
                                Forgot password?
                            </TextLink>
                        </div>

                        <button
                            type="submit"
                            tabindex="4"
                            :disabled="form.processing"
                            class="mt-2 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#579fd4] to-[#4a8ec4] text-sm font-semibold text-white shadow-[0_8px_20px_-6px_rgba(87,159,212,0.65)] transition hover:from-[#4a8ec4] hover:to-[#3d7db3] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
