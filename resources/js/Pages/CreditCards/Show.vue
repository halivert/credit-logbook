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
</script>

<template>
    <Head :title="creditCard.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2
                    class="text-2xl sm:text-3xl text-ink truncate"
                >
                    {{ creditCard.name }}
                </h2>

                <Link
                    :href="route('credit-cards.edit', creditCard)"
                    class="nes-btn is-icon !m-0 shrink-0"
                >
                    <PencilIcon class="size-4" />
                    <span class="sr-only">Editar tarjeta</span>
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
                <section
                    class="nes-container is-tight"
                >
                    <CreditCardSummary
                        :credit-card="creditCard"
                        :show-name="false"
                    />
                </section>

                <div class="grid grid-cols-2 gap-4 px-1">
                    <Link
                        :href="route('credit-cards.purchases.create', creditCard)"
                        class="nes-btn is-primary flex justify-center items-center !m-0"
                    >
                        Agregar compra
                    </Link>
                    <Link
                        :href="route('credit-cards.payments.create', creditCard)"
                        class="nes-btn flex justify-center items-center !m-0"
                    >
                        Registrar pago
                    </Link>
                </div>

                <Link
                    :href="route('credit-cards.reconciliation.show', creditCard)"
                    class="block text-center px-link"
                >
                    Conciliar con tu estado de cuenta
                </Link>

                <section v-if="installmentPurchases.length">
                    <h3
                        class="mb-3 text-xl uppercase tracking-widest text-muted"
                    >
                        A meses
                    </h3>
                    <ul
                        class="nes-container !py-0 !px-4 px-list"
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
                        class="mb-3 text-xl uppercase tracking-widest text-muted"
                    >
                        Compras
                    </h3>
                    <ul
                        v-if="purchases.data.length"
                        class="nes-container !py-0 !px-4 px-list"
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
                        class="nes-container is-dashed text-center text-lg text-muted"
                    >
                        Aún no hay compras en esta tarjeta.
                    </p>

                    <nav
                        v-if="purchases.prev_page_url || purchases.next_page_url"
                        class="mt-3 flex justify-between text-lg"
                    >
                        <Link
                            v-if="purchases.prev_page_url"
                            :href="purchases.prev_page_url"
                            preserve-scroll
                            class="px-link"
                        >
                            ← Más recientes
                        </Link>
                        <span v-else />
                        <Link
                            v-if="purchases.next_page_url"
                            :href="purchases.next_page_url"
                            preserve-scroll
                            class="px-link"
                        >
                            Más antiguas →
                        </Link>
                    </nav>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
