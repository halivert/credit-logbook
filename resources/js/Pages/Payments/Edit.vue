<script setup lang="ts">
import { ref, watch, onMounted } from "vue"
import { Head, Link, useForm, usePage } from "@inertiajs/vue3"
import InstallmentPurchaseNumberInput from "@/Components/Payments/InstallmentPurchaseNumberInput.vue"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import Checkbox from "@/Components/Checkbox.vue"

const page = usePage()

const props = defineProps<{
    creditCard: CreditCard
    purchases: Purchase[]
    installmentPurchases: Purchase[]
}>()

const purchasesById = Object.fromEntries(
    props.purchases.map((purchase) => [purchase.id, purchase]),
)

const installmentPurchasesById = Object.fromEntries(
    props.installmentPurchases.map((purchase) => [purchase.id, purchase]),
)

const datetime = new Date().toISOString().split("T")[0]

const toCurrency = (num: number) =>
    new Intl.NumberFormat(page.props.locale, {
        style: "currency",
        currency: "MXN",
    }).format(num)

const form = useForm({
    datetime,
    amount: "",
    purchases: Object.keys(purchasesById),
    installment_purchases: Object.values(installmentPurchasesById).map(
        (purchase) => ({
            id: purchase.id,
            installment_number: purchase.paid_months + 1,
        }),
    ),
})

onMounted(() => {
    calculateAmount()
})

function calculateAmount() {
    form.amount = (
        form.purchases.reduce((total, purchaseId) => {
            return total + parseFloat(purchasesById[purchaseId].amount)
        }, 0) +
        form.installment_purchases.reduce((total, { id }) => {
            return (
                total +
                parseFloat(installmentPurchasesById[id].installment_amount)
            )
        }, 0)
    ).toFixed(2)
}

function submit() {
    form.post(route("credit-cards.payments.store", props.creditCard))
}
</script>

<template>
    <Head title="Registrar pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Registrar pago: {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <form class="flex flex-col gap-2" @submit.prevent="submit">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="flex-1">
                                <InputLabel for="datetime" value="Fecha" />

                                <TextInput
                                    id="datetime"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.datetime"
                                    required
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.datetime"
                                />
                            </div>

                            <div class="flex-1">
                                <InputLabel for="amount" value="Importe" />

                                <TextInput
                                    id="amount"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.amount"
                                    required
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.amount"
                                />
                            </div>
                        </div>

                        <hr class="my-3" />

                        <h3 class="text-xl text-indigo-600 italic mb-2">
                            Compras incluidas
                        </h3>

                        <div
                            class="grid sm:grid-cols-2 md:grid-cols-3 gap-3 mb-4"
                        >
                            <InstallmentPurchaseNumberInput
                                v-for="purchase in installmentPurchases"
                                :key="purchase.id"
                                :purchase="purchase"
                                v-model="form.installment_purchases"
                            >
                                <InputLabel :for="purchase.id"
                                    >{{ purchase.concept }} ({{
                                        toCurrency(purchase.installment_amount)
                                    }})</InputLabel
                                >
                            </InstallmentPurchaseNumberInput>
                        </div>

                        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <div
                                class="flex gap-2"
                                v-for="purchase in purchases"
                                :key="purchase.id"
                            >
                                <input
                                    :id="purchase.id"
                                    type="checkbox"
                                    class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                    :value="purchase.id"
                                    v-model="form.purchases"
                                    @change="calculateAmount"
                                />

                                <InputLabel :for="purchase.id"
                                    >{{ purchase.concept }} ({{
                                        toCurrency(purchase.amount)
                                    }})</InputLabel
                                >
                            </div>
                        </div>

                        <div class="text-right">
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                                >Guardar</PrimaryButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
