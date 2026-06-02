import { ref } from 'vue'

export const dashboardVersion = ref(0)
export function invalidateDashboard() { dashboardVersion.value++ }
