<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import { computed } from "vue"

const props = defineProps<{
    status?: string
}>()

const form = useForm({})

const sent = computed(() => props.status === "verification-link-sent")
</script>

<template>
    <GuestLayout>
        <Head title="Verifica tu correo" />

        <h1 class="text-lg font-semibold">Verifica tu correo</h1>
        <p class="mt-1 mb-4 text-sm text-gray-600 dark:text-gray-400">
            Te enviamos un enlace para confirmar tu correo. Si no te llegó,
            te mandamos otro.
        </p>

        <p
            v-if="sent"
            class="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400"
        >
            Te enviamos un nuevo enlace.
        </p>

        <form class="space-y-4" @submit.prevent="form.post(route('verification.send'))">
            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Reenviar enlace</PrimaryButton
            >

            <p class="text-center text-sm">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline"
                    >Cerrar sesión</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>
