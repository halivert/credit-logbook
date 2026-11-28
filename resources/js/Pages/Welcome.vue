<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"
import ApplicationLogo from "@/Components/ApplicationLogo.vue"

defineProps<{
    canLogin?: boolean
    canRegister?: boolean
}>()
</script>

<template>
    <Head title="Bienvenido" />

    <div
        class="min-h-screen flex flex-col items-center justify-center gap-8 px-4 py-12 bg-page text-center"
    >
        <ApplicationLogo class="size-32 fill-current text-ink" />

        <h1 class="text-display sm:text-hero">Bitácora de crédito</h1>

        <nav v-if="canLogin" class="flex flex-wrap justify-center gap-2">
            <Link
                v-if="$page.props.auth.user"
                :href="route('credit-cards.index')"
                class="btn is-primary"
                >Tarjetas</Link
            >

            <template v-else>
                <Link :href="route('login')" class="btn is-primary"
                    >Iniciar sesión</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="btn"
                    >Registrarse</Link
                >
            </template>
        </nav>

        <p
            v-if="canLogin && !$page.props.auth.user"
            class="section-label blink text-body"
            aria-hidden="true"
        >
            Presiona start
        </p>
    </div>
</template>
