<script setup lang="ts">
import { ref, watch } from "vue"
import TextInput from "@/Components/TextInput.vue"

const props = defineProps<{
    purchase: Purchase
}>()

const model = defineModel()

const checked = ref(false)
const installmentNumber = ref("0")

watch(
    model,
    (newModel) => {
        if (!Array.isArray(newModel)) {
            return
        }

        const found = newModel.find((item) => item.id === props.purchase.id)
        checked.value = !!found
        installmentNumber.value = found?.installment_number ?? 0
    },
    { immediate: true },
)

watch(checked, (newChecked) => {
    if (model.value && !Array.isArray(model.value)) {
        return
    }

    if (newChecked) {
        model.value = [
            ...model.value,
            { id: props.purchase.id, installment_number: installmentNumber },
        ]
    } else {
        model.value =
            model.value?.filter((item) => item.id !== props.purchase.id) ?? []
    }
})

watch(installmentNumber, (newInstallmentNumber) => {
    if (!Array.isArray(model.value)) {
        model.value = {
            id: props.purchase.id,
            installment_number: newInstallmentNumber,
        }
        return
    }

    const found = model.value.find((item) => item.id === props.purchase.id)
    if (found) found.installment_number = newInstallmentNumber
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
