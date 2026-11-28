<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, useForm } from "@inertiajs/vue3"

const form = useForm({
    password: "",
})

function submit() {
    form.post(route("password.confirm.store"), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Confirma tu contraseña" />

        <h1 class="text-lg font-semibold">Confirma tu contraseña</h1>
        <p class="mt-1 mb-4 text-sm text-gray-600 dark:text-gray-400">
            Por seguridad, escríbela de nuevo antes de continuar.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Confirmar</PrimaryButton
            >
        </form>
    </GuestLayout>
</template>
