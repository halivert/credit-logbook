<script setup lang="ts">
import type { CreditCard, Purchase } from "@/types"
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
    baseInstallmentAmount: string | null
}>()

const form = useForm({
    concept: props.purchase.concept,
    date: props.purchase.date,
    // Returns are negative, the form edits how much was returned
    amount: Math.abs(Number(props.purchase.amount)).toFixed(2),
    installment_count: props.purchase.installment_count,
    installment_amount: props.baseInstallmentAmount ?? "",
    commission: props.purchase.commission ?? "",
    interest_rate: props.purchase.interest_rate ?? "",
    applied_on: props.purchase.applied_on ?? "",
})

function submit() {
    form.patch(route("purchases.update", props.purchase))
}
</script>

<template>
    <Head :title="purchase.is_refund ? 'Editar devolución' : 'Editar compra'" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-sm sm:text-base text-ink"
            >
                {{ purchase.is_refund ? "Editar devolución" : "Editar compra" }}:
                {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="nes-container is-tight"
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
                            <InputLabel for="date" value="Fecha" />

                            <TextInput
                                id="date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.date"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.date"
                            />
                        </div>

                        <div>
                            <InputLabel for="amount" value="Importe" />

                            <TextInput
                                id="amount"
                                type="text"
                                    inputmode="decimal"
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
                                    type="text"
                                    inputmode="decimal"
                                    min="0"
                                    :max="form.amount"
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
                                    type="text"
                                    inputmode="decimal"
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
                                    type="text"
                                    inputmode="decimal"
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
                            class="border-t-4 border-dashed border-primary/40 py-2 mt-4"
                        >
                            <legend
                                class="bg-surface px-3 ml-auto font-pixel text-[0.625rem] text-primary"
                            >
                                Opcional
                            </legend>

                            <div>
                                <InputLabel
                                    for="applied_on"
                                    value="Fecha de aplicación"
                                />

                                <TextInput
                                    id="applied_on"
                                    type="date"
                                    class="mt-1 block w-full"
                                    :min="form.date"
                                    v-model="form.applied_on"
                                />

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.applied_on"
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
