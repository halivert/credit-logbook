<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"

const props = defineProps<{
    creditCard: CreditCard
    transaction: Transaction
}>()

const datetime = (
    props.transaction.datetime ?? new Date().toISOString()
).split("T")[0]

const form = useForm({
    concept: props.transaction.concept ?? "",
    datetime,
    amount: props.transaction.amount ?? "",
})

function submit() {
    form.transform((data) => ({
        ...data,
        datetime: new Date(data.datetime + "T00:00"),
    })).patch(route("transactions.update", props.transaction))
}
</script>

<template>
    <Head title="Editar transacción" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Transacción de tarjeta: {{ creditCard.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-3"
                >
                    <form class="space-y-2" @submit.prevent="submit">
                        <div>
                            <InputLabel for="concept" value="Concepto" />

                            <TextInput
                                id="concept"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.concept"
                                required
                                autofocus
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.concept"
                            />
                        </div>

                        <div>
                            <InputLabel for="datetime" value="Fecha" />

                            <TextInput
                                id="datetime"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.datetime"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.datetime"
                            />
                        </div>

                        <div>
                            <InputLabel for="amount" value="Importe" />

                            <TextInput
                                id="amount"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.amount"
                                required
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.amount"
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
