<?php
use yii\helpers\Html;
?>
<div class="file-input" data-remove-name="<?= $this->context->fieldName('remove') ?>">
	<div class="file-preview ">
	<?php foreach ($images as $img): ?>
        <div class="file-preview-frame file-preview-initial" data-id="<?= $img->id ?>"><div class="kv-file-content">
	<img src="<?= $img->getFileUrl() ?>" class="kv-preview-data file-preview-image" style="width:auto;height:160px;">
	</div>
            <?php if ($this->context->useTitle): ?>
                <div class="form-group" style="text-align: left">
		<label class="control-label">Название картинки</label>
		<input type="text" name="<?= $this->context->fieldName('title',
            $img->id) ?>" value="<?= $img->title ?>" class="form-control">
	</div>
            <?php endif ?>
            <?php if ($this->context->useLink): ?>
                <div class="form-group" style="text-align: left">
		<label class="control-label">Ссылка</label>
		<input type="text" name="<?= $this->context->fieldName('link',
            $img->id) ?>" value="<?= $img->link ?>" class="form-control">
	</div>
            <?php endif ?>
            <div class="file-thumbnail-footer"> <div class="file-actions">
		<div class="file-footer-buttons">
			 <button type="button" class="kv-file-remove btn btn-xs btn-default" title="Удалить"><i class="glyphicon glyphicon-trash text-danger"></i></button>
		</div>
		<div class="file-upload-indicator" title=""></div>
		<div class="clearfix"></div>
	</div>
	</div>
	</div>
    <?php endforeach ?>
        <div class="clearfix"></div>
</div>
<div class="kv-upload-progress hide"></div>
<div class="input-group file-caption-main">
	<div tabindex="500" class="form-control file-caption  kv-fileinput-caption">
	<div class="file-caption-name">
		<i class="glyphicon glyphicon-file kv-caption-icon"></i>
		<span class="input-caption"></span>
	</div>
</div>
	<div class="input-group-btn">
		<div class="btn btn-primary btn-file"><i class="glyphicon glyphicon-folder-open"></i>&nbsp;  <span class="hidden-xs">Обзор …</span><?= Html::activeInput('file',
                $this->context->model, $this->context->attribute . '[]', ['multiple' => true, 'value' => '']) ?></div>
   </div>
</div></div>
