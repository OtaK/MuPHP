<?php if (isset($_GET['delete_success'])): ?>
    <div data-alert class="alert-box success">Element supprimé avec succès<a class="close" href="">&times;</a></div>
<?php endif; ?>

<?php if ($this->_posted): ?>
    <?php if ($inserted): ?>
        <div data-alert class="alert-box success">Element ajouté avec succès</div>
    <?php else: ?>
        <div data-alert class="alert-box alert">Erreur dans l'ajout</div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($data && $data->num_rows): ?>
    <?php foreach ($data as $d): ?>
        <div class="panel radius" data-id="<?php echo $d['__TABLE_NAME___id']; ?>">
            <div class="left">
                <h4 class="subheader" style="margin-top: 0; display: inline;"><a href="__LOWER_CLASSNAME__?id=<?php echo $d['__TABLE_NAME___id']; ?>"><?php echo $d['__TABLE_NAME___name']; ?></a></h4>
            </div>
            <div class="right">
                <a class="button secondary small with-icon" href="__TABLE_NAME__s?pid=<?php echo $d['__TABLE_NAME___id']; ?>"><i class="foundicon-general-edit"></i>Modifier</i></a>
                <a class="button alert small with-icon" href="__TABLE_NAME__s?pid=<?php echo $d['__TABLE_NAME___id']; ?>&delete=1"><i class="foundicon-general-remove"></i>Supprimer</a>
            </div>
            <div style="clear:both;"></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="panel radius">
    <a href="#" data-reveal-id="modModal" class="button radius right with-icon"><i class="foundicon-general-plus"></i>&nbsp;Ajouter</a>
    <div style="clear:both;"></div>
</div>

<script type="text/javascript">
    window.onload = function() {
        $('body').append('' +
            '<div id="modModal" class="reveal-modal medium">' +
            '<form action="__LOWER_CLASSNAME__" method="POST">' +
            '<input type="submit" class="button left" value="Ajouter" />' +
            '<a class="button secondary dismiss-modal right" href="#">Annuler</a>' +
            '<div style="clear:both;"></div></form></div>'
        );
    }
</script>