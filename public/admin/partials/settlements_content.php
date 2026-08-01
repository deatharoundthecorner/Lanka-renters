<?php
// Settlements page content (partial)
?>
<header class="page-header">
    <div>
        <h1>Settlements</h1>
        <p>Calculate refunds, deductions and payouts.</p>
    </div>
</header>

<section class="settlement-grid">
    <div class="panel-card settlement-summary">
        <div class="settlement-card">
            <h3>Settlement calculation · BK-1042</h3>
            <div class="settlement-row">
                <span>Rental amount</span>
                <span class="settlement-value">Rs. 95,000</span>
            </div>
            <div class="settlement-row">
                <span>Refund calculation</span>
                <span class="settlement-value">Rs. 15,000</span>
            </div>
            <div class="settlement-row settlement-deduction">
                <span>Damage deduction</span>
                <span class="settlement-value settlement-negative">-Rs. 12,000</span>
            </div>
            <div class="settlement-row">
                <span>Platform fee</span>
                <span class="settlement-value settlement-negative">-Rs. 4,750</span>
            </div>
            <div class="settlement-row settlement-total-row">
                <strong>Owner earnings</strong>
                <strong id="ownerEarningsValue">Rs. 63,250</strong>
            </div>
        </div>
        <button id="approveSettlement" class="btn btn-primary settlement-action">Approve Settlement</button>
    </div>
    <div class="panel-card">
        <div class="settlement-card">
            <h3>Recent settlements</h3>
            <div class="recent-settlements" id="recentSettlements">
                <div class="recent-item">
                    <div>
                        <strong>BK-1020</strong>
                        <small>Settled</small>
                    </div>
                    <span class="paid-pill">Rs. 120,000</span>
                </div>
                <div class="recent-item">
                    <div>
                        <strong>BK-1005</strong>
                        <small>Settled</small>
                    </div>
                    <span class="paid-pill">Rs. 112,000</span>
                </div>
                <div class="recent-item">
                    <div>
                        <strong>BK-0998</strong>
                        <small>Settled</small>
                    </div>
                    <span class="paid-pill">Rs. 104,000</span>
                </div>
            </div>
        </div>
    </div>
</section>
