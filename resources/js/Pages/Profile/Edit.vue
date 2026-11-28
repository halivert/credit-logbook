<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import DeleteUserForm from "./Partials/DeleteUserForm.vue"
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue"
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue"
import { Head, Link } from "@inertiajs/vue3"

defineProps<{
    mustVerifyEmail?: boolean
    status?: string
}>()
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-large sm:text-title text-ink"
            >
                Perfil
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div
                    class="card is-compact"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="card is-compact"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <Link
                    :href="route('profile.security')"
                    class="card is-compact is-link flex items-center justify-between"
                >
                    <span>
                        <span class="block text-large">Seguridad</span>
                        <span class="block text-small text-muted"
                            >Verificación en dos pasos y passkeys</span
                        >
                    </span>
                    <span aria-hidden="true" class="text-muted">&gt;</span>
                </Link>

                <div
                    class="card is-compact"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
