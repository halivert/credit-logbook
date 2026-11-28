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
                class="text-large sm:text-title text-ink"
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
                            class="card is-compact is-link block h-full"
                        >
                            <CreditCardSummary :credit-card="creditCard" />
                        </Link>
                    </li>
                </ul>

                <div
                    v-else
                    class="card is-empty text-center"
                >
                    <p class="text-large">Aún no tienes tarjetas</p>
                    <p class="mt-1 text-small text-muted">
                        Agrega una para llevar sus compras, pagos y fechas.
                    </p>
                </div>

                <Pagination
                    v-if="creditCards.last_page > 1"
                    :links="creditCards.links"
                />

                <Link
                    class="btn is-primary flex sm:inline-flex justify-center"
                    :href="route('credit-cards.create')"
                >
                    Agregar tarjeta
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
