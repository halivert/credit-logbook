<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import Pagination from "@/Components/Pagination.vue"

defineProps<{
    creditCards: { data: CreditCard[] }
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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <div class="flex flex-wrap">
                        <Link
                            class="max-w-full w-60 bg-blue-100 rounded p-2"
                            v-for="creditCard in creditCards.data"
                            :href="route('credit-cards.show', creditCard)"
                            :key="creditCard.id"
                        >
                            <p>
                                {{ creditCard.name }}
                            </p>
                            <p>
                                {{ creditCard.due_date }}
                            </p>
                            <p>
                                {{ creditCard.closing_date }}
                            </p>
                        </Link>
                    </div>

                    <Pagination :links="creditCards.links"></Pagination>

                    <div class="text-right">
                        <Link
                            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            :href="route('credit-cards.create')"
                        >
                            Agregar tarjeta
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
