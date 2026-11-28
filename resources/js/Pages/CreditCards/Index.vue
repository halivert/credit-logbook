<script setup lang="ts">
import type { CreditCard, LengthAwarePaginator } from "@/types"
import { Head, Link } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import Pagination from "@/Components/Pagination.vue"
import { daysUntil, useFormat } from "@/format"

defineProps<{
    creditCards: LengthAwarePaginator<CreditCard>
}>()

const { toCurrency, toShortDate } = useFormat()

function usage(creditCard: CreditCard): number {
    const limit = Number(creditCard.limit)

    if (limit <= 0) return 0

    return Math.min(100, ((creditCard.balance_used ?? 0) / limit) * 100)
}

/** Only worth pointing out when it's close */
function dueSoon(creditCard: CreditCard): string | null {
    const days = daysUntil(creditCard.next_due_date)

    if (days === 0) return "hoy"
    if (days === 1) return "mañana"
    if (days > 1 && days <= 7) return `en ${days} días`

    return null
}
</script>

<template>
    <Head title="Tarjetas de crédito" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Tarjetas
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6">
                <ul
                    v-if="creditCards.data.length"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li v-for="creditCard in creditCards.data" :key="creditCard.id">
                        <Link
                            :href="route('credit-cards.show', creditCard)"
                            class="block h-full rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-indigo-300 dark:hover:ring-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        >
                            <p class="font-semibold">{{ creditCard.name }}</p>

                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Disponible
                            </p>
                            <p class="text-2xl font-semibold tabular-nums">
                                {{ toCurrency(creditCard.available_credit ?? 0) }}
                            </p>

                            <div
                                class="mt-3 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"
                                role="progressbar"
                                :aria-valuenow="Math.round(usage(creditCard))"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Crédito usado"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="
                                        usage(creditCard) >= 90
                                            ? 'bg-red-500'
                                            : 'bg-indigo-500'
                                    "
                                    :style="{ width: `${usage(creditCard)}%` }"
                                />
                            </div>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400 tabular-nums"
                            >
                                {{ toCurrency(creditCard.balance_used ?? 0) }} usados de
                                {{ toCurrency(creditCard.limit) }}
                            </p>

                            <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">
                                        Corte
                                    </dt>
                                    <dd>{{ toShortDate(creditCard.next_closing_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">
                                        Pago
                                    </dt>
                                    <dd
                                        :class="{
                                            'font-semibold text-amber-600 dark:text-amber-400':
                                                dueSoon(creditCard),
                                        }"
                                    >
                                        {{ toShortDate(creditCard.next_due_date) }}
                                        <span v-if="dueSoon(creditCard)">
                                            · {{ dueSoon(creditCard) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </Link>
                    </li>
                </ul>

                <div
                    v-else
                    class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center"
                >
                    <p class="font-semibold">Aún no tienes tarjetas</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Agrega una para llevar sus compras, pagos y fechas.
                    </p>
                </div>

                <Pagination
                    v-if="creditCards.last_page > 1"
                    :links="creditCards.links"
                />

                <Link
                    class="flex w-full sm:w-auto sm:inline-flex justify-center items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    :href="route('credit-cards.create')"
                >
                    Agregar tarjeta
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
