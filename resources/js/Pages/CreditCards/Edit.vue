<script setup lang="ts">
import type { CreditCard } from "@/types"
import { Head, Link, useForm } from "@inertiajs/vue3"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import TextInput from "@/Components/TextInput.vue"
import DeleteCreditCard from "@/Components/CreditCards/DeleteCreditCard.vue"

const props = defineProps<{
    creditCard?: CreditCard
}>()

const defaultTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone

const form = useForm({
    name: props.creditCard?.name ?? "",
    due_date: props.creditCard?.due_date.toString() ?? "",
    closing_date: props.creditCard?.closing_date.toString() ?? "",
    interest_rate: props.creditCard?.interest_rate?.toString() ?? "",
    limit: props.creditCard?.limit ?? "",
    timezone: defaultTimezone ?? props.creditCard?.timezone,
})

function submit() {
    if (props.creditCard) {
        form.patch(route("credit-cards.update", props.creditCard))
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
                class="text-large sm:text-title text-ink"
            >
                {{ creditCard ? `Tarjeta ${creditCard.name}` : "Nueva tarjeta" }}
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-lg mx-auto px-4 sm:px-6 space-y-6">
                <div
                    class="card is-compact"
                >
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Nombre" />

                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                placeholder="Ej. Oro, Nu, Platino"
                                autocomplete="off"
                                required
                                autofocus
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel
                                    for="closing_date"
                                    value="Día de corte"
                                />

                                <TextInput
                                    id="closing_date"
                                    type="number"
                                    inputmode="numeric"
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
                                <InputLabel for="due_date" value="Día de pago" />

                                <TextInput
                                    id="due_date"
                                    type="number"
                                    inputmode="numeric"
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

                            <p
                                class="col-span-2 -mt-2 text-small text-muted"
                            >
                                Del 1 al 28 de cada mes.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4">
                            <div class="grid grid-rows-subgrid row-span-3">
                                <InputLabel
                                    class="self-end"
                                    for="limit"
                                    value="Límite de crédito"
                                />

                                <div class="relative mt-1">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted text-large"
                                        >$</span
                                    >
                                    <TextInput
                                        id="limit"
                                        type="text"
                                        inputmode="decimal"
                                        min="0"
                                        class="block w-full pl-8"
                                        v-model="form.limit"
                                        required
                                    />
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.limit"
                                />
                            </div>

                            <div class="grid grid-rows-subgrid row-span-3">
                                <InputLabel for="interest_rate" class="self-end">
                                    Tasa anual
                                    <span
                                        class="font-normal text-muted"
                                        >(opcional)</span
                                    >
                                </InputLabel>

                                <div class="relative mt-1">
                                    <TextInput
                                        id="interest_rate"
                                        type="text"
                                        inputmode="decimal"
                                        min="0"
                                        max="200"
                                        class="block w-full pr-8"
                                        v-model="form.interest_rate"
                                    />
                                    <span
                                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted text-large"
                                        >%</span
                                    >
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.interest_rate"
                                />
                            </div>
                        </div>

                        <div v-if="!defaultTimezone">
                            <InputLabel for="timezone" value="Zona horaria" />

                            <TextInput
                                id="timezone"
                                class="mt-1 block w-full"
                                v-model="form.timezone"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.timezone"
                            />
                        </div>

                        <PrimaryButton
                            type="submit"
                            class="w-full justify-center sm:w-auto"
                            :disabled="form.processing"
                            >Guardar</PrimaryButton
                        >
                    </form>
                </div>

                <DeleteCreditCard v-if="creditCard" :credit-card="creditCard" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
