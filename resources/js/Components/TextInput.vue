<script setup lang="ts">
import { onMounted, ref, computed, type InputTypeHTMLAttribute } from "vue"

const props = withDefaults(
    defineProps<{
        type?: InputTypeHTMLAttribute
        modelValue: string | number | Date | null
    }>(),
    { type: "text" },
)

const emit = defineEmits(["update:modelValue"])

const input = ref<HTMLInputElement | null>(null)

onMounted(() => {
    if (input.value?.hasAttribute("autofocus")) {
        input.value?.focus()
    }
})

defineExpose({ focus: () => input.value?.focus() })

const modelValue = computed(() => {
    if (props.type === "date") {
        if (props.modelValue instanceof Date) {
            return props.modelValue.toISOString().split("T")[0] ?? ""
        }
    }

    return props.modelValue ?? ""
})

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement

    if (props.type === "number") {
        return emit("update:modelValue", target.valueAsNumber)
    }

    if (props.type === "date") {
        return emit("update:modelValue", target.valueAsDate)
    }

    emit("update:modelValue", target.value)
}
</script>

<template>
    <input
        :type="type"
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:[color-scheme:dark] focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
        :value="modelValue"
        @input="handleInput"
        ref="input"
    />
</template>
