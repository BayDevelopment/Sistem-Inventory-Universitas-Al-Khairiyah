<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

// Bypass & matikan layout bawaan Starter Kit
defineOptions({
    layout: (h, page) => page,
});

defineProps<{
    passwordRules?: string;
}>();

// State toggle visibilitas password
const showPassword = ref(false);
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

// State toggle visibilitas konfirmasi password
const showPasswordConfirmation = ref(false);
const togglePasswordConfirmationVisibility = () => {
    showPasswordConfirmation.value = !showPasswordConfirmation.value;
};
</script>

<template>
    <Head title="Daftar Akun - Sistem Inventory" />

    <div
        class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a]"
    >
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

        <div
            class="relative z-10 flex w-full items-center justify-center opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-xl border border-black/5 bg-white/90 shadow-2xl backdrop-blur-sm lg:max-w-4xl lg:flex-row dark:border-white/10 dark:bg-[#161615]/90"
            >
                <div class="flex-1 p-8 lg:p-12 dark:text-[#EDEDEC]">
                    <div class="mb-8">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Buat Akun Baru
                        </h1>
                        <p
                            class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Lengkapi data diri Anda di bawah ini untuk mulai menggunakan Sistem Inventory.
                        </p>
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password', 'password_confirmation']"
                        v-slot="{ errors, processing }"
                        class="space-y-4"
                    >
                        <div>
                            <label
                                for="name"
                                class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Nama Lengkap
                            </label>
                            <div class="mt-1.5">
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    required
                                    autofocus
                                    tabindex="1"
                                    autocomplete="name"
                                    placeholder="Nama Lengkap"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                />
                            </div>
                            <span
                                v-if="errors.name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.name }}
                            </span>
                        </div>

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
                                    required
                                    tabindex="2"
                                    autocomplete="email"
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

                        <div>
                            <label
                                for="password"
                                class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Password
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    tabindex="3"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2.5 pl-3.5 pr-10 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                />
                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                    tabindex="-1"
                                >
                                    <svg
                                        v-if="showPassword"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <span
                                v-if="errors.password"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.password }}
                            </span>
                        </div>

                        <div>
                            <label
                                for="password_confirmation"
                                class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Konfirmasi Password
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    required
                                    tabindex="4"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2.5 pl-3.5 pr-10 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                />
                                <button
                                    type="button"
                                    @click="togglePasswordConfirmationVisibility"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                    tabindex="-1"
                                >
                                    <svg
                                        v-if="showPasswordConfirmation"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <span
                                v-if="errors.password_confirmation"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.password_confirmation }}
                            </span>
                        </div>

                        <button
                            type="submit"
                            tabindex="5"
                            :disabled="processing"
                            data-test="register-user-button"
                            class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] py-2.5 text-center text-sm font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
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
                            <span>Daftar Sekarang</span>
                        </button>
                    </Form>

                    <div
                        class="mt-6 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Sudah memiliki akun?
                        <Link
                            :href="login()"
                            tabindex="6"
                            class="font-medium text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            Log in
                        </Link>
                    </div>
                </div>

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
                                    d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"
                                />
                            </svg>
                        </div>
                        <h2
                            class="mt-4 text-xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Bergabung Bersama Kami
                        </h2>
                        <p
                            class="mt-1 text-sm font-medium text-[#f53003] dark:text-[#FF4433]"
                        >
                            Universitas Al-Khairiyah
                        </p>
                        <p
                            class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Daftarkan akun Anda untuk mengakses fasilitas peminjaman barang dan pemantauan inventaris kampus secara real-time.
                        </p>
                    </div>
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
