<script setup lang="ts">
import type { CreditCard, Decimal, Purchase } from "@/types"
import { computed, ref, watch } from "vue"
import { today, useFormat } from "@/format"
import {
    addDays,
    BillingCycle,
    calculateStatement,
    InstallmentPlan,
    type Day,
} from "./logbook"
import CreditCardSummary from "@/Components/CreditCards/CreditCardSummary.vue"
import PurchaseRow from "@/Components/CreditCards/PurchaseRow.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import SecondaryButton from "@/Components/SecondaryButton.vue"

/**
 * A pretend card to show how the logbook works, with the same forms, rules
 * and math as the app (see ./logbook.ts). Everything happens in the
 * browser: nothing is sent to the server and it's gone on reload.
 */

const { toCurrency, toShortDate } = useFormat()

const LIMIT_CENTS = 3000000
/** Same limits as the server: App\Support\Money and StorePurchaseRequest */
const MAX_CENTS = 99999999999
const MAX_INSTALLMENTS = 60

const TODAY = today()

// Closed two weeks ago and due in a few days, so the reminder shows up
const cycle = new BillingCycle(
    Number(addDays(TODAY, -15).slice(8)),
    Number(addDays(TODAY, 5).slice(8)),
)

type DemoPurchase = {
    id: number
    concept: string
    date: Day
    /** Negative for returns */
    cents: number
    installmentCount: number | null
    installmentCents: number | null
    commissionCents: number
}

type DemoPayment = { id: number; date: Day; cents: number }

let nextId = 0

function purchase(
    concept: string,
    date: Day,
    cents: number,
    installments?: { count: number; installmentCents?: number; commissionCents?: number },
): DemoPurchase {
    return {
        id: nextId++,
        concept,
        date,
        cents,
        installmentCount: installments?.count ?? null,
        installmentCents: installments
            ? (installments.installmentCents ??
              InstallmentPlan.evenInstallmentCents(cents, installments.count))
            : null,
        commissionCents: installments?.commissionCents ?? 0,
    }
}

function payment(date: Day, cents: number): DemoPayment {
    return { id: nextId++, date, cents }
}

function seed() {
    return {
        purchases: [
            purchase("Pantalla", addDays(TODAY, -100), 960000, { count: 12 }),
            purchase("Farmacia", addDays(TODAY, -48), 65000),
            purchase("Restaurante", addDays(TODAY, -22), 185000),
            purchase("Gasolina", addDays(TODAY, -6), 80000),
            purchase("Súper", addDays(TODAY, -2), 124050),
        ],
        payments: [payment(addDays(TODAY, -38), 225000)],
    }
}

const data = ref(seed())

const plan = (p: DemoPurchase) =>
    p.installmentCount
        ? new InstallmentPlan(
              p.cents,
              p.installmentCount,
              p.installmentCents ?? 0,
              p.commissionCents,
          )
        : null

const statement = computed(() =>
    calculateStatement(
        cycle,
        TODAY,
        data.value.purchases.map((p) => ({ day: p.date, cents: p.cents, plan: plan(p) })),
        data.value.payments.map((p) => ({ day: p.date, cents: p.cents })),
    ),
)

/** Every charge, commissions included, minus every payment */
const balanceCents = computed(
    () =>
        data.value.purchases.reduce((sum, p) => sum + p.cents + p.commissionCents, 0) -
        data.value.payments.reduce((sum, p) => sum + p.cents, 0),
)

const money = (cents: number) => cents / 100
const decimal = (cents: number) => (cents / 100).toFixed(2) as Decimal

/** As the server serializes it to the card page */
function toPurchase(p: DemoPurchase): Purchase {
    const installments = plan(p)
    const billed = installments
        ? Math.min(cycle.closingsBetween(p.date, TODAY), installments.count)
        : null

    return {
        id: String(p.id),
        credit_card_id: "demo",
        concept: p.concept,
        date: p.date,
        amount: decimal(p.cents),
        installment_count: p.installmentCount,
        installment_amount:
            installments && billed !== null
                ? money(installments.nextInstallmentCents(billed))
                : null,
        commission: p.commissionCents ? decimal(p.commissionCents) : null,
        interest_rate: null,
        applied_on: null,
        created_at: "",
        updated_at: "",
        is_installment_purchase: installments !== null,
        is_refund: p.cents < 0,
        billed_installments: billed,
        remaining_installments:
            installments && billed !== null ? installments.count - billed : null,
        remaining_amount:
            installments && billed !== null
                ? money(installments.remainingCents(billed))
                : 0,
    }
}

