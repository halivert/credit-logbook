<script setup lang="ts">
import type { CreditCard, Purchase, SimplePaginator } from "@/types"
import { Head, Link, router } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import CreditCardSummary from "@/Components/CreditCards/CreditCardSummary.vue"
import PurchaseRow from "@/Components/CreditCards/PurchaseRow.vue"
import PencilIcon from "@/Components/Icons/PencilIcon.vue"

defineProps<{
    creditCard: CreditCard
    purchases: SimplePaginator<Purchase>
    installmentPurchases: Purchase[]
}>()

function deletePurchase(purchase: Purchase) {
    if (confirm(`¿Borrar "${purchase.concept}"?`)) {
        router.delete(route("purchases.destroy", purchase), {
            preserveScroll: true,
        })
    }
}

const button =
    "inline-flex justify-center items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition"
</script>

<template>
    <Head :title="creditCard.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2
                    class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate"
                >
                    {{ creditCard.name }}
                </h2>

                <Link
                    :href="route('credit-cards.edit', creditCard)"
                    class="rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <PencilIcon class="size-5" />
                    <span class="sr-only">Editar tarjeta</span>
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
                <section
                    class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <CreditCardSummary
                        :credit-card="creditCard"
                        :show-name="false"
                    />
                </section>

                <div class="grid grid-cols-2 gap-3">
                    <Link
                        :href="route('credit-cards.purchases.create', creditCard)"
                        :class="[
                            button,
                            'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 hover:bg-gray-700 dark:hover:bg-white',
                        ]"
                    >
                        Agregar compra
                    </Link>
                    <Link
                        :href="route('credit-cards.payments.create', creditCard)"
                        :class="[
                            button,
                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                        ]"
                    >
                        Registrar pago
                    </Link>
                </div>

                <Link
                    :href="route('credit-cards.reconciliation.show', creditCard)"
                    class="block text-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Conciliar con tu estado de cuenta
                </Link>

                <section v-if="installmentPurchases.length">
                    <h3
                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        A meses
                    </h3>
                    <ul
                        class="rounded-lg bg-white dark:bg-gray-800 px-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <PurchaseRow
                            v-for="purchase in installmentPurchases"
                            :key="purchase.id"
                            :purchase="purchase"
                            @delete="deletePurchase"
                        />
                    </ul>
                </section>

                <section>
                    <h3
                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Compras
                    </h3>
                    <ul
                        v-if="purchases.data.length"
                        class="rounded-lg bg-white dark:bg-gray-800 px-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <PurchaseRow
                            v-for="purchase in purchases.data"
                            :key="purchase.id"
                            :purchase="purchase"
                            @delete="deletePurchase"
                        />
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        Aún no hay compras en esta tarjeta.
                    </p>

                    <nav
                        v-if="purchases.prev_page_url || purchases.next_page_url"
                        class="mt-3 flex justify-between text-sm"
                    >
                        <Link
                            v-if="purchases.prev_page_url"
                            :href="purchases.prev_page_url"
                            preserve-scroll
                            class="text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            ← Más recientes
                        </Link>
                        <span v-else />
                        <Link
                            v-if="purchases.next_page_url"
                            :href="purchases.next_page_url"
                            preserve-scroll
                            class="text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            Más antiguas →
                        </Link>
                    </nav>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
