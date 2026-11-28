<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"

const form = useForm({
    name: "",
    username: "",
    email: "",
    password: "",
    password_confirmation: "",
})

function submit() {
    form.post(route("register.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <h1 class="mb-4 text-3xl">Crear cuenta</h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nombre" />
                <TextInput
                    id="name"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="username" value="Usuario" />
                <TextInput
                    id="username"
                    class="mt-1 block w-full"
                    v-model="form.username"
                    required
                    autocapitalize="off"
                    autocomplete="off"
                />
                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="email" value="Correo" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confírmala" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Crear cuenta</PrimaryButton
            >

            <p class="text-center text-lg text-muted">
                ¿Ya tienes cuenta?
                <Link
                    :href="route('login')"
                    class="px-link"
                    >Inicia sesión</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>
