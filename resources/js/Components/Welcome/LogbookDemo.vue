<script setup lang="ts">
import type { CreditCard, Decimal, Purchase } from "@/types"
import { computed, ref } from "vue"
import { today, useFormat } from "@/format"
import CreditCardSummary from "@/Components/CreditCards/CreditCardSummary.vue"
import PurchaseRow from "@/Components/CreditCards/PurchaseRow.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import SecondaryButton from "@/Components/SecondaryButton.vue"

/**
 * A pretend card to show how the logbook works. Everything happens in the
 * browser: nothing is sent to the server and it's gone on reload.
 */

const { toCurrency, toDate } = useFormat()

const LIMIT = 20000
/** What the last statement asked to pay to avoid interest */
const TO_AVOID_INTEREST = 4350

/** "Y-m-d" some days from today */
function day(offset: number): string {
    const [y, m, d] = today().split("-").map(Number)

    return new Date(Date.UTC(y, m - 1, d + offset)).toISOString().slice(0, 10)
}

const closingDate = day(-15)
const dueDate = day(5)
const nextClosingDate = day(15)

const cents = (amount: number) => Math.round(amount * 100) / 100
const decimal = (amount: number) => amount.toFixed(2) as Decimal

let nextId = 0

function makePurchase(
    data: Pick<Purchase, "concept" | "date" | "amount"> & Partial<Purchase>,
): Purchase {
    return {
        id: `demo-${nextId++}`,
        credit_card_id: "demo",
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
        ...data,
    }
}

function makeInstallments(
    concept: string,
    date: string,
    amount: number,
    count: number,
    billed = 0,
): Purchase {
    const monthly = Math.ceil(Number(((amount * 100) / count).toFixed(6))) / 100

    return makePurchase({
        concept,
        date,
        amount: decimal(amount),
        is_installment_purchase: true,
        installment_count: count,
        installment_amount: monthly,
        billed_installments: billed,
        remaining_installments: count - billed,
        remaining_amount: cents(amount - monthly * billed),
    })
}

type Payment = { id: string; date: string; amount: number }

type Movement =
    | { type: "purchase"; purchase: Purchase }
    | { type: "payment"; payment: Payment }

function seed(): Movement[] {
    return [
        {
            type: "purchase",
            purchase: makePurchase({
                concept: "Súper",
                date: day(-2),
                amount: "1240.50",
            }),
        },
        {
            type: "purchase",
            purchase: makePurchase({
                concept: "Gasolina",
                date: day(-6),
                amount: "800.00",
            }),
        },
        {
            type: "purchase",
            purchase: makeInstallments("Pantalla", day(-100), 9600, 12, 3),
        },
    ]
}

const movements = ref<Movement[]>(seed())

const purchases = computed(() =>
    movements.value.flatMap((m) => (m.type === "purchase" ? [m.purchase] : [])),
)
const installmentPurchases = computed(() =>
    purchases.value.filter((p) => p.is_installment_purchase),
)
/** Single purchases, refunds and payments, newest first */
const recent = computed(() =>
    movements.value.filter(
        (m) => m.type === "payment" || !m.purchase.is_installment_purchase,
    ),
)

const sum = (values: number[]) => cents(values.reduce((a, b) => a + b, 0))

const paid = computed(() =>
    sum(
        movements.value.map((m) => (m.type === "payment" ? m.payment.amount : 0)),
    ),
)
const singleCharges = computed(() =>
    sum(
        purchases.value
            .filter((p) => !p.is_installment_purchase)
            .map((p) => Number(p.amount)),
    ),
)
const remaining = computed(() =>
    Math.max(0, cents(TO_AVOID_INTEREST - paid.value)),
)

