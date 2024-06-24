<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"

const props = defineProps<{
    creditCard?: CreditCard
}>()

const form = useForm({
    name: props.creditCard?.name ?? "",
    due_date: props.creditCard?.due_date ?? "",
    closing_date: props.creditCard?.closing_date ?? "",
    interest_rate: props.creditCard?.interest_rate ?? "",
    limit: props.creditCard?.limit ?? "",
})

function submit() {
    if (props.creditCard) {
        throw new Error("Not implemented yet")
    } else {
        form.post(route("credit-cards.store"))
    }
}
</script>

<template>
    <Head :title="creditCard ? 'Editar tarjeta' : 'Nueva tarjeta'" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Tarjeta
                <span v-if="creditCard">{{ creditCard.name }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <form class="space-y-2" @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Nombre" />

                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>

                        <div>
                            <InputLabel for="due_date" value="Día de pago" />

                            <TextInput
                                id="due_date"
                                type="number"
                                min="1"
                                max="28"
                                class="mt-1 block w-full"
                                v-model="form.due_date"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.due_date"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="closing_date"
                                value="Fecha de corte"
                            />

                            <TextInput
                                id="closing_date"
                                type="number"
                                min="1"
                                max="28"
                                class="mt-1 block w-full"
                                v-model="form.closing_date"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.closing_date"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="interest_rate"
                                value="Tasa de interés"
                            />

                            <TextInput
                                id="interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                v-model="form.interest_rate"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.interest_rate"
                            />
                        </div>

                        <div>
                            <InputLabel for="limit" value="Límite de crédito" />

                            <TextInput
                                id="limit"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                v-model="form.limit"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.limit"
                            />
                        </div>

                        <div class="text-right">
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                                >Guardar</PrimaryButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
