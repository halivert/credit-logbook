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
                class="text-large sm:text-title text-ink"
            >
                <Link :href="route('profile.edit')" class="hover:underline"
                    >Perfil</Link
                >
                <span class="text-muted"> · Seguridad</span>
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
                <div
                    class="card is-compact"
                >
                    <PasskeysForm :passkeys="passkeys" />
                </div>

                <div
                    class="card is-compact"
                >
                    <TwoFactorForm :two-factor="twoFactor" />
                </div>

                <div
                    class="card is-compact"
                >
                    <SecurityActivity :events="events" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
