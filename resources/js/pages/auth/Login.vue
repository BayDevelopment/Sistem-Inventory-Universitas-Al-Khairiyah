<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { register } from '@/routes';

// Bypass layout bawaan
defineOptions({
    layout: (h, page) => page,
});

// State & handler toggle password
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

// Form handler Inertia
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in - Sistem Inventory" />

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
                            Selamat Datang
                        </h1>
                        <p
                            class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Masukkan kredensial Anda untuk mengakses akun.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
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
                                    v-model="form.email"
                                    required
                                    autofocus
                                    placeholder="nama@email.com"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-sm text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                />
                            </div>
                            <span
                                v-if="form.errors.email"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.email }}
                            </span>
                        </div>

                        <!-- Password Input with Eye Icon Toggle -->
                        <div>
                            <div class="flex items-center justify-between">
                                <label
                                    for="password"
                                    class="block text-xs font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Password
                                </label>
                                <Link
                                    href="/forgot-password"
                                    class="text-xs font-medium text-[#f53003] transition hover:underline dark:text-[#FF4433]"
                                >
                                    Lupa password?
                                </Link>
                            </div>
                            <div class="relative mt-1.5">
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
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
                                v-if="form.errors.password"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.password }}
                            </span>
                        </div>

                        <!-- Remember Me & Submit -->
                        <div class="flex items-center justify-between pt-2">
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:checked:bg-[#FF4433]"
                                />
                                <span
                                    class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Ingat saya
                                </span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-[#1b1b18] py-2.5 text-center text-sm font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
                        >
                            Log in
                        </button>
                    </form>

                    <!-- Footer -->
                    <div
                        class="mt-6 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Buat akun untuk melakukan peminjaman?
                        <Link
                            :href="register()"
                            class="font-medium text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            Daftar sekarang
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
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                />
                            </svg>
                        </div>
                        <h2
                            class="mt-4 text-xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Sistem Inventory
                        </h2>
                        <p
                            class="mt-1 text-sm font-medium text-[#f53003] dark:text-[#FF4433]"
                        >
                            Universitas Al-Khairiyah
                        </p>
                        <p
                            class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kelola dan pantau peminjaman inventaris kampus
                            dengan sistem autentikasi modern berbasis Laravel
                            & Inertia.js.
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