/** Newest first, like the card page */
const sorted = computed(() =>
    [...data.value.purchases]
        .sort((a, b) => (a.date === b.date ? b.id - a.id : a.date < b.date ? 1 : -1))
        .map(toPurchase),
)
const purchases = computed(() => sorted.value.filter((p) => !p.is_installment_purchase))
const installmentPurchases = computed(() =>
    sorted.value.filter((p) => (p.remaining_installments ?? 0) > 0),
)

const creditCard = computed<CreditCard>(() => ({
    id: "demo",
    user_id: "demo",
    name: "Tarjeta de ejemplo",
    due_date: cycle.dueDay,
    closing_date: cycle.closingDay,
    interest_rate: "45.00",
    limit: decimal(LIMIT_CENTS),
    timezone: "America/Mexico_City",
    next_due_date: cycle.nextDue(TODAY),
    next_closing_date: cycle.nextClosing(TODAY),
    balance_used: money(balanceCents.value),
    available_credit: money(LIMIT_CENTS - balanceCents.value),
    current_statement: {
        closing_date: statement.value.closing,
        due_date: statement.value.due,
        next_closing_date: statement.value.nextClosing,
        to_avoid_interest: money(statement.value.toAvoidInterest),
        paid_since_closing: money(statement.value.paidSinceClosing),
        remaining: money(statement.value.remaining),
        period_charges: money(statement.value.periodCharges),
        is_overdue: statement.value.isOverdue,
        bank_difference: null,
    },
    deleted_at: null,
    created_at: "",
    updated_at: "",
}))

type Kind = "purchase" | "installments" | "refund" | "payment"

const kinds: { value: Kind; label: string }[] = [
    { value: "purchase", label: "Compra" },
    { value: "installments", label: "A meses" },
    { value: "refund", label: "Devolución" },
    { value: "payment", label: "Pago" },
]

function blankForm(kind: Kind = "purchase") {
    return {
        kind,
        concept: "",
        date: TODAY,
        amount: "",
        months: "",
        installment: "",
        commission: "",
    }
}

const form = ref(blankForm())
const message = ref("")

// The payment form starts with what's missing to avoid interest
watch(
    () => form.value.kind,
    (kind) => {
        if (kind === "payment" && form.value.amount === "" && statement.value.remaining > 0) {
            form.value.amount = (statement.value.remaining / 100).toFixed(2)
        }
    },
)

/** Whole cents from what was typed, null when it isn't a valid amount */
function parseCents(value: string): number | null {
    if (!/^\d+(\.\d{1,2})?$/.test(value.trim())) return null

    return Math.round(Number(value) * 100)
}

/** Errors only show once something was typed, like after submitting */
function moneyError(value: string, minCents: number): string {
    if (value.trim() === "") return ""

    const cents = parseCents(value)

    if (cents === null) return "Solo números, con hasta dos decimales y sin comas."
    if (cents < minCents) return `Debe ser al menos ${toCurrency(minCents / 100)}.`
    if (cents > MAX_CENTS) return `No puede ser mayor que ${toCurrency(MAX_CENTS / 100)}.`

    return ""
}

const isInstallments = computed(() => form.value.kind === "installments")

const errors = computed(() => {
    const f = form.value
    const amountCents = parseCents(f.amount)
    const installmentCents = parseCents(f.installment)
    const months = Number(f.months)

    return {
        date:
            f.date === ""
                ? "La fecha es obligatoria."
                : f.date > TODAY
                  ? "No puede ser después de hoy."
                  : "",
        amount: moneyError(f.amount, 1),
        months:
            isInstallments.value &&
            f.months !== "" &&
            (!Number.isInteger(months) || months < 1 || months > MAX_INSTALLMENTS)
                ? `De 1 a ${MAX_INSTALLMENTS} meses.`
                : "",
        installment: !isInstallments.value
            ? ""
            : moneyError(f.installment, 1) ||
              (installmentCents !== null &&
              amountCents !== null &&
              installmentCents > amountCents
                  ? "No puede ser mayor que el importe."
                  : ""),
        commission: isInstallments.value ? moneyError(f.commission, 0) : "",
    }
})

