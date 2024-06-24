<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"

const props = defineProps<{
    creditCard: CreditCard
    transactions: { data: Transaction[] }
}>()

const page = usePage()

const toCurrency = new Intl.NumberFormat(page.props.locale, {
    style: "currency",
    currency: "MXN",
})

const toDate = new Intl.DateTimeFormat(page.props.locale, {
    dateStyle: "short",
})

const limit = toCurrency.format(props.creditCard.limit)
</script>

<template>
    <Head title="Tarjeta de crédito" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Tarjeta {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <div>{{ limit }}</div>

                    <div>
                        <h3 class="font-semibold text-lg mt-3 mb-1">
                            Transacciones
                        </h3>

                        <ul v-if="transactions.data.length">
                            <li
                                v-for="transaction in transactions.data"
                                :key="transaction.id"
                            >
                                {{ transaction.concept }} -
                                {{ toCurrency.format(transaction.amount) }}

                                {{
                                    toDate.format(
                                        new Date(transaction.datetime),
                                    )
                                }}
                            </li>
                        </ul>

                        <Pagination :links="transactions.links" />

                        <div class="text-right">
                            <Link
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                :href="
                                    route(
                                        'credit-cards.transactions.create',
                                        creditCard,
                                    )
                                "
                            >
                                Nueva transacción
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
