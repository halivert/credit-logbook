<script setup lang="ts">
import type { Purchase } from "@/types"
import { Link } from "@inertiajs/vue3"
import { useFormat } from "@/format"
import PencilIcon from "@/Components/Icons/PencilIcon.vue"
import TrashIcon from "@/Components/Icons/TrashIcon.vue"

defineProps<{
    purchase: Purchase
}>()

defineEmits<{
    delete: [purchase: Purchase]
}>()

const { toCurrency, toDate, toShortDate } = useFormat()
</script>

<template>
    <li class="flex items-start gap-3 py-3">
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium">{{ purchase.concept }}</p>

            <template v-if="purchase.is_installment_purchase">
                <p class="text-lg text-muted">
                    {{ purchase.billed_installments ?? 0 }} de
                    {{ purchase.installment_count }} cargadas · faltan
                    {{ toCurrency(purchase.remaining_amount) }}
                </p>
                <progress
                    class="nes-progress is-small is-primary mt-2"
                    :value="purchase.billed_installments ?? 0"
                    :max="purchase.installment_count ?? 0"
                    aria-label="Mensualidades cargadas"
                />
            </template>
            <p v-else class="text-lg text-muted">
                {{ toDate(purchase.date) }}
                <span
                    v-if="purchase.applied_on && purchase.applied_on !== purchase.date"
                >
                    · aplicada {{ toShortDate(purchase.applied_on) }}</span
                >
                <span v-if="purchase.is_refund"> · Devolución</span>
            </p>
        </div>

        <div class="text-right">
            <template v-if="purchase.is_installment_purchase">
                <p class="font-semibold">
                    {{ toCurrency(purchase.installment_amount ?? 0) }}
                </p>
                <p class="text-base text-muted">al mes</p>
            </template>
            <p
                v-else
                class="font-semibold"
                :class="{ 'text-success': purchase.is_refund }"
            >
                {{ purchase.is_refund ? "+" : ""
                }}{{ toCurrency(Math.abs(Number(purchase.amount))) }}
            </p>
        </div>

        <div class="flex shrink-0 gap-1">
            <Link
                :href="route('purchases.edit', purchase)"
                class="nes-btn is-icon !m-0 shrink-0"
            >
                <PencilIcon class="size-4" />
                <span class="sr-only">Editar {{ purchase.concept }}</span>
            </Link>
            <button
                type="button"
                class="nes-btn is-error is-icon !m-0"
                @click="$emit('delete', purchase)"
            >
                <TrashIcon class="size-4" />
                <span class="sr-only">Borrar {{ purchase.concept }}</span>
            </button>
        </div>
    </li>
</template>
