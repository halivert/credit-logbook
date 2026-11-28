<script setup lang="ts">
import type { CreditCard, LengthAwarePaginator } from "@/types"
import { Head, Link } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import CreditCardSummary from "@/Components/CreditCards/CreditCardSummary.vue"
import Pagination from "@/Components/Pagination.vue"

defineProps<{
    creditCards: LengthAwarePaginator<CreditCard>
}>()
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
                            <CreditCardSummary :credit-card="creditCard" />
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
