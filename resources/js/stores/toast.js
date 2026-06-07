import { ref } from 'vue'

export const toasts = ref([])
let nextId = 0

export function showToast(message, type = 'success', duration = 3500) {
    const id = ++nextId
    toasts.value.push({ id, message, type, duration })
    setTimeout(() => dismissToast(id), duration)
}

export function dismissToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
}
