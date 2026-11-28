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

const secondary = "nes-btn inline-flex justify-center items-center"
</script>

<template>
    <Head :title="`Conciliar ${creditCard.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-2xl sm:text-3xl text-ink truncate"
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
                    class="nes-container is-tight space-y-5"
                >
                    <div>
                        <p class="text-xs text-muted">
                            Corte del {{ toShortDate(statement.closing_date) }} ·
                            pago antes del {{ toShortDate(statement.due_date) }}
                        </p>
                        <p class="mt-1 text-sm">
                            Para no generar intereses, la bitácora dice
                            <strong>{{
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
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted"
                                        >$</span
                                    >
                                    <TextInput
                                        id="to_avoid_interest"
                                        type="text"
                                        inputmode="decimal"
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
                                class="mt-1 text-xs text-muted"
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
                        class="px-alert bg-success/10 text-success"
                    >
                        <p class="font-semibold">Cuadra con tu estado de cuenta</p>
                    </div>

                    <div
                        v-else-if="reconciliation"
                        class="px-alert bg-warning/10 text-warning space-y-3"
                    >
                        <div>
                            <p
                                class="text-xs text-warning"
                            >
                                Diferencia
                            </p>
                            <p
                                class="text-3xl leading-none text-warning"
                            >
                                {{ toCurrency(Math.abs(difference)) }}
                            </p>
                        </div>

                        <p
                            v-if="difference > 0"
                            class="text-sm text-warning"
                        >
                            El banco te cobra más de lo que tienes registrado.
                            ¿Te faltó anotar una compra o una comisión?
                        </p>
                        <p v-else class="text-sm text-warning">
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
                        <p class="text-xs text-warning">
                            Si no encuentras la causa, "Ajustar" registra la
                            diferencia en el corte para que cuadre. Puedes
                            borrar el ajuste después.
                        </p>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-3 text-base uppercase tracking-widest text-muted"
                    >
                        Movimientos del periodo
                    </h3>
                    <ul
                        v-if="lines.length"
                        class="nes-container !py-0 !px-4 px-list"
                    >
                        <li
                            v-for="(line, index) in lines"
                            :key="index"
                            class="flex items-start justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ line.concept }}</p>
                                <p class="text-sm text-muted">
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
                        class="nes-container is-dashed text-center text-sm text-muted"
                    >
                        No hay movimientos en este periodo.
                    </p>
                </section>

                <section v-if="adjustments.length">
                    <h3
                        class="mb-3 text-base uppercase tracking-widest text-muted"
                    >
                        Ajustes
                    </h3>
                    <ul
                        class="nes-container !py-0 !px-4 px-list"
                    >
                        <li
                            v-for="adjustment in adjustments"
                            :key="adjustment.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate">{{ adjustment.concept }}</p>
                                <p class="text-sm text-muted">
                                    {{ toShortDate(adjustment.date) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <p>
                                    {{ Number(adjustment.amount) < 0 ? "−" : "+"
                                    }}{{
                                        toCurrency(Math.abs(Number(adjustment.amount)))
                                    }}
                                </p>
                                <button
                                    type="button"
                                    class="nes-btn is-error is-icon !m-0"
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
