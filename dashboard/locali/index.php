<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$locali = $localeModel->getByUserId($user['id']);

$pageTitle = 'Gestione Locali';
$pageActions = '<a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nuovo Locale</a>';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <?php if (empty($locali)): ?>
            <div class="text-center py-5">
                <i class="bi bi-shop display-1 text-muted"></i>
                <h4 class="mt-3">Nessun locale configurato</h4>
                <p class="text-muted">Inizia creando il tuo primo locale per gestire i menu digitali</p>
                <a href="create.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Crea il Primo Locale
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Indirizzo</th>
                            <th>Città</th>
                            <th>Telefono</th>
                            <th>Stato</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locali as $locale): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $locale['nome']; ?></strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-link-45deg"></i>
                                        <?php echo BASE_URL . '/menu/' . $locale['slug']; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($locale['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo $locale['indirizzo'] ?? '-'; ?></td>
                                <td><?php echo $locale['citta'] ?? '-'; ?></td>
                                <td>
                                    <?php if ($locale['telefono']): ?>
                                        <a href="tel:<?php echo $locale['telefono']; ?>">
                                            <?php echo $locale['telefono']; ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($locale['attivo']): ?>
                                        <span class="badge bg-success">Attivo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Non Attivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit.php?id=<?php echo $locale['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="../menu/?locale=<?php echo $locale['id']; ?>" 
                                       class="btn btn-sm btn-success" title="Menu">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                    <a href="../qrcode/?locale=<?php echo $locale['id']; ?>" 
                                       class="btn btn-sm btn-info" title="QR Code">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteLocale(<?php echo $locale['id']; ?>)" title="Elimina">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteLocale(id) {
    if (confirm('Sei sicuro di voler eliminare questo locale? Verranno eliminati anche tutti i menu e i piatti associati.')) {
        $.ajax({
            url: '../../api/locali/delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Successo', 'Locale eliminato', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
