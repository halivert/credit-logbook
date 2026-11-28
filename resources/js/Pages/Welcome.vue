<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"
import ApplicationLogo from "@/Components/ApplicationLogo.vue"
import LogbookTour from "@/Components/Welcome/LogbookTour.vue"

defineProps<{
    canLogin?: boolean
    canRegister?: boolean
}>()

const features = [
    {
        title: "Anota lo que gastas",
        text: "Compras, compras a meses, devoluciones y pagos de cada tarjeta.",
    },
    {
        title: "Paga a tiempo",
        text: "Cuánto pagar para no generar intereses y antes de qué día.",
    },
    {
        title: "Cuadra con el banco",
        text: "Concilia con tu estado de cuenta y encuentra lo que falta.",
    },
]
</script>

<template>
    <Head title="Bienvenido" />

    <div class="min-h-screen bg-page">
        <header
            class="flex flex-col items-center gap-6 px-4 pt-16 pb-12 text-center"
        >
            <ApplicationLogo class="size-32 fill-current text-ink" />

            <h1 class="reading text-display sm:text-hero">Tarjetero</h1>

            <p class="reading max-w-xl text-large text-muted">
                Lleva tus tarjetas de crédito al día: cuánto debes, cuánto te
                queda y cuándo pagar.
            </p>

            <nav v-if="canLogin" class="actions justify-center">
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
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 pb-16 space-y-12">
            <ul class="grid gap-4 sm:grid-cols-3">
                <li
                    v-for="feature in features"
                    :key="feature.title"
                    class="card is-compact"
                >
                    <h2 class="reading text-large">{{ feature.title }}</h2>
                    <p class="mt-1 text-small text-muted">{{ feature.text }}</p>
                </li>
            </ul>

            <section aria-labelledby="demo-title">
                <h2 id="demo-title" class="mb-4 text-title text-center">
                    Así funciona
                </h2>

                <LogbookTour />
            </section>
        </main>

        <footer class="pb-10 text-center text-note">
            <Link :href="route('privacy')" class="link">
                Aviso de privacidad
            </Link>
        </footer>
    </div>
</template>
