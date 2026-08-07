<div class="card">

    <?php if(isset($cardTitle)): ?>

        <div class="card-header">

            <h3><?php echo htmlspecialchars($cardTitle); ?></h3>

        </div>

    <?php endif; ?>

    <div class="card-body">

        <?php echo $cardContent ?? ""; ?>

    </div>

</div>