<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: (h, page) => page,
});

interface Toast {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
}

interface PageProps {
    email: string;
    email_verified: boolean;
    flash?: {
        toast?: Toast | null;
    };
}

const page = usePage<PageProps>();

const props = defineProps<{
    email: string;
    email_verified: boolean;
}>();

const isProcessing = ref(false);
const cooldown = ref(0);

const toast = ref<Toast | null>(null);
const showToast = ref(false);

let timer: ReturnType<typeof setInterval> | null = null;
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const buttonLabel = computed(() => {
    if (isProcessing.value) {
        return 'Mengirim...';
    }

    if (cooldown.value > 0) {
        return `Kirim Ulang (${cooldown.value}s)`;
    }

    return 'Kirim Ulang Email Verifikasi';
});

const isButtonDisabled = computed(
    () => isProcessing.value || cooldown.value > 0,
);

function displayToast(value: Toast | null | undefined) {
    if (!value) {
        return;
    }

    toast.value = value;
    showToast.value = true;

    if (toastTimer) {
        clearTimeout(toastTimer);
    }

    toastTimer = setTimeout(() => {
        showToast.value = false;

        setTimeout(() => {
            toast.value = null;
        }, 300);
    }, 4000);
}

watch(
    () => page.props.flash?.toast,
    (value) => {
        displayToast(value);
    },
    {
        immediate: true,
    },
);

function closeToast() {
    showToast.value = false;

    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }

    setTimeout(() => {
        toast.value = null;
    }, 300);
}

function startCooldown(seconds = 60) {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }

    cooldown.value = seconds;

    timer = setInterval(() => {
        cooldown.value -= 1;

        if (cooldown.value <= 0) {
            cooldown.value = 0;

            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }
    }, 1000);
}

function resend() {
    if (isButtonDisabled.value) {
        return;
    }

    isProcessing.value = true;

    router.post(
        '/email/verification-notification',
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                startCooldown();
            },

            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
}

onMounted(() => {
    if (props.email_verified) {
        router.visit('/dashboard');
    }
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }

    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
});
</script>

<template>
    <Head title="Verifikasi Email - Sistem Inventory" />

    <div
        class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a]"
    >
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="animate-blob absolute -left-24 -top-24 h-72 w-72 rounded-full bg-[#f53003]/20 blur-3xl sm:h-96 sm:w-96 dark:bg-[#FF4433]/10"
            ></div>

            <div
                class="animate-blob animation-delay-2000 absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-[#f53003]/10 blur-3xl sm:h-96 sm:w-96 dark:bg-[#FF4433]/10"
            ></div>

            <div
                class="animate-blob animation-delay-4000 absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-amber-300/10 blur-3xl sm:h-72 sm:w-72 dark:bg-amber-500/10"
            ></div>

            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:32px_32px]"
            ></div>
        </div>

        <div
            class="relative z-10 flex w-full items-center justify-center opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col overflow-hidden rounded-xl border border-black/5 bg-white/90 shadow-2xl backdrop-blur-sm lg:max-w-4xl lg:flex-row dark:border-white/10 dark:bg-[#161615]/90"
            >
                <div
                    class="relative flex shrink-0 items-center justify-center overflow-hidden bg-[#fff2f2] p-8 lg:w-[420px] lg:p-12 dark:bg-[#1D0002]"
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
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"
                                />
                            </svg>
                        </div>

                        <h2
                            class="mt-4 text-xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Verifikasi Email
                        </h2>

                        <p
                            class="mt-1 text-sm font-medium text-[#f53003] dark:text-[#FF4433]"
                        >
                            Sistem Inventory
                        </p>

                        <p
                            class="mt-3 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Verifikasi alamat email Anda untuk menjaga
                            keamanan akun dan mendapatkan akses ke seluruh
                            fitur Sistem Inventory.
                        </p>
                    </div>

                    <div
                        class="absolute -bottom-10 -right-10 h-48 w-48 rounded-full bg-[#f53003]/20 blur-3xl dark:bg-[#FF4433]/20"
                    ></div>
                </div>

                <div class="flex-1 p-8 lg:p-12 dark:text-[#EDEDEC]">
                    <div class="mb-8">
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/20 dark:text-[#FF4433]"
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
                                    d="M9 12.75 11.25 15 15 9.75m6.75 2.25a9.75 9.75 0 1 1-19.5 0 9.75 9.75 0 0 1 19.5 0Z"
                                />
                            </svg>
                        </div>

                        <h1
                            class="text-2xl font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Verifikasi Email Diperlukan
                        </h1>

                        <p
                            class="mt-2 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Satu langkah lagi untuk mengaktifkan akun Anda.
                            Silakan periksa inbox email dan ikuti tautan
                            verifikasi yang telah kami kirimkan.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-4 dark:border-[#3E3E3A] dark:bg-[#1c1c1a]"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/20 dark:text-[#FF4433]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-[10px] font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Email terdaftar
                                </p>

                                <p
                                    class="mt-1 break-all text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ props.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p
                            class="text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kami telah mengirimkan email verifikasi ke alamat
                            tersebut. Buka email Anda dan klik
                            <strong
                                class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Verifikasi Email Sekarang
                            </strong>
                            untuk mengaktifkan akun.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="resend"
                        :disabled="isButtonDisabled"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white dark:focus:ring-white"
                    >
                        <svg
                            v-if="isProcessing"
                            class="h-4 w-4 animate-spin"
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
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>

                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-3.75m0 0h3.75m-3.75 0 3 3m-3-3a9 9 0 1 1 1.071 4.297"
                            />
                        </svg>

                        <span>{{ buttonLabel }}</span>
                    </button>

                    <div
                        class="mt-5 rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] px-4 py-3 dark:border-[#3E3E3A] dark:bg-[#1c1c1a]"
                    >
                        <div class="flex gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="mt-0.5 h-4 w-4 shrink-0 text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.25 11.25 11.25 15m0-8.25h.008v.008h-.008V6.75Zm0 14.25a9 9 0 1 1 0-18 9 9 0 0 1 0 18Z"
                                />
                            </svg>

                            <p
                                class="text-[11px] leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tidak menerima email? Periksa folder
                                <span
                                    class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Spam
                                </span>
                                atau
                                <span
                                    class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Junk
                                </span>
                                . Anda dapat mengirim ulang email setelah
                                cooldown selesai.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-7 border-t border-[#e3e3e0] pt-5 dark:border-[#3E3E3A]"
                    >
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="inline-flex w-full items-center justify-center gap-1.5 text-xs font-medium text-[#706f6c] transition hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-6 3 3m0 0-3 3m3-3H9"
                                />
                            </svg>

                            Keluar dari akun ini
                        </Link>
                    </div>
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

@keyframes toast-progress {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}

.animate-blob {
    animation: blob 10s infinite ease-in-out;
}

.animate-toast-progress {
    animation: toast-progress 4s linear forwards;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-blob,
    .animate-toast-progress {
        animation: none;
    }
}
</style>