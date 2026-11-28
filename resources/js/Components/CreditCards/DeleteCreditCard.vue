<script setup lang="ts">
import type { CreditCard } from "@/types"
import { useForm } from "@inertiajs/vue3"
import { ref } from "vue"
import DangerButton from "@/Components/DangerButton.vue"
import Modal from "@/Components/Modal.vue"
import SecondaryButton from "@/Components/SecondaryButton.vue"

const props = defineProps<{
    creditCard: CreditCard
}>()

const confirming = ref(false)
const form = useForm({})

function destroy() {
    form.delete(route("credit-cards.destroy", props.creditCard), {
        onSuccess: () => (confirming.value = false),
    })
}
</script>

<template>
    <section class="card is-compact space-y-4">
        <header>
            <h3 class="text-large">Borrar tarjeta</h3>
            <p class="mt-1 text-small text-muted">
                Se borran para siempre sus compras, pagos y conciliaciones. No
                se puede deshacer.
            </p>
        </header>

        <DangerButton @click="confirming = true">Borrar tarjeta</DangerButton>

        <Modal :show="confirming" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-large">¿Borrar {{ creditCard.name }}?</h2>

                <p class="mt-1 text-small text-muted">
                    Se borran para siempre sus compras, pagos y
                    conciliaciones. No se puede deshacer.
                </p>

                <div class="mt-6 actions justify-end">
                    <SecondaryButton @click="confirming = false">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton :disabled="form.processing" @click="destroy">
                        Borrar tarjeta
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
