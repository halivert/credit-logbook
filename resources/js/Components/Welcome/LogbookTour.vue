<script setup lang="ts">
import type { CreditCard, Decimal, Purchase, Statement } from "@/types"
import { computed, ref } from "vue"
import { today, useFormat } from "@/format"
import CreditCardSummary from "@/Components/CreditCards/CreditCardSummary.vue"
import PurchaseRow from "@/Components/CreditCards/PurchaseRow.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import SecondaryButton from "@/Components/SecondaryButton.vue"

/**
 * A guided tour of one card through a billing cycle, with the components of
 * the card page and fixed numbers that already add up the way the app
 * computes them. Nothing is calculated here on purpose: the billing rules
 * live on the server.
 *
 * Dates are relative to today so "pago en 5 días" reads right any day.
 */

const { toCurrency, toShortDate } = useFormat()

function day(offset: number): string {
    const [y, m, d] = today().split("-").map(Number)

    return new Date(Date.UTC(y, m - 1, d + offset)).toISOString().slice(0, 10)
}

const LIMIT = 30000

// The closing in the middle of the tour, two weeks ago, due in 5 days
const PREVIOUS_CLOSING = day(-45)
const CLOSING = day(-15)
const DUE = day(5)
const NEXT_CLOSING = day(15)

const decimal = (amount: number) => amount.toFixed(2) as Decimal

function purchase(concept: string, date: string, amount: number): Purchase {
    return {
        id: concept,
        credit_card_id: "tour",
        concept,
        date,
        amount: decimal(amount),
        installment_count: null,
        installment_amount: null,
        commission: null,
        interest_rate: null,
        applied_on: null,
        created_at: "",
        updated_at: "",
        is_installment_purchase: false,
        is_refund: false,
        billed_installments: null,
        remaining_installments: null,
        remaining_amount: 0,
    }
}

/** $12,000 at 12 months: $1,000 billed on each closing */
function laptop(billed: number): Purchase {
    return {
        ...purchase("Laptop", day(-20), 12000),
        installment_count: 12,
        installment_amount: 1000,
        is_installment_purchase: true,
        billed_installments: billed,
        remaining_installments: 12 - billed,
        remaining_amount: 12000 - 1000 * billed,
    }
}

const supermarket = purchase("Súper", day(-27), 1240.5)
const gas = purchase("Gasolina", day(-23), 800)
const restaurant = purchase("Restaurante", day(-12), 650)

type Step = {
    /** Short, for the menu */
    label: string
    title: string
    text: string
    used: number
    statement: Statement
    purchases: Purchase[]
    installmentPurchases: Purchase[]
}

const beforeClosing: Statement = {
    closing_date: PREVIOUS_CLOSING,
    due_date: day(-25),
    next_closing_date: CLOSING,
    to_avoid_interest: 0,
    paid_since_closing: 0,
    remaining: 0,
    period_charges: 0,
    is_overdue: false,
    bank_difference: null,
}

// Billed at the closing: súper + gasolina + the first laptop installment
const TO_AVOID_INTEREST = 1240.5 + 800 + 1000

const afterClosing: Statement = {
    closing_date: CLOSING,
    due_date: DUE,
    next_closing_date: NEXT_CLOSING,
    to_avoid_interest: TO_AVOID_INTEREST,
    paid_since_closing: 0,
    remaining: TO_AVOID_INTEREST,
    period_charges: 650,
    is_overdue: false,
    bank_difference: null,
}

