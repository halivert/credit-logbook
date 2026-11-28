<script setup lang="ts">
import { onMounted, ref, computed, type InputTypeHTMLAttribute } from "vue"

const props = withDefaults(
    defineProps<{
        type?: InputTypeHTMLAttribute
        modelValue: string | number | null
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

const modelValue = computed(() => props.modelValue ?? "")

/**
 * Always the text as typed: dates stay "Y-m-d" and amounts aren't turned
 * into numbers (an invalid one would become NaN and be erased), so the
 * server can say what's wrong and the person can fix it.
 */
function handleInput(event: Event) {
    emit("update:modelValue", (event.target as HTMLInputElement).value)
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
