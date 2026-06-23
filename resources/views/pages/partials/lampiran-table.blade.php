<table class="result-table">
    <thead>
        <tr>
            <th class="rank-col">Peringkat</th>
            <th class="lot-col">Nomor Lot</th>
            <th class="name-col">Nama</th>
            <th class="district-col">Kecamatan</th>
            <th class="score-col">Total Nilai</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($juaraList)): ?>
            <?php foreach ($juaraList as $item): ?>
                <tr class="juara-row">
                    <td class="rank-cell">
                        <span class="rank-badge juara<?= $item['rank'] ?? 1 ?>">
                            <?= e($item['rank_label']) ?>
                        </span>
                        <?php if (!empty($item['is_fallback'])): ?>
                            <div class="fallback-note">* dari Penyisihan</div>
                        <?php endif; ?>
                    </td>
                    <td class="lot-cell"><?= e($item['lot_number'] ?? '-') ?></td>
                    <td class="name-cell"><?= e($item['name']) ?></td>
                    <td class="district-cell"><?= e($item['district']) ?></td>
                    <td class="score-cell"><?= e($item['score']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($harapanList)): ?>
            <?php foreach ($harapanList as $item): ?>
                <tr class="harapan-row">
                    <td class="rank-cell">
                        <span class="rank-badge harapan<?= $item['rank'] ?? 1 ?>">
                            <?= e($item['rank_label']) ?>
                        </span>
                    </td>
                    <td class="lot-cell"><?= e($item['lot_number'] ?? '-') ?></td>
                    <td class="name-cell"><?= e($item['name']) ?></td>
                    <td class="district-cell"><?= e($item['district']) ?></td>
                    <td class="score-cell"><?= e($item['score']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($juaraList) && empty($harapanList)): ?>
            <tr>
                <td colspan="5" class="no-data">Belum ada data juara</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