const creditCard = computed<CreditCard>(() => {
    // Each installment purchase bills one monthly payment per closing
    const nextInstallments = sum(
        installmentPurchases.value.map((p) => Number(p.installment_amount)),
    )
    const installmentsLeft = sum(
        installmentPurchases.value.map((p) => Number(p.remaining_amount)),
    )
    const balanceUsed = cents(
        TO_AVOID_INTEREST - paid.value + singleCharges.value + installmentsLeft,
    )

    return {
        id: "demo",
        user_id: "demo",
        name: "Tarjeta de ejemplo",
        due_date: Number(dueDate.slice(8)),
        closing_date: Number(closingDate.slice(8)),
        interest_rate: "45.00",
        limit: decimal(LIMIT),
        timezone: "America/Mexico_City",
        next_due_date: dueDate,
        next_closing_date: nextClosingDate,
        balance_used: balanceUsed,
        available_credit: cents(LIMIT - balanceUsed),
        current_statement: {
            closing_date: closingDate,
            due_date: dueDate,
            next_closing_date: nextClosingDate,
            to_avoid_interest: TO_AVOID_INTEREST,
            paid_since_closing: paid.value,
            remaining: remaining.value,
            period_charges: cents(singleCharges.value + nextInstallments),
            is_overdue: false,
            bank_difference: null,
        },
        deleted_at: null,
        created_at: "",
        updated_at: "",
    }
})

type Kind = "purchase" | "installments" | "refund" | "payment"

const kinds: { value: Kind; label: string }[] = [
    { value: "purchase", label: "Compra" },
    { value: "installments", label: "A meses" },
    { value: "refund", label: "Devolución" },
    { value: "payment", label: "Pago" },
]

const placeholders: Record<Kind, string> = {
    purchase: "Ej. Cena",
    installments: "Ej. Laptop",
    refund: "Ej. Devolución Amazon",
    payment: "",
}

const form = ref({ kind: "purchase" as Kind, concept: "", amount: "", months: "6" })
const message = ref("")

const amount = computed(() => cents(Number(form.value.amount)))
const months = computed(() => Math.trunc(Number(form.value.months)))
const valid = computed(
    () =>
        amount.value > 0 &&
        (form.value.kind === "payment" || form.value.concept.trim() !== "") &&
        (form.value.kind !== "installments" ||
            (months.value >= 2 && months.value <= 60)),
)

function add() {
    if (!valid.value) return

    const { kind } = form.value
    const concept = form.value.concept.trim()
    let movement: Movement

    if (kind === "payment") {
        movement = {
            type: "payment",
            payment: { id: `demo-${nextId++}`, date: today(), amount: amount.value },
        }
    } else if (kind === "installments") {
        movement = {
            type: "purchase",
            purchase: makeInstallments(concept, today(), amount.value, months.value),
        }
    } else {
        movement = {
            type: "purchase",
            purchase: makePurchase({
                concept,
                date: today(),
                amount: decimal(kind === "refund" ? -amount.value : amount.value),
                is_refund: kind === "refund",
            }),
        }
    }

    movements.value = [movement, ...movements.value]
    message.value = explain(movement)
    form.value = { ...form.value, concept: "", amount: "" }
}

/** What just changed on the card, in words */
function explain(movement: Movement): string {
    if (movement.type === "payment") {
        return remaining.value > 0
            ? `Te faltan ${toCurrency(remaining.value)} para no generar intereses.`
            : "Listo: este mes no generas intereses."
    }

    const purchase = movement.purchase

    if (purchase.is_installment_purchase) {
        return `Apartas ${toCurrency(purchase.amount)} de tu crédito, pero cada corte solo se suman ${toCurrency(purchase.installment_amount ?? 0)}.`
    }

    if (purchase.is_refund) {
        return "Se abona a la tarjeta y liberas crédito."
    }

    return "Baja tu disponible y se suma a lo que pagarás después del siguiente corte."
}

