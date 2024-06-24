export interface Transaction {
    id: string
    credit_card_id: string
    concept: string
    datetime: Date
    amount: number
    deadline_months?: number
    commission: number
    interest_rate: number
    parent_transaction_id?: string
    created_at: Date
    updated_at?: Date
}
