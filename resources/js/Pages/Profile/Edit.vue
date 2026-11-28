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
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Perfil
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div
                    class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <Link
                    :href="route('profile.security')"
                    class="flex items-center justify-between p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <span>
                        <span class="block text-lg font-medium">Seguridad</span>
                        <span class="block text-sm text-gray-600 dark:text-gray-400"
                            >Verificación en dos pasos y passkeys</span
                        >
                    </span>
                    <span aria-hidden="true" class="text-gray-400">→</span>
                </Link>

                <div
                    class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
