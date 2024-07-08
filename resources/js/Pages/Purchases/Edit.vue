<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import Checkbox from "@/Components/Checkbox.vue"

const props = defineProps<{
    creditCard: CreditCard
    purchase: Purchase
}>()

function getDate(
    possibleDate: string | Date | null | undefined,
    defaultValue: string = "",
) {
    return possibleDate ? new Date(possibleDate) : defaultValue
}

const form = useForm({
    concept: props.purchase.concept,
    datetime: getDate(props.purchase.datetime),
    amount: props.purchase.amount,
    installment_count: props.purchase.installment_count,
    installment_amount: props.purchase.installment_amount ?? "",
    commission: props.purchase.commission ?? "",
    interest_rate: props.purchase.interest_rate ?? "",
    applied_at: getDate(props.purchase.applied_at),
})

function submit() {
    form.patch(route("purchases.update", props.purchase))
}
</script>

<template>
    <Head title="Nueva compra" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Agregar compra: {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <form class="flex flex-col gap-2" @submit.prevent="submit">
                        <div>
                            <InputLabel for="concept" value="Concepto" />

                            <TextInput
                                id="concept"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.concept"
                                required
                                autofocus
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.concept"
                            />
                        </div>

                        <div>
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

                        <div>
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

                        <div
                            class="flex flex-wrap gap-2"
                            v-if="purchase.is_installment_purchase"
                        >
                            <div class="flex-1 min-w-32">
                                <InputLabel
                                    for="installment_count"
                                    value="Plazos"
                                />

                                <TextInput
                                    id="installment_count"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                    v-model="form.installment_count"
                                    required
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.installment_count"
                                />
                            </div>

                            <div class="flex-1 min-w-32">
                                <InputLabel
                                    for="installment_amount"
                                    value="Importe de plazo"
                                />

                                <TextInput
                                    id="installment_amount"
                                    type="number"
                                    min="0"
                                    :max="form.amount"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                    v-model="form.installment_amount"
                                    required
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.installment_amount"
                                />
                            </div>
                        </div>

                        <div
                            class="flex flex-wrap gap-2"
                            v-if="purchase.is_installment_purchase"
                        >
                            <div class="flex-1 min-w-32">
                                <InputLabel
                                    for="commission"
                                    value="% Comisión"
                                />

                                <TextInput
                                    id="commission"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                    v-model="form.commission"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.commission"
                                />
                            </div>

                            <div class="flex-1 min-w-32">
                                <InputLabel
                                    for="interest_rate"
                                    value="% Intereses"
                                />

                                <TextInput
                                    id="interest_rate"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model="form.interest_rate"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.interest_rate"
                                />
                            </div>
                        </div>

                        <fieldset
                            class="border-t-2 border-indigo-200 py-2 mt-4"
                        >
                            <legend
                                class="bg-white px-3 ml-auto text-indigo-600"
                            >
                                Opcional
                            </legend>

                            <div>
                                <InputLabel
                                    for="applied_at"
                                    value="Fecha de aplicación"
                                />

                                <TextInput
                                    id="applied_at"
                                    type="date"
                                    class="mt-1 block w-full"
                                    :placeholder="form.datetime"
                                    v-model="form.applied_at"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.applied_at"
                                />
                            </div>
                        </fieldset>

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
