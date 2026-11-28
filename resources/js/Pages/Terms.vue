<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"
import ApplicationLogo from "@/Components/ApplicationLogo.vue"

defineProps<{
    owner: string | null
    email: string | null
}>()
</script>

<template>
    <Head title="Términos de uso" />

    <div class="min-h-screen bg-page px-4 py-10 sm:py-16">
        <article class="reading max-w-2xl mx-auto space-y-6">
            <Link href="/" class="inline-block">
                <ApplicationLogo class="size-16 fill-current text-ink" />
                <span class="sr-only">Inicio</span>
            </Link>

            <header>
                <h1 class="text-title">Términos de uso</h1>
                <p class="text-small text-muted">
                    Última actualización: 28 de noviembre de 2026
                </p>
            </header>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Qué es Tarjetero</h2>
                <p>
                    Una libreta digital para llevar tú mismo la cuenta de tus
                    tarjetas de crédito: lo que compras, lo que pagas y cuándo.
                    Todo lo que ves sale de lo que tú anotas.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Lo que no hace</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>
                        No se conecta a ningún banco ni a tus tarjetas.
                    </li>
                    <li>
                        No hace pagos ni los programa. Registrar un pago solo
                        lo anota aquí: el pago lo haces tú en tu banco.
                    </li>
                    <li>
                        Lo que anotas o dejas de anotar no cambia nada en tu
                        tarjeta. Una compra que no registras igual te la cobra
                        el banco, y un pago que registras sin hacerlo no cuenta
                        para el banco.
                    </li>
                </ul>
                <p>
                    Lo que vale siempre es tu estado de cuenta y lo que diga tu
                    banco.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Las cifras son estimaciones</h2>
                <p>
                    Saldos, pagos para no generar intereses, fechas y
                    mensualidades se calculan con lo que anotas y con reglas
                    generales. Pueden no coincidir con tu banco: intereses,
                    comisiones, pagos mínimos, cargos que no anotaste o fechas
                    que el banco mueve. Para encontrar diferencias, concilia
                    con tu estado de cuenta.
                </p>
                <p>Tarjetero no es asesoría financiera.</p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Tu responsabilidad</h2>
                <p>
                    Pagar tus tarjetas a tiempo y revisar tus estados de cuenta
                    te corresponde a ti. Tarjetero no responde por intereses,
                    comisiones, recargos, afectaciones a tu historial de
                    crédito ni ningún otro daño que resulte de usar, o de dejar
                    de usar, la información que muestra.
                </p>
                <p>
                    Tu cuenta es personal: cuida tu contraseña. Podemos
                    suspender cuentas que se usen para abusar del servicio.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Versión beta</h2>
                <p>
                    Tarjetero está en beta y se ofrece tal cual: puede tener
                    errores, cambiar o dejar de estar disponible. Si fuera a
                    cerrar, te avisaremos con anticipación.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Lo demás</h2>
                <p>
                    Cómo cuidamos tus datos está en el
                    <Link :href="route('privacy')" class="link"
                        >aviso de privacidad</Link
                    >. Si estos términos cambian, lo publicaremos aquí con la
                    nueva fecha; seguir usando Tarjetero significa que los
                    aceptas. Se rigen por las leyes de México.
                </p>
                <p v-if="email">
                    Dudas:
                    <a :href="`mailto:${email}`" class="link">{{ email }}</a
                    >{{ owner ? ` (${owner})` : "" }}.
                </p>
            </section>
        </article>
    </div>
</template>
