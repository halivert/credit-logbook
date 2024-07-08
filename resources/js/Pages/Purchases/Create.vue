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
}>()

const datetime = new Date().toISOString().split("T")[0]

const form = useForm({
    is_installment_purchase: false,
    concept: "",
    datetime,
    amount: "",
    installment_count: "",
    installment_amount: "",
    commission: "",
    interest_rate: "",
})

function submit() {
    form.transform((data) => ({
        ...data,
        datetime: new Date(data.datetime + "T00:00"),
    })).post(route("credit-cards.purchases.store", props.creditCard))
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
                    <form class="space-y-2" @submit.prevent="submit">
                        <div>
                            <div class="flex gap-2 flex-wrap items-center">
                                <InputLabel
                                    class="inline-block"
                                    for="is_installment_purchase"
                                    value="Compra a plazos"
                                />

                                <Checkbox
                                    id="is_installment_purchase"
                                    class="rounded accent-red-700"
                                    v-model="form.is_installment_purchase"
                                    :checked="form.is_installment_purchase"
                                />
                            </div>

                            <InputError
                                class="mt-2"
                                :message="form.errors.is_installment_purchase"
                            />
                        </div>

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
                            v-if="form.is_installment_purchase"
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
                            v-if="form.is_installment_purchase"
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
