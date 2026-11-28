<script setup lang="ts">
import type { CreditCard } from "@/types"
import { Head, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import { today, useFormat } from "@/format"

const props = defineProps<{
    creditCard: CreditCard
}>()

const { toCurrency } = useFormat()


const balance = props.creditCard.balance_used ?? 0
const statement = props.creditCard.current_statement

// What's usually paid: what's missing to avoid interest
const toAvoidInterest = statement?.remaining ?? 0

const form = useForm({
    date: today(),
    amount: toAvoidInterest > 0 ? toAvoidInterest.toFixed(2) : "",
})

function submit() {
    form.post(route("credit-cards.payments.store", props.creditCard))
}
</script>

<template>
    <Head title="Registrar pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-2xl sm:text-3xl text-ink truncate"
            >
                {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-lg mx-auto px-4 sm:px-6">
                <div
                    class="nes-container is-tight"
                >
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <p class="text-base text-muted">
                                Saldo actual
                            </p>
                            <p class="text-3xl sm:text-4xl leading-none">
                                {{ toCurrency(balance) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="amount" value="Pagaste" />

                                <div class="relative mt-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-2xl"
                                        >$</span
                                    >
                                    <TextInput
                                        id="amount"
                                        type="text"
                                        inputmode="decimal"
                                        min="0.01"
                                        class="block w-full pl-8"
                                        v-model="form.amount"
                                        required
                                        autofocus
                                    />
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.amount"
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
                        </div>

                        <div class="flex flex-col items-start gap-2 text-lg">
                            <button
                                v-if="toAvoidInterest > 0"
                                type="button"
                                class="px-link"
                                @click="form.amount = toAvoidInterest.toFixed(2)"
                            >
                                Para no generar intereses
                                ({{ toCurrency(toAvoidInterest) }})
                            </button>
                            <button
                                v-if="balance > 0"
                                type="button"
                                class="px-link"
                                @click="form.amount = balance.toFixed(2)"
                            >
                                Todo el saldo ({{ toCurrency(balance) }})
                            </button>
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
