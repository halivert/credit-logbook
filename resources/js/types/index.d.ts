export * from "./models"

export interface User {
    id: string
    name: string
    username: string
    email: string
    email_verified_at: string | null
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User
    }
    locale: string
}
