<script setup>
    import { Button } from '@/components/ui/button'
    import { Input } from '@/components/ui/input'
    import { SunMoon, Search } from 'lucide-vue-next'
    import Sidebar from '../Components/Sidebar.vue'
    import MobileSidebar from '../Components/MobileSidebar.vue'
    import UserDropdownMenu from '../Components/UserDropdownMenu.vue'
    import { switchTheme } from '../theme'
</script>

<template>
    <!-- layout wrapper -->
    <div class="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[280px_1fr]">
        
        <!-- main sidebar -->
        <Sidebar />

        <!-- main content (1fr)-->
        <div class="flex flex-col">
            <!-- header -->
            <header class="flex h-14 items-center gap-4 border-b bg-muted/40 px-4 lg:h-[60px] lg:px-6">
                
                <!-- mobile sidebar -->
                <MobileSidebar />

                <!-- search bar -->
                <div class="w-full flex-1">
                    <form>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search anything..."
                                class="w-full appearance-none bg-background pl-8 shadow-none md:w-2/3 lg:w-1/3"
                            />
                        </div>
                    </form>
                </div>

                <div class="flex items-center gap-1">
                    <Button 
                        @click="switchTheme()"
                        variant="ghost" 
                        size="icon" 
                    >
                        <SunMoon class="h-9 w-9" />
                        <span class="sr-only">Toggle dark mode</span>
                    </Button>

                    <!-- user dropdown menu -->
                    <UserDropdownMenu />
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex flex-1 flex-col gap-4 p-4 lg:gap-6 lg:p-6">
                
                <!-- Page Title -->
                <div class="flex items-center">
                    <h1 class="text-lg font-semibold md:text-2xl">{{ $page.component }}</h1>
                </div>
                
                <!-- slot contents -->
                <slot />
            </main>
        </div>
    </div>
</template>