const steps: Step[] = [
    {
        label: "Tu tarjeta",
        title: "Das de alta tu tarjeta",
        text: "Con su límite, su día de corte y su día límite de pago. Todavía no debes nada.",
        used: 0,
        statement: beforeClosing,
        purchases: [],
        installmentPurchases: [],
    },
    {
        label: "Compras",
        title: "Anotas tus compras",
        text: "Cada compra baja tu disponible. La compra a meses aparta el total, pero en cada corte solo se cobra una mensualidad.",
        used: 1240.5 + 800 + 12000,
        statement: { ...beforeClosing, period_charges: 1240.5 + 800 },
        purchases: [gas, supermarket],
        installmentPurchases: [laptop(0)],
    },
    {
        label: "El corte",
        title: "Llega el corte",
        text: `Te dice cuánto pagar para no generar intereses y antes de qué día: el ${toShortDate(DUE)}. Lo que compras después ya es del siguiente corte.`,
        used: 1240.5 + 800 + 12000 + 650,
        statement: afterClosing,
        purchases: [restaurant, gas, supermarket],
        installmentPurchases: [laptop(1)],
    },
    {
        label: "El pago",
        title: "Pagas y quedas cubierto",
        text: `Pagas ${toCurrency(TO_AVOID_INTEREST)} en tu banco y lo anotas aquí: ya no generas intereses y tu disponible se recupera.`,
        used: 1240.5 + 800 + 12000 + 650 - TO_AVOID_INTEREST,
        statement: {
            ...afterClosing,
            paid_since_closing: TO_AVOID_INTEREST,
            remaining: 0,
        },
        purchases: [restaurant, gas, supermarket],
        installmentPurchases: [laptop(1)],
    },
]

const current = ref(0)
const step = computed(() => steps[current.value])
const isLast = computed(() => current.value === steps.length - 1)

const creditCard = computed<CreditCard>(() => ({
    id: "tour",
    user_id: "tour",
    name: "Tarjeta de ejemplo",
    due_date: Number(DUE.slice(8)),
    closing_date: Number(CLOSING.slice(8)),
    interest_rate: "45.00",
    limit: decimal(LIMIT),
    timezone: "America/Mexico_City",
    next_due_date: step.value.statement.due_date,
    next_closing_date: step.value.statement.next_closing_date,
    balance_used: step.value.used,
    available_credit: LIMIT - step.value.used,
    current_statement: step.value.statement,
    deleted_at: null,
    created_at: "",
    updated_at: "",
}))

function next() {
    current.value = isLast.value ? 0 : current.value + 1
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <div class="card is-compact space-y-5">
            <div
                class="card has-title is-compact"
                role="radiogroup"
                aria-labelledby="tour-title"
            >
                <p id="tour-title" class="card-title">Paso</p>

                <div class="flex flex-col gap-3">
                    <label
                        v-for="(s, index) in steps"
                        :key="s.label"
                        class="text-large"
                    >
                        <input
                            type="radio"
                            class="choice"
                            name="tour-step"
                            :value="index"
                            v-model="current"
                        />
                        <span>{{ s.label }}</span>
                    </label>
                </div>
            </div>

            <div class="reading" aria-live="polite">
                <h3 class="text-large">
                    {{ current + 1 }}. {{ step.title }}
                </h3>
                <p class="mt-1 text-body text-muted">
                    {{ step.text }}
                </p>
            </div>

            <div class="actions">
                <SecondaryButton
                    :disabled="current === 0"
                    @click="current--"
                    >Anterior</SecondaryButton
                >
                <PrimaryButton type="button" @click="next">
                    {{ isLast ? "Otra vez" : "Siguiente" }}
                </PrimaryButton>
            </div>
        </div>

        <div class="space-y-6">
            <section class="card is-compact">
                <CreditCardSummary :credit-card="creditCard" />
            </section>

            <section v-if="step.installmentPurchases.length">
                <h3 class="mb-3 section-label text-body">A meses</h3>
                <ul class="card is-list">
                    <PurchaseRow
                        v-for="p in step.installmentPurchases"
                        :key="p.id"
                        :purchase="p"
                        readonly
                    />
                </ul>
            </section>

            <section>
                <h3 class="mb-3 section-label text-body">Compras</h3>
                <ul v-if="step.purchases.length" class="card is-list">
                    <PurchaseRow
                        v-for="p in step.purchases"
                        :key="p.id"
                        :purchase="p"
                        readonly
                    />
                </ul>
                <p
                    v-else
                    class="card is-empty text-center text-small text-muted"
                >
                    Aún no hay compras en esta tarjeta.
                </p>
            </section>
        </div>
    </div>
</template>