const valid = computed(() => {
    const f = form.value

    return (
        Object.values(errors.value).every((error) => error === "") &&
        f.amount.trim() !== "" &&
        (f.kind === "payment" || f.concept.trim() !== "") &&
        (!isInstallments.value || f.months !== "")
    )
})

/** What the bank usually charges: the amount split evenly, rounded up */
const suggestedInstallment = computed(() => {
    const amountCents = parseCents(form.value.amount)
    const months = Number(form.value.months)

    if (!amountCents || !Number.isInteger(months) || months < 1) return null

    return money(InstallmentPlan.evenInstallmentCents(amountCents, months))
})

function save() {
    if (!valid.value) return

    const f = form.value
    const cents = parseCents(f.amount) ?? 0
    const before = { ...statement.value, available: LIMIT_CENTS - balanceCents.value }

    let added: DemoPurchase | null = null

    if (f.kind === "payment") {
        data.value.payments.push(payment(f.date, cents))
    } else {
        added = purchase(
            f.concept.trim(),
            f.date,
            f.kind === "refund" ? -cents : cents,
            f.kind === "installments"
                ? {
                      count: Number(f.months),
                      installmentCents: parseCents(f.installment) ?? undefined,
                      commissionCents: parseCents(f.commission) ?? 0,
                  }
                : undefined,
        )
        data.value.purchases.push(added)
    }

    message.value = explain(before, added)
    form.value = blankForm(f.kind)
}

/** What just changed on the card, in words */
function explain(
    before: { available: number; remaining: number; periodCharges: number },
    added: DemoPurchase | null,
): string {
    const after = statement.value
    const available = LIMIT_CENTS - balanceCents.value
    const lines: string[] = []

    const availableChange = available - before.available
    if (availableChange < 0) {
        lines.push(`Tu disponible baja ${toCurrency(-availableChange / 100)}.`)
    } else if (availableChange > 0) {
        lines.push(`Liberas ${toCurrency(availableChange / 100)} de crédito.`)
    }

    if (after.remaining !== before.remaining) {
        lines.push(
            after.remaining === 0
                ? "Ya cubriste el pago para no generar intereses."
                : `Para no generar intereses: ${toCurrency(after.remaining / 100)} antes del ${toShortDate(after.due)}.`,
        )
    }

    const periodChange = after.periodCharges - before.periodCharges
    if (periodChange !== 0) {
        lines.push(
            `Se cobra en el corte del ${toShortDate(after.nextClosing)}: ${periodChange > 0 ? "+" : "−"}${toCurrency(Math.abs(periodChange) / 100)} este periodo.`,
        )
    }

    const installments = added && plan(added)
    if (added && installments) {
        const billed = Math.min(cycle.closingsBetween(added.date, TODAY), installments.count)

        if (billed === 0) {
            lines.push(
                `La primera mensualidad, ${toCurrency(installments.nextInstallmentCents(0) / 100)}, se cobra en el corte del ${toShortDate(cycle.nextClosing(added.date))}.`,
            )
        }
    }

    return lines.join(" ")
}

function reset() {
    data.value = seed()
    message.value = ""
    form.value = blankForm()
}

