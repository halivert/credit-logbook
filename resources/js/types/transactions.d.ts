export interface Transaction {
    id: string
    credit_card_id: string
    concept: string
    datetime: Date
    amount: number
    created_at: Date
    updated_at?: Date
}