function reset() {
    movements.value = seed()
    message.value = ""
    form.value = { kind: "purchase", concept: "", amount: "", months: "6" }
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <form class="card is-compact space-y-5" @submit.prevent="add">
            <div>
                <h3 class="section-label text-body">Pruébalo</h3>
                <p class="mt-1 text-small text-muted">
                    Anota algo y mira cómo cambia la tarjeta. Es un ejemplo:
                    no se guarda nada.
                </p>
            </div>

            <div
                class="card has-title is-compact"
                role="radiogroup"
                aria-labelledby="demo-kind-title"
            >
                <p id="demo-kind-title" class="card-title">Tipo</p>

                <div class="flex flex-col gap-3">
                    <label
                        v-for="kind in kinds"
                        :key="kind.value"
                        class="text-large"
                    >
                        <input
                            type="radio"
                            class="choice"
                            name="demo-kind"
                            :value="kind.value"
                            v-model="form.kind"
                        />
                        <span>{{ kind.label }}</span>
                    </label>
                </div>
            </div>

            <div v-if="form.kind !== 'payment'">
                <InputLabel for="demo-concept" value="Concepto" />
                <TextInput
                    id="demo-concept"
                    class="mt-1 block w-full"
                    v-model="form.concept"
                    :placeholder="placeholders[form.kind]"
                    required
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel
                        for="demo-amount"
                        :value="
                            form.kind === 'refund'
                                ? 'Te devuelven'
                                : form.kind === 'payment'
                                  ? 'Pagas'
                                  : 'Importe'
                        "
                    />
                    <div class="relative mt-1">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-large"
                            >$</span
                        >
                        <TextInput
                            id="demo-amount"
                            inputmode="decimal"
                            class="block w-full pl-8"
                            v-model="form.amount"
                            placeholder="0.00"
                            required
                        />
                    </div>
                </div>

                <div v-if="form.kind === 'installments'">
                    <InputLabel for="demo-months" value="Meses" />
                    <TextInput
                        id="demo-months"
                        type="number"
                        inputmode="numeric"
                        min="2"
                        max="60"
                        class="mt-1 block w-full"
                        v-model="form.months"
                        required
                    />
                </div>
            </div>

            <button
                v-if="form.kind === 'payment' && remaining > 0"
                type="button"
                class="link text-small"
                @click="form.amount = remaining.toFixed(2)"
            >
                Lo que falta para no generar intereses:
                {{ toCurrency(remaining) }}
            </button>

            <p
                v-if="message"
                class="notice is-success text-small"
                aria-live="polite"
            >
                {{ message }}
            </p>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton
                    type="submit"
                    class="!m-0 justify-center"
                    :disabled="!valid"
                    >Anotar</PrimaryButton
                >
                <SecondaryButton type="button" class="!m-0" @click="reset"
                    >Reiniciar</SecondaryButton
                >
            </div>
        </form>

        <div class="space-y-6">
            <section class="card is-compact">
                <CreditCardSummary :credit-card="creditCard" />
            </section>

            <section v-if="installmentPurchases.length">
                <h3 class="mb-3 section-label text-body">A meses</h3>
                <ul class="card is-list">
                    <PurchaseRow
                        v-for="purchase in installmentPurchases"
                        :key="purchase.id"
                        :purchase="purchase"
                        readonly
                    />
                </ul>
            </section>

            <section>
                <h3 class="mb-3 section-label text-body">Movimientos</h3>
                <ul v-if="recent.length" class="card is-list">
                    <template v-for="movement in recent">
                        <PurchaseRow
                            v-if="movement.type === 'purchase'"
                            :key="movement.purchase.id"
                            :purchase="movement.purchase"
                            readonly
                        />
                        <li
                            v-else
                            :key="movement.payment.id"
                            class="flex items-start gap-3 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">Pago</p>
                                <p class="text-small text-muted">
                                    {{ toDate(movement.payment.date) }}
                                </p>
                            </div>
                            <p class="font-semibold text-success">
                                +{{ toCurrency(movement.payment.amount) }}
                            </p>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </div>
</template>