const balance = computed(() => money(balanceCents.value))
const remaining = computed(() => money(statement.value.remaining))
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <form class="card is-compact space-y-5" @submit.prevent="save">
            <div>
                <h3 class="section-label text-body">Pruébalo</h3>
                <p class="mt-1 text-small text-muted">
                    Anota algo y mira cómo cambia la tarjeta. Es un ejemplo:
                    se queda en tu navegador y se borra al recargar.
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

            <!-- Payment: same fields as Payments/Create -->
            <template v-if="form.kind === 'payment'">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="demo-amount" value="Pagaste" />
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
                                required
                            />
                        </div>
                        <InputError class="mt-2" :message="errors.amount" />
                    </div>

                    <div>
                        <InputLabel for="demo-date" value="Fecha" />
                        <TextInput
                            id="demo-date"
                            type="date"
                            class="mt-1 block w-full"
                            :max="TODAY"
                            v-model="form.date"
                            required
                        />
                        <InputError class="mt-2" :message="errors.date" />
                    </div>
                </div>

                <div class="flex flex-col items-start gap-2 text-small">
                    <button
                        v-if="remaining > 0"
                        type="button"
                        class="link"
                        @click="form.amount = remaining.toFixed(2)"
                    >
                        Para no generar intereses ({{ toCurrency(remaining) }})
                    </button>
                    <button
                        v-if="balance > 0"
                        type="button"
                        class="link"
                        @click="form.amount = balance.toFixed(2)"
                    >
                        Todo el saldo ({{ toCurrency(balance) }})
                    </button>
                </div>
            </template>

            <!-- Purchase, installments or refund: same fields as Purchases/Create -->
            <template v-else>
                <div>
                    <InputLabel for="demo-concept" value="Concepto" />
                    <TextInput
                        id="demo-concept"
                        class="mt-1 block w-full"
                        v-model="form.concept"
                        maxlength="255"
                        :placeholder="
                            form.kind === 'refund'
                                ? 'Ej. Devolución Amazon'
                                : 'Ej. Súper, gasolina'
                        "
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="demo-date" value="Fecha" />
                        <TextInput
                            id="demo-date"
                            type="date"
                            class="mt-1 block w-full"
                            :max="TODAY"
                            v-model="form.date"
                            required
                        />
                        <InputError class="mt-2" :message="errors.date" />
                    </div>

                    <div>
                        <InputLabel
                            for="demo-amount"
                            :value="form.kind === 'refund' ? 'Te devuelven' : 'Importe'"
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
                                required
                            />
                        </div>
                        <InputError class="mt-2" :message="errors.amount" />
                    </div>
                </div>

                <template v-if="isInstallments">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="demo-months" value="Meses" />
                            <TextInput
                                id="demo-months"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :max="MAX_INSTALLMENTS"
                                class="mt-1 block w-full"
                                v-model="form.months"
                                required
                            />
                            <InputError class="mt-2" :message="errors.months" />
                        </div>

                        <div>
                            <InputLabel for="demo-installment" value="Mensualidad" />
                            <div class="relative mt-1">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-large"
                                    >$</span
                                >
                                <TextInput
                                    id="demo-installment"
                                    inputmode="decimal"
                                    class="block w-full pl-8"
                                    v-model="form.installment"
                                    :placeholder="suggestedInstallment?.toFixed(2)"
                                />
                            </div>
                            <InputError class="mt-2" :message="errors.installment" />
                        </div>

                        <p
                            v-if="suggestedInstallment"
                            class="col-span-2 -mt-2 text-small text-muted"
                        >
                            Si la dejas vacía:
                            {{ toCurrency(suggestedInstallment) }} al mes.
                        </p>
                    </div>

                    <div>
                        <InputLabel for="demo-commission">
                            Comisión por apertura
                            <span class="font-normal text-muted">(opcional)</span>
                        </InputLabel>
                        <div class="relative mt-1 w-1/2 pr-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-large"
                                >$</span
                            >
                            <TextInput
                                id="demo-commission"
                                inputmode="decimal"
                                class="block w-full pl-8"
                                v-model="form.commission"
                            />
                        </div>
                        <InputError class="mt-2" :message="errors.commission" />
                    </div>
                </template>

                <p v-if="form.kind === 'refund'" class="text-small text-muted">
                    Se abona a la tarjeta: baja tu saldo y libera crédito.
                </p>
            </template>

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
                    >Guardar</PrimaryButton
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
                        v-for="p in installmentPurchases"
                        :key="p.id"
                        :purchase="p"
                        readonly
                    />
                </ul>
            </section>

            <section>
                <h3 class="mb-3 section-label text-body">Compras</h3>
                <ul v-if="purchases.length" class="card is-list">
                    <PurchaseRow
                        v-for="p in purchases"
                        :key="p.id"
                        :purchase="p"
                        readonly
                    />
                </ul>
                <p v-else class="card is-empty text-center text-small text-muted">
                    Aún no hay compras en esta tarjeta.
                </p>
            </section>
        </div>
    </div>
</template>
