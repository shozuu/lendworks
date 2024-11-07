<script setup>
    import { switchTheme } from '../theme';
    import NavLink from "../Components/NavLink.vue";
    import ProfileIcon from '../Components/ProfileIcon.vue';
    import ProfileDropdownItem from '../Components/ProfileDropdownItem.vue';
    import ProfileDropdownMenu from '../Components/ProfileDropdownMenu.vue';
    import ThemeSwitch from '../Components/ThemeSwitch.vue';
    import Logo from '../Components/Logo.vue';  
    import { usePage } from '@inertiajs/vue3';
    import { computed, ref  } from 'vue';

    const user = computed(() => usePage().props.auth.user)
    const show = ref(false);
</script>

<template>
    <!-- overlay to replicate click outside -->
    <div v-show="show" @click="show = false" class="fixed inset-0 z-40"></div>

    <header class="border-b border-border bg-sides">
        <nav class="p-6 flex items-center justify-between">

            <div class="flex items-center gap-3">
                <div class="h-8 shrink-0">
                    <Logo :wrenchColor="'var(--wrench-color)'" :handColor="'var(--hand-color)'" />
                </div>

                <Link :href="route('home')" class=" font-semibold text-2xl hover:text-primary">
                    <span class="hover:text-text">Lend</span>
                    <span class="text-primary hover:text-text">Works</span>
                </Link>
            </div>

            <div class="flex items-center ml-auto">
                <NavLink routeName="home" componentName="Home">+ Create Listing</NavLink>

                <!-- auth -->
                <div 
                    v-if="user" 
                    class="relative flex items-center border-l border-border ml-6 pl-6">
                    <!-- container that holds profile icon and dropdown menu -->

                    <ProfileIcon @click="show = !show">
                        <img 
                            src="https://picsum.photos/200" 
                            alt="Profile Picture" 
                            class="h-8 w-8 rounded-full object-cover"
                        >
                    </ProfileIcon>

                    <ProfileDropdownMenu v-show="show" @click="show = !show">
                        
                        <ProfileDropdownItem routeName="home">Profile</ProfileDropdownItem>
                        <ProfileDropdownItem routeName="home">Test Link</ProfileDropdownItem>

                        <ProfileDropdownItem 
                            routeName="logout" method="post" as="button">Log Out
                        </ProfileDropdownItem>

                        <!-- divider -->
                        <div class="border-t border-border my-1"></div>

                        <!-- theme switch -->
                        <ThemeSwitch 
                            @click="switchTheme(); show = false">
                        </ThemeSwitch>
                    </ProfileDropdownMenu>

                </div>

                <!-- guest -->
                <div 
                    v-else
                    class="relative flex items-center border-l border-border ml-6 pl-6">
                    <!-- profile icon for guest user -->
                    <ProfileIcon @click="show = !show">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 32 32" 
                            aria-hidden="true" 
                            role="presentation" 
                            focusable="false" 
                            class="h-8 w-8 fill-current text-text"
                        >
                            <path d="M16 .7C7.56.7.7 7.56.7 16S7.56 31.3 16 31.3 31.3 24.44 31.3 16 24.44.7 16 .7zm0 28c-4.02 0-7.6-1.88-9.93-4.81a12.43 12.43 0 0 1 6.45-4.4A6.5 6.5 0 0 1 9.5 14a6.5 6.5 0 0 1 13 0 6.51 6.51 0 0 1-3.02 5.5 12.42 12.42 0 0 1 6.45 4.4A12.67 12.67 0 0 1 16 28.7z"></path>
                        </svg>
                    </ProfileIcon>

                    <ProfileDropdownMenu v-show="show" @click="show = !show">
                        <ProfileDropdownItem routeName="login">Login</ProfileDropdownItem>
                        <ProfileDropdownItem routeName="register">Register</ProfileDropdownItem>

                        <!-- divider -->
                        <div class="border-t border-border my-1"></div>

                        <!-- theme switch -->
                        <ThemeSwitch 
                            @click="switchTheme(); show = false">
                        </ThemeSwitch>
                    </ProfileDropdownMenu>
                </div>
            </div>
        </nav>
    </header>

    <main class="p-6">
        <slot />
    </main>
</template>
