<script setup lang="ts">
import type { CreditCard, Statement } from "@/types"
import { computed } from "vue"
import { Head, Link, router, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import TrashIcon from "@/Components/Icons/TrashIcon.vue"
import { useFormat } from "@/format"

type Line = {
    date: string
    concept: string
    amount: number
    kind: "purchase" | "refund" | "installment" | "adjustment" | "payment"
}

type Adjustment = { id: string; date: string; concept: string; amount: string }

const props = defineProps<{
    creditCard: CreditCard
    statement: Statement
    bankAmount: string | null
    reconciliation: {
        logbook: number
        bank: number
        difference: number
        matched: boolean
    } | null
    lines: Line[]
    adjustments: Adjustment[]
}>()

const { toCurrency, toShortDate } = useFormat()

const form = useForm({
    to_avoid_interest: props.bankAmount ?? "",
})

function save() {
    form.put(route("credit-cards.reconciliation.update", props.creditCard), {
        preserveScroll: true,
    })
}

const difference = computed(() => props.reconciliation?.difference ?? 0)

function adjust() {
    router.post(
        route("credit-cards.adjustments.store", props.creditCard),
        {},
        { preserveScroll: true },
    )
}

function removeAdjustment(adjustment: Adjustment) {
    if (confirm("¿Borrar este ajuste?")) {
        router.delete(route("adjustments.destroy", adjustment.id), {
            preserveScroll: true,
        })
    }
}

const button =
    "inline-flex justify-center items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition"
const secondary = `${button} bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700`
</script>

<template>
    <Head :title="`Conciliar ${creditCard.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate"
            >
                <Link
                    :href="route('credit-cards.show', creditCard)"
                    class="hover:underline"
                    >{{ creditCard.name }}</Link
                >
                <span class="text-gray-500 dark:text-gray-400">
                    · Conciliar</span
                >
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-lg mx-auto px-4 sm:px-6 space-y-6">
                <section
                    class="rounded-lg bg-white dark:bg-gray-800 p-4 sm:p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 space-y-5"
                >
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Corte del {{ toShortDate(statement.closing_date) }} ·
                            pago antes del {{ toShortDate(statement.due_date) }}
                        </p>
                        <p class="mt-1 text-sm">
                            Para no generar intereses, la bitácora dice
                            <strong class="tabular-nums">{{
                                toCurrency(statement.to_avoid_interest)
                            }}</strong>
                        </p>
                    </div>

                    <form class="space-y-3" @submit.prevent="save">
                        <div>
                            <InputLabel
                                for="to_avoid_interest"
                                value="Tu estado de cuenta dice"
                            />
                            <div class="mt-1 flex gap-3">
                                <div class="relative flex-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-500 dark:text-gray-400"
                                        >$</span
                                    >
                                    <TextInput
                                        id="to_avoid_interest"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="0"
                                        class="block w-full pl-7"
                                        v-model="form.to_avoid_interest"
                                        required
                                        :autofocus="!bankAmount"
                                    />
                                </div>
                                <PrimaryButton :disabled="form.processing"
                                    >Comparar</PrimaryButton
                                >
                            </div>
                            <p
                                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                El "pago para no generar intereses" de ese
                                corte.
                            </p>
                            <InputError
                                class="mt-2"
                                :message="form.errors.to_avoid_interest"
                            />
                        </div>
                    </form>

                    <div
                        v-if="reconciliation?.matched"
                        class="rounded-md bg-emerald-50 dark:bg-emerald-900/30 p-4 text-emerald-700 dark:text-emerald-300"
                    >
                        <p class="font-semibold">Cuadra con tu estado de cuenta</p>
                    </div>

                    <div
                        v-else-if="reconciliation"
                        class="rounded-md bg-amber-50 dark:bg-amber-900/30 p-4 space-y-3"
                    >
                        <div>
                            <p
                                class="text-xs text-amber-800 dark:text-amber-300"
                            >
                                Diferencia
                            </p>
                            <p
                                class="text-2xl font-semibold tabular-nums text-amber-800 dark:text-amber-200"
                            >
                                {{ toCurrency(Math.abs(difference)) }}
                            </p>
                        </div>

                        <p
                            v-if="difference > 0"
                            class="text-sm text-amber-900 dark:text-amber-100"
                        >
                            El banco te cobra más de lo que tienes registrado.
                            ¿Te faltó anotar una compra o una comisión?
                        </p>
                        <p v-else class="text-sm text-amber-900 dark:text-amber-100">
                            Tienes registrado más de lo que te cobra el banco.
                            ¿Te faltó un pago o una devolución, o hay algo
                            duplicado?
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <Link
                                :href="
                                    route('credit-cards.purchases.create', [
                                        creditCard,
                                        {
                                            from: 'reconciliation',
                                            kind:
                                                difference > 0
                                                    ? undefined
                                                    : 'refund',
                                        },
                                    ])
                                "
                                :class="secondary"
                            >
                                {{
                                    difference > 0
                                        ? "Agregar compra"
                                        : "Agregar devolución"
                                }}
                            </Link>
                            <button type="button" :class="secondary" @click="adjust">
                                Ajustar
                            </button>
                        </div>
                        <p class="text-xs text-amber-800 dark:text-amber-300">
                            Si no encuentras la causa, "Ajustar" registra la
                            diferencia en el corte para que cuadre. Puedes
                            borrar el ajuste después.
                        </p>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Movimientos del periodo
                    </h3>
                    <ul
                        v-if="lines.length"
                        class="rounded-lg bg-white dark:bg-gray-800 px-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <li
                            v-for="(line, index) in lines"
                            :key="index"
                            class="flex items-start justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ line.concept }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ toShortDate(line.date) }}
                                </p>
                            </div>
                            <p
                                class="tabular-nums"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400':
                                        line.amount < 0,
                                }"
                            >
                                {{ line.amount < 0 ? "−" : ""
                                }}{{ toCurrency(Math.abs(line.amount)) }}
                            </p>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        No hay movimientos en este periodo.
                    </p>
                </section>

                <section v-if="adjustments.length">
                    <h3
                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Ajustes
                    </h3>
                    <ul
                        class="rounded-lg bg-white dark:bg-gray-800 px-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <li
                            v-for="adjustment in adjustments"
                            :key="adjustment.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ adjustment.concept }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ toShortDate(adjustment.date) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="tabular-nums">
                                    {{ Number(adjustment.amount) < 0 ? "−" : "+"
                                    }}{{
                                        toCurrency(Math.abs(Number(adjustment.amount)))
                                    }}
                                </p>
                                <button
                                    type="button"
                                    class="rounded p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500"
                                    @click="removeAdjustment(adjustment)"
                                >
                                    <TrashIcon class="size-5" />
                                    <span class="sr-only">Borrar ajuste</span>
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
