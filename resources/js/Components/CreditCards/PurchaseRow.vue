<script setup lang="ts">
import type { Purchase } from "@/types"
import { computed } from "vue"
import { Link } from "@inertiajs/vue3"
import { useFormat } from "@/format"
import PencilIcon from "@/Components/Icons/PencilIcon.vue"
import TrashIcon from "@/Components/Icons/TrashIcon.vue"

const props = defineProps<{
    purchase: Purchase
}>()

defineEmits<{
    delete: [purchase: Purchase]
}>()

const { toCurrency, toDate } = useFormat()

const billedPercentage = computed(() =>
    props.purchase.installment_count
        ? ((props.purchase.billed_installments ?? 0) /
              props.purchase.installment_count) *
          100
        : 0,
)
</script>

<template>
    <li class="flex items-start gap-3 py-3">
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium">{{ purchase.concept }}</p>

            <template v-if="purchase.is_installment_purchase">
                <p class="text-sm text-gray-500 dark:text-gray-400 tabular-nums">
                    {{ purchase.billed_installments ?? 0 }} de
                    {{ purchase.installment_count }} cargadas · faltan
                    {{ toCurrency(purchase.remaining_amount) }}
                </p>
                <div
                    class="mt-2 h-1 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"
                    role="progressbar"
                    :aria-valuenow="purchase.billed_installments ?? 0"
                    aria-valuemin="0"
                    :aria-valuemax="purchase.installment_count ?? 0"
                    aria-label="Mensualidades cargadas"
                >
                    <div
                        class="h-full rounded-full bg-indigo-500"
                        :style="{ width: `${billedPercentage}%` }"
                    />
                </div>
            </template>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                {{ toDate(purchase.date) }}
                <span v-if="purchase.is_refund"> · Devolución</span>
            </p>
        </div>

        <div class="text-right tabular-nums">
            <template v-if="purchase.is_installment_purchase">
                <p class="font-semibold">
                    {{ toCurrency(purchase.installment_amount ?? 0) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">al mes</p>
            </template>
            <p
                v-else
                class="font-semibold"
                :class="{ 'text-emerald-600 dark:text-emerald-400': purchase.is_refund }"
            >
                {{ purchase.is_refund ? "+" : ""
                }}{{ toCurrency(Math.abs(Number(purchase.amount))) }}
            </p>
        </div>

        <div class="flex shrink-0 -mr-1">
            <Link
                :href="route('purchases.edit', purchase)"
                class="rounded p-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <PencilIcon class="size-5" />
                <span class="sr-only">Editar {{ purchase.concept }}</span>
            </Link>
            <button
                type="button"
                class="rounded p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500"
                @click="$emit('delete', purchase)"
            >
                <TrashIcon class="size-5" />
                <span class="sr-only">Borrar {{ purchase.concept }}</span>
            </button>
        </div>
    </li>
</template>
