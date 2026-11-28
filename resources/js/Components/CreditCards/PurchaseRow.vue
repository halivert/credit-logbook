<script setup lang="ts">
import type { Purchase } from "@/types"
import { useFormat } from "@/format"

defineProps<{
    purchase: Purchase
}>()

const { toCurrency, toDate } = useFormat()
</script>

<template>
    <li
        class="inline-flex justify-between w-full px-3 py-1 focus:bg-zinc-50 hover:bg-zinc-50 rounded items-center focus-within:bg-zinc-50"
    >
        <span>
            {{ toDate(purchase.datetime) }}
            <br />

            <span :class="{ 'line-through': purchase.paid_at }">
                {{ purchase.concept }} -
                {{ toCurrency(purchase.amount) }}
            </span>
        </span>

        <slot name="actions" />
    </li>
</template>
