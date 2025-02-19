<script setup>
import { Button } from "@/components/ui/button";
import { SunMoon } from "lucide-vue-next";
import { switchTheme } from "../theme";
import MobileAdminSidebar from "@/Components/MobileAdminSidebar.vue";
import AdminSidebar from "@/Components/AdminSidebar.vue";
</script>

<template>
    <div class="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[240px_1fr]">
        <!-- main sidebar -->
        <AdminSidebar />

        <!-- main content -->
        <div class="flex flex-col">
            <!-- header -->
            <header class="bg-background sticky top-0 z-10 border-b">
                <div class="mx-auto flex h-14 max-w-screen-2xl items-center gap-4 px-6 lg:h-[60px]">
                    <!-- mobile sidebar -->
                    <MobileAdminSidebar />

                    <!-- header actions -->
                    <div class="flex items-center justify-end flex-1">
                        <Button @click="switchTheme()" variant="ghost" size="icon">
                            <SunMoon class="h-9 w-9" />
                            <span class="sr-only">Toggle dark mode</span>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- main content -->
            <main class="lg:max-w-screen-2xl lg:p-8 flex-1 w-full p-6 mx-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
