import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    function show(message, type = 'success', duration = 4000) {
        const id = ++nextId;
        toasts.value.push({ id, message, type });
        setTimeout(() => dismiss(id), duration);
    }

    function dismiss(id) {
        const idx = toasts.value.findIndex(t => t.id === id);
        if (idx !== -1) toasts.value.splice(idx, 1);
    }

    return { toasts, show, dismiss };
}
