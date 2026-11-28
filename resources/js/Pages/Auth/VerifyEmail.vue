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

        <h1 class="text-3xl">Verifica tu correo</h1>
        <p class="mt-1 mb-4 text-lg text-muted">
            Te enviamos un enlace para confirmar tu correo. Si no te llegó,
            te mandamos otro.
        </p>

        <p
            v-if="sent"
            class="mb-4 text-lg font-medium text-success"
        >
            Te enviamos un nuevo enlace.
        </p>

        <form class="space-y-4" @submit.prevent="form.post(route('verification.send'))">
            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Reenviar enlace</PrimaryButton
            >

            <p class="text-center text-lg">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="px-link"
                    >Cerrar sesión</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>
