# Customer Module Business Rules

## Phase 7 regression invariants

- Customer record access is tied to the authenticated session user; a database ID alone does not grant access.
- Customer state-changing routes require a valid Customer CSRF token before controller logic runs.
- Customer payment submission derives amount and initial status from approved server-side booking data; customers do not set payment verification outcomes.
- Driver-change, return and replacement-decision workflows are clearly labelled session-only demos until approved database support exists.

1. Only registered and verified customers can create bookings.

2. Customers can only book available vehicles.

3. A booking must be approved by the vehicle owner before payment.

4. Customers cannot make payments before booking approval.

5. Only the Admin can verify or reject submitted payments.

6. An invoice is generated automatically after booking confirmation and before payment.

7. Customers can only rate vehicles and drivers after the booking has been completed.

8. Customers can only review their own bookings.

9. A chat room is automatically created after a booking is confirmed.

10. Customers receive automatic notifications whenever booking or payment status changes.

11. Customers can report incidents only for their own active bookings.

12. Customer identity is always obtained from the logged-in session.
