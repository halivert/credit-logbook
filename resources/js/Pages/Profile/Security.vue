<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PasskeysForm, { type PasskeyItem } from "./Partials/PasskeysForm.vue"
import SecurityActivity, {
    type SecurityEventItem,
} from "./Partials/SecurityActivity.vue"
import TwoFactorForm, { type TwoFactorState } from "./Partials/TwoFactorForm.vue"
import { Head, Link } from "@inertiajs/vue3"

defineProps<{
    twoFactor: TwoFactorState
    passkeys: PasskeyItem[]
    events: SecurityEventItem[]
}>()
</script>

<template>
    <Head title="Seguridad" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                <Link :href="route('profile.edit')" class="hover:underline"
                    >Perfil</Link
                >
                <span class="text-gray-500 dark:text-gray-400"> · Seguridad</span>
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
                <div
                    class="rounded-lg bg-white dark:bg-gray-800 p-4 sm:p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <PasskeysForm :passkeys="passkeys" />
                </div>

                <div
                    class="rounded-lg bg-white dark:bg-gray-800 p-4 sm:p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <TwoFactorForm :two-factor="twoFactor" />
                </div>

                <div
                    class="rounded-lg bg-white dark:bg-gray-800 p-4 sm:p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <SecurityActivity :events="events" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
