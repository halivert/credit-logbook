<script setup lang="ts">
import type { CreditCard, Purchase } from "@/types"
import { Head, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"

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
            <h2 class="text-2xl sm:text-3xl text-ink truncate">
                {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-lg mx-auto px-4 sm:px-6">
                <div class="nes-container is-tight">
                    <form class="space-y-5" @submit.prevent="submit">
                        <h3 class="text-xl uppercase tracking-widest text-muted">
                            {{
                                purchase.is_refund
                                    ? "Editar devolución"
                                    : purchase.is_installment_purchase
                                      ? "Editar compra a meses"
                                      : "Editar compra"
                            }}
                        </h3>

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

                        <div class="grid grid-cols-2 gap-4">
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
                                <InputLabel
                                    for="amount"
                                    :value="
                                        purchase.is_refund
                                            ? 'Te devuelven'
                                            : 'Importe'
                                    "
                                />

                                <div class="relative mt-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-2xl"
                                        >$</span
                                    >
                                    <TextInput
                                        id="amount"
                                        type="text"
                                        inputmode="decimal"
                                        class="block w-full pl-8"
                                        v-model="form.amount"
                                        required
                                    />
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.amount"
                                />
                            </div>
                        </div>

                        <template v-if="purchase.is_installment_purchase">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel
                                        for="installment_count"
                                        value="Meses"
                                    />

                                    <TextInput
                                        id="installment_count"
                                        type="number"
                                        inputmode="numeric"
                                        min="1"
                                        max="60"
                                        class="mt-1 block w-full"
                                        v-model="form.installment_count"
                                        required
                                    />

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.installment_count"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        for="installment_amount"
                                        value="Mensualidad"
                                    />

                                    <div class="relative mt-1">
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-2xl"
                                            >$</span
                                        >
                                        <TextInput
                                            id="installment_amount"
                                            type="text"
                                            inputmode="decimal"
                                            class="block w-full pl-8"
                                            v-model="form.installment_amount"
                                            required
                                        />
                                    </div>

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.installment_amount"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-4">
                                <div class="grid grid-rows-subgrid row-span-3">
                                    <InputLabel for="commission" class="self-end">
                                        Comisión
                                        <span class="text-muted">(opcional)</span>
                                    </InputLabel>

                                    <div class="relative mt-1">
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-2xl"
                                            >$</span
                                        >
                                        <TextInput
                                            id="commission"
                                            type="text"
                                            inputmode="decimal"
                                            class="block w-full pl-8"
                                            v-model="form.commission"
                                        />
                                    </div>

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.commission"
                                    />
                                </div>

                                <div class="grid grid-rows-subgrid row-span-3">
                                    <InputLabel for="interest_rate" class="self-end">
                                        Tasa anual
                                        <span class="text-muted">(opcional)</span>
                                    </InputLabel>

                                    <div class="relative mt-1">
                                        <TextInput
                                            id="interest_rate"
                                            type="text"
                                            inputmode="decimal"
                                            class="block w-full pr-8"
                                            v-model="form.interest_rate"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted text-2xl"
                                            >%</span
                                        >
                                    </div>

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.interest_rate"
                                    />
                                </div>
                            </div>
                        </template>

                        <div>
                            <InputLabel for="applied_on">
                                Fecha de aplicación
                                <span class="text-muted">(opcional)</span>
                            </InputLabel>

                            <TextInput
                                id="applied_on"
                                type="date"
                                class="mt-1 block w-1/2"
                                :min="form.date"
                                v-model="form.applied_on"
                            />

                            <p class="mt-1 text-lg text-muted">
                                Cuando el banco la carga después de la fecha
                                de compra.
                            </p>

                            <InputError
                                class="mt-2"
                                :message="form.errors.applied_on"
                            />
                        </div>

                        <PrimaryButton
                            type="submit"
                            class="w-full justify-center sm:w-auto"
                            :disabled="form.processing"
                            >Guardar</PrimaryButton
                        >
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
