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

const usage = computed(() => {
    const limit = Number(props.creditCard.limit)

    if (limit <= 0) return 0

    return Math.min(100, ((props.creditCard.balance_used ?? 0) / limit) * 100)
})

const overLimit = computed(() => (props.creditCard.available_credit ?? 0) < 0)

/** Only worth pointing out when it's close */
const dueSoon = computed(() => {
    const days = daysUntil(props.creditCard.next_due_date)

    if (days === 0) return "hoy"
    if (days === 1) return "mañana"
    if (days > 1 && days <= 7) return `en ${days} días`

    return null
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

        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Corte</dt>
                <dd>{{ toShortDate(creditCard.next_closing_date) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Pago</dt>
                <dd
                    :class="{
                        'font-semibold text-amber-600 dark:text-amber-400':
                            dueSoon,
                    }"
                >
                    {{ toShortDate(creditCard.next_due_date) }}
                    <span v-if="dueSoon"> · {{ dueSoon }}</span>
                </dd>
            </div>
        </dl>
    </div>
</template>
