<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden">

        <!-- ── Sidebar : desktop uniquement ───────────────────────── -->
        <aside class="hidden lg:flex flex-col w-60 bg-white border-r border-slate-200 shrink-0">
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-100">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
                    <Activity class="w-5 h-5 text-white" />
                </div>
                <span class="font-bold text-slate-800 text-sm">DentalApp</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <template v-for="item in filteredNavItems" :key="item.name">
                    <div v-if="item.divider" class="pt-4 pb-2 px-2">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                            {{ item.divider }}
                        </span>
                    </div>
                    <RouterLink
                        v-else
                        :to="{ name: item.route }"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 group"
                        :class="isActive(item.route)
                            ? 'bg-blue-50 text-blue-700 font-medium'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                    >
                        <component :is="item.icon" class="w-5 h-5 transition-colors"
                            :class="isActive(item.route) ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600'" />
                        <span>{{ item.label }}</span>
                        <span v-if="isActive(item.route)" class="ml-auto w-1.5 h-1.5 bg-blue-600 rounded-full" />
                    </RouterLink>
                </template>
            </nav>

            <div class="border-t border-slate-100 p-3">
                <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-slate-50 cursor-pointer group">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center overflow-hidden">
                        <img v-if="user?.avatar" :src="user.avatar" class="w-full h-full object-cover" alt="Avatar" />
                        <span v-else class="text-xs font-bold text-white">{{ userInitials }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ user?.name }}</p>
                        <p class="text-xs text-slate-400 capitalize">
                            {{ user?.role === 'DENTIST' ? 'Dentiste' : 'Assistant(e)' }}
                        </p>
                    </div>
                    <button @click="handleLogout" title="Se déconnecter"
                        class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 transition-all">
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- ── Zone principale ────────────────────────────────────── -->
        <div class="flex flex-col flex-1 min-w-0">

            <!-- Header -->
            <header class="flex items-center justify-between px-4 lg:px-6 py-3 lg:py-3.5 bg-white border-b border-slate-200 shrink-0">
                <!-- Mobile : logo | Desktop : titre de page -->
                <div>
                    <div class="flex items-center gap-2 lg:hidden">
                        <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
                            <Activity class="w-4 h-4 text-white" />
                        </div>
                        <span class="font-bold text-slate-800 text-sm">DentalApp</span>
                    </div>
                    <div class="hidden lg:block">
                        <h1 class="text-base font-semibold text-slate-800">{{ currentPageTitle }}</h1>
                        <p class="text-xs text-slate-400">{{ currentDate }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:gap-3">
                    <NotificationsDropdown />

                    <div class="w-px h-6 bg-slate-200"></div>

                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center overflow-hidden">
                            <img v-if="user?.avatar" :src="user.avatar" class="w-full h-full object-cover" alt="Avatar" />
                            <span v-else class="text-xs font-bold text-white">{{ userInitials }}</span>
                        </div>
                        <span class="text-sm font-medium text-slate-700 hidden sm:block">{{ user?.name }}</span>
                    </div>

                    <!-- Déconnexion mobile -->
                    <button @click="handleLogout"
                        class="lg:hidden p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </header>

            <!-- Contenu -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-4 lg:p-6">
                <RouterView v-slot="{ Component }">
                    <Transition name="fade" mode="out-in">
                        <component :is="Component" />
                    </Transition>
                </RouterView>
                <!-- Espaceur pour la nav mobile fixe en bas -->
                <div class="h-16 lg:hidden" aria-hidden="true"></div>
            </main>
        </div>

        <!-- ── Navigation mobile — barre fixe en bas ──────────────── -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-200 lg:hidden">
            <div class="flex items-center justify-around">
                <RouterLink
                    v-for="item in mobileNavItems"
                    :key="item.route"
                    :to="{ name: item.route }"
                    class="flex flex-col items-center gap-0.5 py-2 px-1 flex-1 transition-colors"
                    :class="isActive(item.route) ? 'text-blue-600' : 'text-slate-400'"
                >
                    <component :is="item.icon" class="w-5 h-5" />
                    <span class="text-[10px] font-medium">{{ item.label }}</span>
                </RouterLink>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { computed, markRaw } from "vue";
import { RouterLink, RouterView, useRoute } from "vue-router";
import { authStore } from "../stores/auth";
import {
    LayoutDashboard, CalendarDays, Users, Stethoscope,
    CreditCard, LogOut, Activity, UserCircle, Settings,
} from "lucide-vue-next";
import NotificationsDropdown from "../components/NotificationsDropdown.vue";

const user = computed(() => authStore.user);

const userInitials = computed(() => {
    if (!user.value?.name) return "?";
    return user.value.name.split(" ").map((w) => w[0]).join("").toUpperCase().slice(0, 2);
});

const currentDate = computed(() =>
    new Date().toLocaleDateString("fr-FR", {
        weekday: "long", year: "numeric", month: "long", day: "numeric",
    })
);

const navItems = [
    { route: "dashboard",     label: "Tableau de bord", icon: markRaw(LayoutDashboard), roles: ["DENTIST"] },
    { divider: "Gestion",     roles: ["DENTIST", "ASSISTANT"] },
    { route: "agenda",        label: "Agenda",           icon: markRaw(CalendarDays),   roles: ["DENTIST", "ASSISTANT"] },
    { route: "patients",      label: "Patients",         icon: markRaw(Users),          roles: ["DENTIST", "ASSISTANT"] },
    { divider: "Soins",       roles: ["DENTIST"] },
    { route: "consultations", label: "Consultations",    icon: markRaw(Stethoscope),    roles: ["DENTIST"] },
    { divider: "Paiements",   roles: ["DENTIST", "ASSISTANT"] },
    { route: "paiements",     label: "Paiements",        icon: markRaw(CreditCard),     roles: ["DENTIST", "ASSISTANT"] },
    { divider: "Cabinet",     roles: ["DENTIST"] },
    { route: "parametres",   label: "Paramètres",       icon: markRaw(Settings),       roles: ["DENTIST"] },
    { divider: "Compte",      roles: ["DENTIST", "ASSISTANT"] },
    { route: "profil",        label: "Mon profil",       icon: markRaw(UserCircle),     roles: ["DENTIST", "ASSISTANT"] },
];

const filteredNavItems = computed(() => {
    const role = user.value?.role;
    return navItems.filter((item) => !item.roles || item.roles.includes(role));
});

// Nav mobile : items essentiels sans dividers
const mobileNavItems = computed(() => {
    const role = user.value?.role;
    if (role === "DENTIST") {
        return [
            { route: "dashboard",     label: "Accueil",    icon: markRaw(LayoutDashboard) },
            { route: "agenda",        label: "Agenda",     icon: markRaw(CalendarDays) },
            { route: "patients",      label: "Patients",   icon: markRaw(Users) },
            { route: "consultations", label: "Soins",      icon: markRaw(Stethoscope) },
            { route: "paiements",     label: "Paiements",  icon: markRaw(CreditCard) },
        ];
    }
    return [
        { route: "agenda",   label: "Agenda",    icon: markRaw(CalendarDays) },
        { route: "patients", label: "Patients",  icon: markRaw(Users) },
        { route: "paiements",label: "Paiements", icon: markRaw(CreditCard) },
        { route: "profil",   label: "Profil",    icon: markRaw(UserCircle) },
    ];
});

const route = useRoute();

const currentPageTitle = computed(() => {
    const item = navItems.find((i) => i.route === route.name);
    return item?.label || "DentalApp";
});

function isActive(routeName) {
    return route.name === routeName;
}

async function handleLogout() {
    await authStore.logout();
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
