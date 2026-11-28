<script setup lang="ts">
import type { CreditCard } from "@/types"
import { computed } from "vue"
import { daysUntil, useFormat } from "@/format"

const props = withDefaults(
    defineProps<{
        creditCard: CreditCard
        showName?: boolean
    }>(),
    { showName: true },
)

const { toCurrency, toShortDate } = useFormat()

const statement = computed(() => props.creditCard.current_statement)

const usage = computed(() => {
    const limit = Number(props.creditCard.limit)

    if (limit <= 0) return 0

    return Math.min(100, ((props.creditCard.balance_used ?? 0) / limit) * 100)
})

const overLimit = computed(() => (props.creditCard.available_credit ?? 0) < 0)

/** Only worth pointing out when it's close */
const dueSoon = computed(() => {
    if (!statement.value?.remaining) return null

    const days = daysUntil(statement.value.due_date)

    if (days === 0) return "hoy"
    if (days === 1) return "mañana"
    if (days > 1 && days <= 7) return `en ${days} días`

    return null
})

type PaymentState = "overdue" | "pending" | "covered" | "nothing"

const paymentState = computed<PaymentState>(() => {
    const current = statement.value

    if (!current || current.to_avoid_interest <= 0) return "nothing"
    if (current.remaining <= 0) return "covered"

    return current.is_overdue ? "overdue" : "pending"
})
</script>

<template>
    <div>
        <p v-if="showName" class="mb-3 text-large">{{ creditCard.name }}</p>

        <p class="text-note text-muted">
            {{ overLimit ? "Excedido" : "Disponible" }}
        </p>
        <p
            class="text-title sm:text-display leading-none"
            :class="{ 'text-error': overLimit }"
        >
            {{ toCurrency(Math.abs(creditCard.available_credit ?? 0)) }}
        </p>

        <progress
            class="meter is-small mt-3"
            :class="usage >= 90 ? 'is-error' : 'is-primary'"
            :value="Math.round(usage)"
            max="100"
            aria-label="Crédito usado"
        />
        <p class="mt-1 text-small text-muted">
            {{ toCurrency(creditCard.balance_used ?? 0) }} usados de
            {{ toCurrency(creditCard.limit) }}
        </p>

        <dl v-if="statement" class="mt-4 grid grid-cols-2 gap-3 text-small">
            <div>
                <dt class="text-muted">
                    <template v-if="paymentState === 'overdue'">
                        Venció el {{ toShortDate(statement.due_date) }}
                    </template>
                    <template v-else>
                        Pago antes del {{ toShortDate(statement.due_date) }}
                    </template>
                </dt>
                <dd
                    v-if="paymentState === 'overdue' || paymentState === 'pending'"
                    class="font-semibold"
                    :class="{
                        'text-error': paymentState === 'overdue',
                        'text-warning': dueSoon,
                    }"
                >
                    {{ toCurrency(statement.remaining) }}
                    <span v-if="dueSoon" class="font-normal"> · {{ dueSoon }}</span>
                </dd>
                <dd
                    v-else-if="paymentState === 'covered'"
                    class="font-semibold text-success"
                >
                    Cubierto
                </dd>
                <dd v-else class="text-muted">
                    Nada que pagar
                </dd>
                <dd
                    v-if="statement.paid_since_closing > 0 && statement.remaining > 0"
                    class="text-note text-muted"
                >
                    de {{ toCurrency(statement.to_avoid_interest) }}
                </dd>
            </div>

            <div>
                <dt class="text-muted">
                    Corte {{ toShortDate(statement.next_closing_date) }}
                </dt>
                <dd>
                    {{ toCurrency(statement.period_charges) }}
                    <span class="text-muted">
                        este periodo
                    </span>
                </dd>
            </div>
        </dl>

        <p
            v-if="statement?.bank_difference"
            class="mt-3 text-small text-warning"
        >
            No cuadra con tu estado de cuenta por
            {{ toCurrency(Math.abs(statement.bank_difference)) }}
        </p>
    </div>
</template>
