<script setup lang="ts">
import ApplicationLogo from "@/Components/ApplicationLogo.vue"
import { Link } from "@inertiajs/vue3"
import LegalLinks from "@/Components/LegalLinks.vue"
</script>

<template>
    <div class="min-h-screen flex flex-col items-center bg-page">
        <!-- Takes the spare height, so the links sit at the bottom -->
        <div
            class="flex-1 w-full flex flex-col items-center pt-6 sm:justify-center sm:pt-0"
        >
            <div>
                <Link href="/">
                    <ApplicationLogo class="w-20 h-20 fill-current text-ink" />
                </Link>
            </div>

            <div class="card w-full sm:max-w-md mt-6 !px-6 !py-6">
                <slot />
            </div>
        </div>

        <LegalLinks class="my-6" />
    </div>
</template>
