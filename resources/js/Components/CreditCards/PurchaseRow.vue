<script setup lang="ts">
import { usePage } from "@inertiajs/vue3"

defineProps<{
    purchase: Purchase
}>()

const page = usePage()

const toCurrency = new Intl.NumberFormat(page.props.locale, {
    style: "currency",
    currency: "MXN",
})

const toDate = new Intl.DateTimeFormat(page.props.locale, {
    dateStyle: "short",
})
</script>

<template>
    <li
        class="inline-flex justify-between w-full px-3 py-1 focus:bg-zinc-50 hover:bg-zinc-50 rounded items-center focus-within:bg-zinc-50"
    >
        <span>
            {{ toDate.format(new Date(purchase.datetime)) }}
            <br />

            <span :class="{ 'line-through': purchase.paid_at }">
                {{ purchase.concept }} -
                {{ toCurrency.format(purchase.amount) }}
            </span>
        </span>

        <slot name="actions" />
    </li>
</template>
