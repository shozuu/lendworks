import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp, Head, Link } from "@inertiajs/vue3";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

import Main from "./Layouts/Main.vue";
import FaceMatcher from "./Pages/Auth/FaceMatcher.vue";
import { setThemeOnLoad } from "./theme";

createInertiaApp({
    title: (title) => `LendWorks ${title}`,
    resolve: async (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        let page = pages[`./Pages/${name}.vue`];

        if (!page) {
            console.error(`Page not found: ${name}`);
            throw new Error(`Page not found: ${name}`);
        }

        // Ensure page has a default export
        if (!page.default) {
            throw new Error(`Page ${name} has no default export`);
        }

        page.default.layout = page.default.layout || Main;
        return page;
    },
    setup({ el, App, props, plugin }) {
        // Ensure props is an object even if empty
        const safeProps = props || {};

        return createApp({
            render: () => h(App, safeProps),
        })
            .use(plugin)
            .use(ZiggyVue)
            .component("Head", Head)
            .component("Link", Link)
            .component("face-matcher", FaceMatcher)
            .mount(el);
    },
    progress: {
        color: "#DB1414",
        showSpinner: false,
    },
}).catch((e) => console.error("Failed to start Inertia app:", e));

setThemeOnLoad();
