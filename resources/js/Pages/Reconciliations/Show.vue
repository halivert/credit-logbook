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

const secondary = "btn inline-flex justify-center items-center"
</script>

<template>
    <Head :title="`Conciliar ${creditCard.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-large sm:text-title text-ink truncate"
            >
                <Link
                    :href="route('credit-cards.show', creditCard)"
                    class="hover:underline"
                    >{{ creditCard.name }}</Link
                >
                <span class="text-muted">
                    · Conciliar</span
                >
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-lg mx-auto px-4 sm:px-6 space-y-6">
                <section
                    class="card space-y-6"
                >
                    <div>
                        <p class="text-note text-muted">
                            Corte del {{ toShortDate(statement.closing_date) }} ·
                            pago antes del {{ toShortDate(statement.due_date) }}
                        </p>
                        <p class="mt-1 text-small">
                            Para no generar intereses, la bitácora dice
                            <strong>{{
                                toCurrency(statement.to_avoid_interest)
                            }}</strong>
                        </p>
                    </div>

                    <form @submit.prevent="save">
                        <div>
                            <InputLabel
                                for="to_avoid_interest"
                                value="Tu estado de cuenta dice"
                            />
                            <div class="mt-2 flex items-center gap-4">
                                <div class="relative flex-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-large"
                                        >$</span
                                    >
                                    <TextInput
                                        id="to_avoid_interest"
                                        type="text"
                                        inputmode="decimal"
                                        min="0"
                                        class="block w-full pl-8"
                                        v-model="form.to_avoid_interest"
                                        required
                                        :autofocus="!bankAmount"
                                    />
                                </div>
                                <PrimaryButton
                                    class="shrink-0"
                                    :disabled="form.processing"
                                    >Comparar</PrimaryButton
                                >
                            </div>
                            <p
                                class="mt-2 text-note text-muted"
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
                        class="notice is-success"
                    >
                        <p class="font-semibold">Cuadra con tu estado de cuenta</p>
                    </div>

                    <div
                        v-else-if="reconciliation"
                        class="notice is-warning space-y-3"
                    >
                        <div>
                            <p
                                class="text-note text-warning"
                            >
                                Diferencia
                            </p>
                            <p
                                class="text-title leading-none text-warning"
                            >
                                {{ toCurrency(Math.abs(difference)) }}
                            </p>
                        </div>

                        <p
                            v-if="difference > 0"
                            class="text-small text-warning"
                        >
                            El banco te cobra más de lo que tienes registrado.
                            ¿Te faltó anotar una compra o una comisión?
                        </p>
                        <p v-else class="text-small text-warning">
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
                        <p class="text-note text-warning">
                            Si no encuentras la causa, "Ajustar" registra la
                            diferencia en el corte para que cuadre. Puedes
                            borrar el ajuste después.
                        </p>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-3 section-label text-small"
                    >
                        Movimientos del periodo
                    </h3>
                    <ul
                        v-if="lines.length"
                        class="card is-list text-small"
                    >
                        <li
                            v-for="(line, index) in lines"
                            :key="index"
                            class="flex items-start justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ line.concept }}</p>
                                <p class="text-note text-muted">
                                    {{ toShortDate(line.date) }}
                                </p>
                            </div>
                            <p
                                :class="{
                                    'text-success':
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
                        class="card is-empty text-center text-small text-muted"
                    >
                        No hay movimientos en este periodo.
                    </p>
                </section>

                <section v-if="adjustments.length">
                    <h3
                        class="mb-3 section-label text-small"
                    >
                        Ajustes
                    </h3>
                    <ul
                        class="card is-list text-small"
                    >
                        <li
                            v-for="adjustment in adjustments"
                            :key="adjustment.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ adjustment.concept }}</p>
                                <p class="text-note text-muted">
                                    {{ toShortDate(adjustment.date) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p>
                                    {{ Number(adjustment.amount) < 0 ? "−" : "+"
                                    }}{{
                                        toCurrency(Math.abs(Number(adjustment.amount)))
                                    }}
                                </p>
                                <button
                                    type="button"
                                    class="btn is-error is-icon"
                                    @click="removeAdjustment(adjustment)"
                                >
                                    <TrashIcon class="size-4" />
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
