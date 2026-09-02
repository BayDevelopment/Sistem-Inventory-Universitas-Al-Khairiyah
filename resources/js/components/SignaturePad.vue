<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import SignaturePad from 'signature_pad';

// Emits untuk mengirim data tanda tangan (base64 PNG) ke parent component / form
const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
    (e: 'change', value: string | null): void;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let signaturePad: SignaturePad | null = null;

// Mengatur ukuran canvas agar responsif dan tidak buram pada layar Retina/HiDPI
const resizeCanvas = () => {
    if (!canvasRef.value) return;
    const canvas = canvasRef.value;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);

    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d')?.scale(ratio, ratio);

    if (signaturePad) {
        signaturePad.clear(); // Clear saat window di-resize agar posisi stroke tidak bergeser
    }
};

onMounted(() => {
    if (canvasRef.value) {
        signaturePad = new SignaturePad(canvasRef.value, {
            penColor: 'rgb(0, 0, 0)',
            backgroundColor: 'rgb(255, 255, 255)',
        });

        // Event listener ketika user selesai menggoreskan tanda tangan
        signaturePad.addEventListener('endStroke', () => {
            if (signaturePad && !signaturePad.isEmpty()) {
                const dataUrl = signaturePad.toDataURL('image/png');
                emit('update:modelValue', dataUrl);
                emit('change', dataUrl);
            }
        });

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', resizeCanvas);
    if (signaturePad) {
        signaturePad.off();
    }
});

// Fungsi Reset / Clear Tanda Tangan
const clearSignature = () => {
    if (signaturePad) {
        signaturePad.clear();
        emit('update:modelValue', null);
        emit('change', null);
    }
};
</script>

<template>
    <div class="w-full space-y-2">
        <div class="relative h-48 w-full overflow-hidden rounded-xl border border-slate-300 bg-white shadow-inner dark:border-white/10 dark:bg-[#18181c]">
            <canvas
                ref="canvasRef"
                class="h-full w-full touch-none cursor-crosshair"
            ></canvas>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Tanda tangan di dalam kotak di atas.
            </span>
            <button
                type="button"
                @click="clearSignature"
                class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
            >
                Hapus Tanda Tangan
            </button>
        </div>
    </div>
</template>
