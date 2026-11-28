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
        <p v-if="showName" class="mb-3 font-semibold">{{ creditCard.name }}</p>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ overLimit ? "Excedido" : "Disponible" }}
        </p>
        <p
            class="text-2xl font-semibold tabular-nums"
            :class="{ 'text-red-600 dark:text-red-400': overLimit }"
        >
            {{ toCurrency(Math.abs(creditCard.available_credit ?? 0)) }}
        </p>

        <div
            class="mt-3 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"
            role="progressbar"
            :aria-valuenow="Math.round(usage)"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="Crédito usado"
        >
            <div
                class="h-full rounded-full"
                :class="usage >= 90 ? 'bg-red-500' : 'bg-indigo-500'"
                :style="{ width: `${usage}%` }"
            />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 tabular-nums">
            {{ toCurrency(creditCard.balance_used ?? 0) }} usados de
            {{ toCurrency(creditCard.limit) }}
        </p>

        <dl v-if="statement" class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">
                    <template v-if="paymentState === 'overdue'">
                        Venció el {{ toShortDate(statement.due_date) }}
                    </template>
                    <template v-else>
                        Pago antes del {{ toShortDate(statement.due_date) }}
                    </template>
                </dt>
                <dd
                    v-if="paymentState === 'overdue' || paymentState === 'pending'"
                    class="font-semibold tabular-nums"
                    :class="{
                        'text-red-600 dark:text-red-400': paymentState === 'overdue',
                        'text-amber-600 dark:text-amber-400': dueSoon,
                    }"
                >
                    {{ toCurrency(statement.remaining) }}
                    <span v-if="dueSoon" class="font-normal"> · {{ dueSoon }}</span>
                </dd>
                <dd
                    v-else-if="paymentState === 'covered'"
                    class="font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    Cubierto
                </dd>
                <dd v-else class="text-gray-500 dark:text-gray-400">
                    Nada que pagar
                </dd>
                <dd
                    v-if="statement.paid_since_closing > 0 && statement.remaining > 0"
                    class="text-xs text-gray-500 dark:text-gray-400 tabular-nums"
                >
                    de {{ toCurrency(statement.to_avoid_interest) }}
                </dd>
            </div>

            <div>
                <dt class="text-gray-500 dark:text-gray-400">
                    Corte {{ toShortDate(statement.next_closing_date) }}
                </dt>
                <dd class="tabular-nums">
                    {{ toCurrency(statement.period_charges) }}
                    <span class="text-gray-500 dark:text-gray-400">
                        este periodo
                    </span>
                </dd>
            </div>
        </dl>
    </div>
</template>
