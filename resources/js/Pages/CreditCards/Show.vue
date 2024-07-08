<script setup lang="ts">
import { Head, Link, usePage, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import PurchaseRow from "@/Components/CreditCards/PurchaseRow.vue"
import Pagination from "@/Components/Pagination.vue"

const props = defineProps<{
    creditCard: CreditCard
    purchases: { data: Purchase[] }
    installmentPurchases: Purchase[]
}>()

const page = usePage()
const deleteTransactionForm = useForm({})

const toDate = (date: Date | string) =>
    new Intl.DateTimeFormat(page.props.locale, {
        dateStyle: "short",
    }).format(new Date(date))

const toCurrency = (num: number) =>
    new Intl.NumberFormat(page.props.locale, {
        style: "currency",
        currency: "MXN",
    }).format(num)

const limit = toCurrency(props.creditCard.limit)

function deletePurchase(purchase: Purchase) {
    const answer = confirm("¿De verdad quieres borrar esta compra?")

    if (answer) {
        deleteTransactionForm.delete(route("purchases.destroy", purchase))
    }
}
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
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3 space-y-6"
                >
                    <section class="flex justify-between">
                        <div>
                            <p class="text-xl">{{ limit }}</p>
                            <p>
                                Fecha de corte:
                                {{ toDate(creditCard.next_closing_date) }}
                            </p>
                            <p>
                                Fecha de pago:
                                {{ toDate(creditCard.next_due_date) }}
                            </p>
                        </div>

                        <div class="text-right">
                            <Link
                                class="text-indigo-500 hover:text-indigo-700 focus:text-indigo-700 border-transparent rounded outline-none focus:ring-2 focus:ring-indigo-700 active:text-white active:bg-indigo-500 inline-block"
                                :href="route('credit-cards.edit', creditCard)"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="size-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </section>

                    <section>
                        <div class="text-right">
                            <Link
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                                :href="
                                    route(
                                        'credit-cards.payments.create',
                                        creditCard,
                                    )
                                "
                            >
                                Registrar pago
                            </Link>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <h3 class="font-semibold text-lg mt-3 mb-1">
                            Compras a plazos
                        </h3>

                        <ul
                            v-if="installmentPurchases.length"
                            class="space-y-2"
                        >
                            <li
                                v-for="purchase in installmentPurchases"
                                :key="purchase.id"
                                class="flex justify-between items-center px-3 py-1"
                            >
                                <div>
                                    <strong class="font-bold">
                                        {{ purchase.concept }}
                                    </strong>
                                    <p
                                        :class="[
                                            'italic',
                                            {
                                                'line-through':
                                                    !purchase.remaining_months,
                                            },
                                        ]"
                                    >
                                        {{ toCurrency(purchase.amount) }}
                                    </p>
                                    <p v-if="purchase.remaining_months">
                                        <span class="text-zinc-600">
                                            Restan:
                                        </span>
                                        {{ purchase.remaining_months }}/{{
                                            purchase.installment_count
                                        }}
                                        -
                                        {{
                                            toCurrency(
                                                purchase.remaining_amount,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <Link
                                        class="text-indigo-500 hover:text-indigo-700 focus:text-indigo-700 border-transparent rounded outline-none focus:ring-2 focus:ring-indigo-700 active:text-white active:bg-indigo-500"
                                        :href="
                                            route('purchases.edit', purchase)
                                        "
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="size-5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                            />
                                        </svg>
                                    </Link>

                                    <button
                                        class="text-red-500 hover:text-red-700 focus:text-red-700 border-transparent rounded outline-none focus:ring-2 focus:ring-red-700 active:text-white active:bg-red-500"
                                        @click="deletePurchase(purchase)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="size-5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-lg mt-3 mb-1">
                                Compras
                            </h3>

                            <div class="text-right">
                                <Link
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                    :href="
                                        route(
                                            'credit-cards.purchases.create',
                                            creditCard,
                                        )
                                    "
                                >
                                    Agregar compra
                                </Link>
                            </div>
                        </div>

                        <ul v-if="purchases.data.length" class="space-y-2">
                            <PurchaseRow
                                v-for="purchase in purchases.data"
                                :key="purchase.id"
                                :purchase="purchase"
                            >
                                <template #actions>
                                    <div class="flex gap-2">
                                        <Link
                                            class="text-indigo-500 hover:text-indigo-700 focus:text-indigo-700 border-transparent rounded outline-none focus:ring-2 focus:ring-indigo-700 active:text-white active:bg-indigo-500"
                                            :href="
                                                route(
                                                    'purchases.edit',
                                                    purchase,
                                                )
                                            "
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                                />
                                            </svg>
                                        </Link>

                                        <button
                                            class="text-red-500 hover:text-red-700 focus:text-red-700 border-transparent rounded outline-none focus:ring-2 focus:ring-red-700 active:text-white active:bg-red-500"
                                            @click="deletePurchase(purchase)"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </PurchaseRow>
                        </ul>

                        <Pagination v-bind="purchases" />
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
