import { User } from "."
import { Transaction } from "./transactions"

export interface CreditCard {
    id: string
    user_id: string
    name: string
    due_date: number
    closing_date: number
    interest_rate: number
    limit: number
    created_at: Date
    updated_at?: Date
    user: User
    transactions?: Transaction[]
}
