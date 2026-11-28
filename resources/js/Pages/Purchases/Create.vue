<script setup lang="ts">
import type { CreditCard } from "@/types"
import { computed } from "vue"
import { Head, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import { today, useFormat } from "@/format"

const props = defineProps<{
    creditCard: CreditCard
    /** Coming from reconciling a statement: go back there after saving */
    returnTo?: "reconciliation" | null
    defaultDate?: string | null
    defaultKind?: "refund" | null
}>()

type Kind = "purchase" | "installments" | "refund"

const kinds: { value: Kind; label: string }[] = [
    { value: "purchase", label: "Compra" },
    { value: "installments", label: "A meses" },
    { value: "refund", label: "Devolución" },
]

const { toCurrency } = useFormat()


const form = useForm({
    kind: (props.defaultKind ?? "purchase") as Kind,
    concept: "",
    date: props.defaultDate ?? today(),
    amount: "",
    installment_count: "",
    installment_amount: "",
    commission: "",
})

/**
 * What the bank usually charges: the amount split evenly, rounded up. The
 * server uses the same split when the field is left empty.
 */
const suggestedInstallment = computed(() => {
    const amount = Number(form.amount)
    const count = Number(form.installment_count)

    if (!amount || !count) return null

    // Rounded to 6 decimals first so float noise doesn't add a cent
    return Math.ceil(Number(((amount * 100) / count).toFixed(6))) / 100
})

function submit() {
    form.transform(({ kind, ...data }) => ({
        ...data,
        is_installment_purchase: kind === "installments",
        is_refund: kind === "refund",
        return_to: props.returnTo ?? undefined,
    })).post(route("credit-cards.purchases.store", props.creditCard))
}
</script>

<template>
    <Head title="Nueva compra" />

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
                        <div
                            class="grid grid-cols-3 gap-1 border-4 border-line p-1"
                            role="radiogroup"
                            aria-label="Tipo"
                        >
                            <button
                                v-for="kind in kinds"
                                :key="kind.value"
                                type="button"
                                role="radio"
                                :aria-checked="form.kind === kind.value"
                                class="px-segment"
                                @click="form.kind = kind.value"
                            >
                                {{ kind.label }}
                            </button>
                        </div>

                        <div>
                            <InputLabel for="concept" value="Concepto" />

                            <TextInput
                                id="concept"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.concept"
                                :placeholder="
                                    form.kind === 'refund'
                                        ? 'Ej. Devolución Amazon'
                                        : 'Ej. Súper, gasolina'
                                "
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
                                        form.kind === 'refund'
                                            ? 'Te devuelven'
                                            : 'Importe'
                                    "
                                />

                                <div class="relative mt-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted"
                                        >$</span
                                    >
                                    <TextInput
                                        id="amount"
                                        type="text"
                                        inputmode="decimal"
                                        min="0.01"
                                        class="block w-full pl-7"
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

                        <template v-if="form.kind === 'installments'">
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
                                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted"
                                            >$</span
                                        >
                                        <TextInput
                                            id="installment_amount"
                                            type="text"
                                            inputmode="decimal"
                                            min="0.01"
                                            class="block w-full pl-7"
                                            v-model="form.installment_amount"
                                            :placeholder="
                                                suggestedInstallment?.toFixed(2)
                                            "
                                        />
                                    </div>

                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.installment_amount"
                                    />
                                </div>

                                <p
                                    v-if="suggestedInstallment"
                                    class="col-span-2 -mt-2 text-sm text-muted"
                                >
                                    Si la dejas vacía:
                                    {{ toCurrency(suggestedInstallment) }} al
                                    mes.
                                </p>
                            </div>

                            <div>
                                <InputLabel for="commission">
                                    Comisión por apertura
                                    <span
                                        class="font-normal text-muted"
                                        >(opcional)</span
                                    >
                                </InputLabel>

                                <div class="relative mt-1 w-1/2 pr-2">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted"
                                        >$</span
                                    >
                                    <TextInput
                                        id="commission"
                                        type="text"
                                        inputmode="decimal"
                                        min="0"
                                        class="block w-full pl-7"
                                        v-model="form.commission"
                                    />
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.commission"
                                />
                            </div>
                        </template>

                        <p
                            v-if="form.kind === 'refund'"
                            class="text-sm text-muted"
                        >
                            Se abona a la tarjeta: baja tu saldo y libera
                            crédito.
                        </p>

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
