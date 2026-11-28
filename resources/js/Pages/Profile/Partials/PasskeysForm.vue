<script setup lang="ts">
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import TrashIcon from "@/Components/Icons/TrashIcon.vue"
import { useFormat } from "@/format"
import { router } from "@inertiajs/vue3"
import { usePasskeyRegister } from "@laravel/passkeys/vue"
import { passkeyErrorMessage } from "@/passkeys"
import { ref } from "vue"

export type PasskeyItem = {
    id: number
    name: string
    authenticator: string | null
    createdAt: string
    lastUsedAt: string | null
}

defineProps<{
    passkeys: PasskeyItem[]
}>()

const { toDate } = useFormat()

const name = ref("")

const { register, isLoading, errorInstance, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = ""
        router.reload({ only: ["passkeys"] })
    },
})

function add() {
    register(name.value.trim())
}

function remove(passkey: PasskeyItem) {
    if (
        window.confirm(
            `¿Borrar la passkey "${passkey.name}"? Ya no podrás entrar con ella.`,
        )
    ) {
        router.delete(route("passkey.destroy", passkey.id), {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-large">Passkeys</h2>
            <p class="mt-1 text-small text-muted">
                Entra con la huella, la cara o el PIN de tu dispositivo, sin
                escribir tu contraseña. No se pueden robar con phishing.
            </p>
        </header>

        <ul
            v-if="passkeys.length"
            class="rows"
        >
            <li
                v-for="passkey in passkeys"
                :key="passkey.id"
                class="flex items-center justify-between gap-3 py-3"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ passkey.name }}</p>
                    <p class="text-small text-muted">
                        <template v-if="passkey.authenticator"
                            >{{ passkey.authenticator }} ·
                        </template>
                        <template v-if="passkey.lastUsedAt">
                            Usada el {{ toDate(passkey.lastUsedAt) }}
                        </template>
                        <template v-else>
                            Agregada el {{ toDate(passkey.createdAt) }}
                        </template>
                    </p>
                </div>
                <button
                    type="button"
                    class="btn is-error is-icon !m-0"
                    @click="remove(passkey)"
                >
                    <TrashIcon class="size-4" />
                    <span class="sr-only">Borrar {{ passkey.name }}</span>
                </button>
            </li>
        </ul>

        <p
            v-if="!isSupported"
            class="text-small text-warning"
        >
            Este navegador no permite passkeys.
        </p>

        <form v-else class="space-y-2" @submit.prevent="add">
            <InputLabel for="passkey-name" value="Agregar una passkey" />
            <div class="flex gap-3">
                <TextInput
                    id="passkey-name"
                    class="block flex-1 min-w-0"
                    v-model="name"
                    placeholder="Ej. iPhone, MacBook"
                    maxlength="255"
                    required
                />
                <PrimaryButton :disabled="isLoading || !name.trim()"
                    >Agregar</PrimaryButton
                >
            </div>
            <InputError :message="passkeyErrorMessage(errorInstance)" />
        </form>
    </section>
</template>
