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
        <ApplicationLogo class="w-20 h-20 fill-current text-ink" />

        <h1 class="text-base sm:text-xl leading-loose">Bitácora de crédito</h1>

        <nav v-if="canLogin" class="flex flex-wrap justify-center gap-2">
            <Link
                v-if="$page.props.auth.user"
                :href="route('credit-cards.index')"
                class="nes-btn is-primary"
                >Tarjetas</Link
            >

            <template v-else>
                <Link :href="route('login')" class="nes-btn is-primary"
                    >Iniciar sesión</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="nes-btn"
                    >Registrarse</Link
                >
            </template>
        </nav>

        <p
            v-if="canLogin && !$page.props.auth.user"
            class="font-pixel text-[0.625rem] text-muted motion-safe:animate-[blink_1s_steps(1)_infinite]"
            aria-hidden="true"
        >
            Presiona start
        </p>
    </div>
</template>
