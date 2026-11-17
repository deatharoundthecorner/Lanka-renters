# Lanka Renters — Sequence Diagram: Payment & Invoicing Flow

## Overview
This sequence diagram illustrates the financial processing pipeline in **Lanka Renters**, beginning from customer checkout, calculation of rental fees, driver wages, key money deposits, payment gateway transaction execution, invoice generation, booking confirmation, and post-trip owner settlement.

```mermaid
sequenceDiagram
    autonumber
    actor Customer as 👤 Customer
    participant UI as 🖥️ Customer Portal UI
    participant Ctrl as ⚙️ CustomerBookingController
    participant ModelBooking as 📦 CustomerBooking Model
    participant DB as 🗄️ MariaDB Database
    participant Gateway as 💳 Payment Gateway (IPG)
    participant InvoiceSvc as 📑 Invoice Service
    participant Notif as 🔔 Notification System
    actor Owner as 👤 Vehicle Owner

    Customer ->> UI: Clicks "Proceed to Payment" for Booking #ID
    UI ->> Ctrl: GET /customer/bookings/payment.php?id={id}
    Ctrl ->> ModelBooking: findCustomerBooking(userId, bookingId)
    ModelBooking ->> DB: SELECT b.*, v.price_per_day, v.refundable_key_money FROM bookings b ...
    DB -->> ModelBooking: Return booking details (Status: 'pending_payment')
    ModelBooking -->> Ctrl: Booking record & fee structure
    Ctrl -->> UI: Render Payment Form (Itemized Rental, Driver Fee, Deposit)

    Customer ->> UI: Submits Card Credentials / Authorization
    UI ->> Ctrl: POST /customer/bookings/process-payment.php (CSRF Token, Amount)
    Ctrl ->> Ctrl: Validate CSRF & Anti-Tamper Pricing Signature

    Ctrl ->> Gateway: POST /v1/charge (Amount, Currency, CardToken, Reference)
    Gateway -->> Ctrl: HTTP 200 OK (TransactionID: txn_77921a, Status: "succeeded")

    Ctrl ->> DB: BEGIN TRANSACTION
    Ctrl ->> DB: INSERT INTO payments (booking_id, customer_id, amount, payment_method, transaction_id, payment_status) VALUES (...)
    DB -->> Ctrl: Payment Record ID: #p_45

    Ctrl ->> InvoiceSvc: generateInvoice(bookingId, paymentId)
    InvoiceSvc ->> DB: INSERT INTO invoices (invoice_number, booking_id, customer_id, payment_id, rental_fee, driver_fee, additional_charges, discount, tax, total_amount, invoice_status) VALUES (...)
    DB -->> InvoiceSvc: Invoice Record (Invoice #INV-2026-0045)

    Ctrl ->> DB: UPDATE bookings SET status = 'confirmed' WHERE id = bookingId
    Ctrl ->> DB: COMMIT TRANSACTION

    Ctrl ->> Notif: dispatchPaymentConfirmation(customerUserId, ownerUserId, bookingId)
    Notif ->> DB: INSERT INTO notifications (...)
    Notif -->> Customer: In-App / SMS: "Payment successful. Booking #ID confirmed!"
    Notif -->> Owner: In-App: "Payment received for Booking #ID. Prepare vehicle for handover."

    Ctrl -->> UI: Redirect to /customer/bookings/details.php?id={id}&status=confirmed
    UI -->> Customer: Display Confirmed Booking, Downloadable Invoice Link & Pickup Instructions
```

## Financial Integrity Protections
1. **Idempotency & Replay Defense**: Each payment attempt binds to a unique transaction ID. Duplicate gateway submissions for an already confirmed booking are rejected.
2. **Atomic Invoicing**: The payment recording, invoice row creation, and booking state progression execute within a single atomic database transaction. If any sub-operation fails, the entire database transaction rolls back.
3. **Escrow & Key Money Separation**: Refundable key money deposits are tracked as dedicated liability ledger lines, ensuring rapid post-rental restitution upon clean inspection.
