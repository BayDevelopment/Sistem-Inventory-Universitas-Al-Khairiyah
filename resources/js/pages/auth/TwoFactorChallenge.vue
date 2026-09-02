<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { store } from '@/routes/two-factor/login';

// Bypass & matikan layout bawaan Starter Kit
defineOptions({
    layout: (h, page) => page,
});

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const authConfigContent = computed(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Kode Pemulihan (Recovery)',
            description:
                'Masukkan salah satu kode pemulihan darurat Anda untuk mengonfirmasi akses akun.',
            buttonText: 'Gunakan kode autentikasi aplikasi',
        };
    }

    return {
        title: 'Verifikasi Dua Langkah',
        description:
            'Masukkan 6 digit kode autentikasi yang dibuat oleh aplikasi authenticator Anda.',
        buttonText: 'Gunakan kode pemulihan (Recovery Code)',
    };
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <Head title="Verifikasi Dua Langkah - Sistem Inventory" />

    <div
        class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a]"
    >
        <!-- Animated decorative background -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="animate-blob absolute -left-24 -top-24 h-72 w-72 rounded-full bg-[#f53003]/20 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>
            <div
                class="animate-blob animation-delay-2000 absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>
            <div
                class="animate-blob animation-delay-4000 absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-amber-300/10 blur-3xl dark:bg-amber-500/10 sm:h-72 sm:w-72"
            ></div>
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:32px_32px]"
            ></div>
        </div>

        <!-- Main Card Container -->
        <div
            class="relative z-10 flex w-full items-center justify-center opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-xl border border-black/5 bg-white/90 shadow-2xl backdrop-blur-sm lg:max-w-4xl lg:flex-row dark:border-white/10 dark:bg-[#161615]/90"
            >
                <!-- Form Section -->
                <div class="flex-1 p-8 lg:p-12 dark:text-[#EDEDEC]">
                    <div class="mb-8">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ authConfigContent.title }}
                        </h1>
                        <p
                            class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            {{ authConfigContent.description }}
                        </p>
                    </div>

                    <!-- Mode 1: Authentication OTP Code -->
                    <template v-if="!showRecoveryInput">
                        <Form
                            v-bind="store.form()"
                            class="space-y-6"
                            reset-on-error
                            @error="code = ''"
                            #default="{ errors, processing, clearErrors }"
                        >
                            <input type="hidden" name="code" :value="code" />

                            <div class="flex flex-col items-center space-y-3">
                                <div class="flex w-full justify-center">
                                    <input
                                        id="otp"
                                        v-model="code"
                                        type="text"
                                        maxlength="6"
                                        :disabled="processing"
                                        autofocus
                                        placeholder="000000"
                                        class="w-full max-w-[240px] rounded-lg border border-[#e3e3e0] bg-transparent py-3 text-center text-2xl font-bold tracking-[0.5em] text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                    />
                                </div>
                                <span
                                    v-if="errors.code"
                                    class="text-xs text-red-500"
                                >
                                    {{ errors.code }}
                                </span>
                            </div>

                            <button
                                type="submit"
                                :disabled="processing || code.length < 6"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] py-2.5 text-center text-sm font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
                            >
                                <svg
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin text-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>Verifikasi</span>
                            </button>

                            <div
                                class="text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                ATAU
                                <button
                                    type="button"
                                    class="ml-1 font-medium text-[#f53003] hover:underline dark:text-[#FF4433]"
                                    @click="() => toggleRecoveryMode(clearErrors)"
                                >
                                    {{ authConfigContent.buttonText }}
                                </button>
                            </div>
                        </Form>
                    </template>

                    <!-- Mode 2: Emergency Recovery Code -->
                    <template v-else>
                        <Form
                            v-bind="store.form()"
                            class="space-y-6"
                            reset-on-error
                            #default="{ errors, processing, clearErrors }"
                        >
                            <div>
                                <label
                                    for="recovery_code"
                                    class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Kode Pemulihan
                                </label>
                                <div class="mt-1.5">
                                    <input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        placeholder="xxxx-xxxx-xxxx"
                                        :autofocus="showRecoveryInput"
                                        required
                                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                    />
                                </div>
                                <span
                                    v-if="errors.recovery_code"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ errors.recovery_code }}
                                </span>
                            </div>

                            <button
                                type="submit"
                                :disabled="processing"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] py-2.5 text-center text-sm font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
                            >
                                <svg
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin text-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>Verifikasi Kode Pemulihan</span>
                            </button>

                            <div
                                class="text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                ATAU
                                <button
                                    type="button"
                                    class="ml-1 font-medium text-[#f53003] hover:underline dark:text-[#FF4433]"
                                    @click="() => toggleRecoveryMode(clearErrors)"
                                >
                                    {{ authConfigContent.buttonText }}
                                </button>
                            </div>
                        </Form>
                    </template>
                </div>

                <!-- Visual Decorative Side Panel -->
                <div
                    class="relative flex shrink-0 items-center justify-center overflow-hidden bg-[#fff2f2] p-8 lg:w-[420px] dark:bg-[#1D0002]"
                >
                    <div class="relative z-10 text-center lg:text-left">
                        <div
                            class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/20 dark:text-[#FF4433]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-6 w-6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                />
                            </svg>
                        </div>
                        <h2
                            class="mt-4 text-xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Keamanan Akun
                        </h2>
                        <p
                            class="mt-1 text-sm font-medium text-[#f53003] dark:text-[#FF4433]"
                        >
                            Universitas Al-Khairiyah
                        </p>
                        <p
                            class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Verifikasi dua langkah memastikan hanya Anda yang memiliki akses penuh ke dalam Sistem Inventory.
                        </p>
                    </div>
                    <!-- Soft Background Gradient Blur -->
                    <div
                        class="absolute -bottom-10 -right-10 h-48 w-48 rounded-full bg-[#f53003]/20 blur-3xl dark:bg-[#FF4433]/20"
                    ></div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(20px, -30px) scale(1.1);
    }
    66% {
        transform: translate(-15px, 15px) scale(0.95);
    }
}

.animate-blob {
    animation: blob 10s infinite ease-in-out;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-blob {
        animation: none;
    }
}
</style>
