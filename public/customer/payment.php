<?php

$invoiceNo = $invoiceNo ?? "INV-1001";

$total = $total ?? "Rs. 18,500";

$status = $status ?? "Pending Verification";

?>

<div class="invoice-card">

    <h3>

        Invoice <?= htmlspecialchars($invoiceNo) ?>

    </h3>

    <p>

        Total

        <strong>

            <?= htmlspecialchars($total) ?>

        </strong>

    </p>

    <span class="badge badge-warning">

        <?= htmlspecialchars($status) ?>

    </span>

</div>