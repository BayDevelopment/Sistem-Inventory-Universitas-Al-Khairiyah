<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { email } from '@/routes/password';

// Bypass & matikan layout bawaan Starter Kit
defineOptions({
    layout: (h, page) => page,
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Password - Sistem Inventory" />

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
                            Lupa Password?
                        </h1>
                        <p
                            class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Masukkan alamat email Anda dan kami akan mengirimkan instruksi untuk me-reset password.
                        </p>
                    </div>

                    <!-- Status Notification Success -->
                    <div
                        v-if="status"
                        class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs font-medium text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-400"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-5 w-5 shrink-0"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                        <span>{{ status }}</span>
                    </div>

                    <Form
                        v-bind="email.form()"
                        v-slot="{ errors, processing }"
                        class="space-y-5"
                    >
                        <!-- Email Input -->
                        <div>
                            <label
                                for="email"
                                class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Email Address
                            </label>
                            <div class="mt-1.5">
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autocomplete="off"
                                    autofocus
                                    required
                                    placeholder="nama@email.com"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                />
                            </div>
                            <span
                                v-if="errors.email"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.email }}
                            </span>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="processing"
                            data-test="email-password-reset-link-button"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] py-2.5 text-center text-sm font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
                        >
                            <!-- Spinner Loading Icon -->
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
                            <span>Kirim Link Reset Password</span>
                        </button>
                    </Form>

                    <!-- Footer Link -->
                    <div
                        class="mt-6 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Atau, kembali ke halaman
                        <Link
                            :href="login()"
                            class="font-medium text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            Log in
                        </Link>
                    </div>
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
                                    d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"
                                />
                            </svg>
                        </div>
                        <h2
                            class="mt-4 text-xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Pemulihan Akun
                        </h2>
                        <p
                            class="mt-1 text-sm font-medium text-[#f53003] dark:text-[#FF4433]"
                        >
                            Universitas Al-Khairiyah
                        </p>
                        <p
                            class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Tautan reset password akan dikirimkan langsung ke email resmi Anda yang terdaftar pada sistem inventory.
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
