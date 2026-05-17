import { createRouter, createWebHistory } from "vue-router";

// Import des layouts et vues (lazy loading pour performance maximale)
// Le lazy loading = la page n'est chargée que quand l'utilisateur y accède
import LoginView from "../views/LoginView.vue";
const AppLayout = () => import("../layouts/AppLayout.vue");
const DashboardView = () => import("../views/DashboardView.vue");
const PatientsView = () => import("../views/PatientsView.vue");
const ConsultView = () => import("../views/ConsultationsView.vue");
const AgendaView = () => import("../views/AgendaView.vue");
const PaiementsView = () => import("../views/PaiementsView.vue");
const StockView = () => import("../views/StockView.vue");
const ProfileView = () => import("../views/ProfileView.vue");

const routes = [
    // ─── Page publique ───────────────────────────────────────────────
    {
        path: "/login",
        name: "login",
        component: LoginView,
        // Si déjà connecté → redirige vers dashboard
        beforeEnter: () => {
            if (localStorage.getItem("token")) return { name: "dashboard" };
        },
    },

    // ─── Pages protégées (nécessitent d'être connecté) ───────────────
    {
        path: "/",
        component: AppLayout, // Le layout avec navbar + sidebar
        meta: { requiresAuth: true },
        children: [
            { path: "", redirect: { name: "dashboard" } },
            { path: "dashboard", name: "dashboard", component: DashboardView },
            { path: "patients", name: "patients", component: PatientsView },
            { path: "agenda", name: "agenda", component: AgendaView },
            {
                path: "consultations",
                name: "consultations",
                component: ConsultView,
            },
            { path: "paiements", name: "paiements", component: PaiementsView },
            { path: "stock", name: "stock", component: StockView },
            { path: "profil", name: "profil", component: ProfileView },
        ],
    },

    // ─── Route 404 : toute URL inconnue → login ──────────────────────
    { path: "/:pathMatch(.*)*", redirect: "/login" },

];

const router = createRouter({
    history: createWebHistory(), // URLs propres sans # (ex: /dashboard au lieu de /#/dashboard)
    routes,
});

// ─── Guard global : vérifie le token avant chaque navigation ─────────
router.beforeEach((to) => {
    const token = localStorage.getItem("token");
    if (to.meta.requiresAuth && !token) return { name: "login" };

    // ── Restriction assistant ─────────────────────────────────────
    if (token) {
        try {
            const user = JSON.parse(localStorage.getItem("user") || "{}");
            if (user.role === "ASSISTANT") {
                const forbidden = ["dashboard", "consultations", "stock"];
                if (forbidden.includes(to.name)) return { name: "agenda" };
            }
        } catch {}
    }
});

export default router;
