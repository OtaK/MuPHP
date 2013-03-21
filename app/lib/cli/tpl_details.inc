<?php if ($this->_posted): ?>
    <?php if ((isset($edited) && $edited) || (isset($deleted) && $deleted)): ?>
        <div data-alert class="alert-box success">Element édité avec succès<a class="close" href="">&times;</a></div>
    <?php endif; ?>
<?php endif; ?>

<a class="has-tip tip-top back-arrow" title="Revenir à la liste" href="__LOWER_CLASSNAME__"><i class="foundicon-general-left-arrow"></i></a>
<h4 class="subheader" style="display: inline;">Editer</h4>

<?php if ($data): ?>
    <form action="" method="POST">
        <input type="submit" class="button left" value="Enregistrer" />
        <a href="#" class="button alert" data-reveal-id="deleteModalConfirm" id="deleteModalButton">Supprimer</a>
    </form>
<?php else: ?>
    <h3 class="subheader">L'élément demandé n'existe pas.</h3>
    <a class="button with-icon" href="<?php echo BASE_URL . '__LOWER_CLASSNAME__'; ?>"><i class="foundicon-general-left-arrow"></i>Revenir à la liste</a>
<?php endif; ?>

<script type="text/javascript">
    window.onload = function()
    {
        $('body').append('' +
                '<div id="deleteModalConfirm" class="reveal-modal medium">' +
                '<form action="" method="POST">' +
                '<input type="submit" class="button alert left" name="delete" value="Confirmer la suppression" />' +
                '<a class="button secondary dismiss-modal right" href="#">Annuler</a>' +
                '<div style="clear:both;"></div></form></div>'
        );
    <?php if ($this->_actionCalled === 'details' && isset($_GET['delete']) && $_GET['delete']): ?>
        $('#deleteModalButton').click();
    <?php endif; ?>
    }
</script>