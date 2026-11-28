<script setup lang="ts">
import { ref } from "vue"
import ApplicationLogo from "@/Components/ApplicationLogo.vue"
import ChevronDownIcon from "@/Components/Icons/ChevronDownIcon.vue"
import CloseIcon from "@/Components/Icons/CloseIcon.vue"
import MenuIcon from "@/Components/Icons/MenuIcon.vue"
import Dropdown from "@/Components/Dropdown.vue"
import DropdownLink from "@/Components/DropdownLink.vue"
import NavLink from "@/Components/NavLink.vue"
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue"
import { Link } from "@inertiajs/vue3"

const showingNavigationDropdown = ref(false)
</script>

<template>
    <div>
        <div class="min-h-screen bg-page">
            <nav
                class="bg-surface border-b-4 border-line"
            >
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('credit-cards.index')">
                                    <ApplicationLogo
                                        class="block size-8 fill-current text-ink"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('credit-cards.index')"
                                    :active="
                                        route().current('credit-cards.index')
                                    "
                                >
                                    Tarjetas
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 text-xl leading-4 text-muted hover:text-ink focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <ChevronDownIcon class="ml-2 size-4" />
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Perfil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Salir
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="nes-btn is-icon"
                            >
                                <MenuIcon
                                    v-if="!showingNavigationDropdown"
                                    class="size-4"
                                />
                                <CloseIcon v-else class="size-4" />
                                <span class="sr-only">Menú</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink
                            :href="route('credit-cards.index')"
                            :active="route().current('credit-cards.index')"
                        >
                            Tarjetas
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="pt-4 pb-1 border-t-4 border-dashed border-line/25"
                    >
                        <div class="px-4">
                            <div
                                class="text-xl text-ink"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="mt-1 font-medium text-lg text-muted">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Salir
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-surface border-b-4 border-line"
                v-if="$slots.header"
            >
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
