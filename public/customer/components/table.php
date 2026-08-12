<table class="table">

    <thead>

        <tr>

            <?php foreach($headers as $header): ?>

                <th>

                    <?php echo htmlspecialchars($header); ?>

                </th>

            <?php endforeach; ?>

        </tr>

    </thead>

    <tbody>

        <?php echo $rows ?? ""; ?>

    </tbody>

</table>