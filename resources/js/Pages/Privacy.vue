<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"
import ApplicationLogo from "@/Components/ApplicationLogo.vue"

defineProps<{
    owner: string | null
    email: string | null
}>()
</script>

<template>
    <Head title="Aviso de privacidad" />

    <div class="min-h-screen bg-page px-4 py-10 sm:py-16">
        <article class="reading max-w-2xl mx-auto space-y-6">
            <Link href="/" class="inline-block">
                <ApplicationLogo class="size-16 fill-current text-ink" />
                <span class="sr-only">Inicio</span>
            </Link>

            <header>
                <h1 class="text-title">Aviso de privacidad</h1>
                <p class="text-small text-muted">
                    Última actualización: 28 de noviembre de 2026
                </p>
            </header>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Quién es responsable</h2>
                <p>
                    {{ owner ?? "Quien administra esta aplicación" }} es
                    responsable de tus datos personales en Tarjetero.
                    <template v-if="email">
                        Para cualquier duda o solicitud escribe a
                        <a :href="`mailto:${email}`" class="link">{{ email }}</a>.
                    </template>
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Qué datos guardamos</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>
                        Tu cuenta: nombre, usuario, correo y
                        contraseña (guardada como hash, nadie la puede leer).
                        Si los activas, tus passkeys y tu segundo factor.
                    </li>
                    <li>
                        Lo que anotas: el nombre de tus
                        tarjetas, su límite (guardado cifrado), sus días de
                        corte y de pago, su tasa, y las compras, pagos y
                        conciliaciones que registras.
                    </li>
                    <li>
                        Seguridad: la dirección IP y el
                        navegador de los inicios de sesión y de los intentos
                        sospechosos. Se borran a los 90 días.
                    </li>
                </ul>
                <p>
                    Nunca pedimos el número de tu tarjeta, su código de
                    seguridad ni acceso a tu banco. No guardamos datos
                    personales sensibles.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Para qué los usamos</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>
                        Darte el servicio: calcular tu saldo, cuánto pagar y
                        cuándo.
                    </li>
                    <li>
                        Proteger tu cuenta: detectar accesos sospechosos y
                        avisarte.
                    </li>
                    <li>
                        Mandarte los correos necesarios: restablecer tu
                        contraseña, verificar tu correo y alertas de
                        seguridad.
                    </li>
                </ul>
                <p>
                    No vendemos tus datos, no mostramos publicidad y no usamos
                    herramientas de análisis ni de rastreo. Las únicas cookies
                    son las necesarias para mantener tu sesión.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Con quién se comparten</h2>
                <p>
                    Solo con los proveedores que necesitamos para funcionar, y
                    solo para eso: el del servidor donde vive la aplicación y
                    el de envío de correos (Resend). Pueden guardar los datos
                    fuera de México. No los compartimos con nadie más, salvo
                    que una autoridad lo pida conforme a la ley.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Tus derechos</h2>
                <p>
                    Puedes borrar tus tarjetas o tu cuenta completa cuando
                    quieras desde la aplicación: se borran para siempre. Las
                    copias de respaldo se eliminan en un máximo de 30 días.
                </p>
                <p>
                    También puedes pedir acceso a tus datos, corregirlos,
                    cancelarlos u oponerte a su uso (derechos ARCO), o
                    retirar tu consentimiento, escribiendo al correo de
                    contacto. Te respondemos en un máximo de 20 días hábiles.
                </p>
            </section>

            <section class="card is-compact space-y-3">
                <h2 class="section-label text-body">Cambios</h2>
                <p>
                    Si este aviso cambia, lo publicaremos en esta página con
                    la nueva fecha.
                </p>
            </section>
        </article>
    </div>
</template>
