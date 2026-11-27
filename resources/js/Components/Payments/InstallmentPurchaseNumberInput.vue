<script setup lang="ts">
import type { InstallmentPayment, Purchase } from "@/types"
import { ref, watch } from "vue"
import TextInput from "@/Components/TextInput.vue"

const props = defineProps<{
    purchase: Purchase
}>()

const model = defineModel<InstallmentPayment[]>({ required: true })

const checked = ref(false)
const installmentNumber = ref(0)

watch(
    model,
    (newModel) => {
        const found = newModel.find((item) => item.id === props.purchase.id)
        checked.value = !!found
        installmentNumber.value = found?.installment_number ?? 0
    },
    { immediate: true },
)

watch(checked, (newChecked) => {
    const others = model.value.filter((item) => item.id !== props.purchase.id)

    model.value = newChecked
        ? [
              ...others,
              {
                  id: props.purchase.id,
                  installment_number: installmentNumber.value,
              },
          ]
        : others
})

watch(installmentNumber, (newInstallmentNumber) => {
    model.value = model.value.map((item) =>
        item.id === props.purchase.id
            ? { ...item, installment_number: newInstallmentNumber }
            : item,
    )
})
</script>

<template>
    <div>
        <div class="flex gap-2">
            <input
                :id="purchase.id"
                type="checkbox"
                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                v-model="checked"
            />

            <slot />
        </div>

        <TextInput
            :id="`${purchase.id}_installment_number`"
            type="number"
            class="flex-1 block w-full"
            v-model="installmentNumber"
            :disabled="!checked"
        />
    </div>
</template>
