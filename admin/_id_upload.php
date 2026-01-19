<?php
// Variables supported (optional):
// $front_src, $show_front_preview, $front_placeholder_opacity
// $back_src, $show_back_preview, $back_placeholder_opacity
// $front_label, $back_label
// $front_required, $back_required (set truthy to add required attribute)

$front_src = isset($front_src) ? $front_src : '';
$show_front_preview = isset($show_front_preview) ? $show_front_preview : 'display:none;';
$front_placeholder_opacity = isset($front_placeholder_opacity) ? $front_placeholder_opacity : '';
$back_src = isset($back_src) ? $back_src : '';
$show_back_preview = isset($show_back_preview) ? $show_back_preview : 'display:none;';
$back_placeholder_opacity = isset($back_placeholder_opacity) ? $back_placeholder_opacity : '';
$front_label = isset($front_label) ? $front_label : 'Front ID';
$back_label = isset($back_label) ? $back_label : 'Back ID';
$front_required = isset($front_required) && $front_required ? 'required' : '';
$back_required = isset($back_required) && $back_required ? 'required' : '';
?>
<div class="form-row">
    <div class="id-upload-wrapper" style="width:100%;">

        <div class="id-upload-col">
            <label class="id-label-header"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($front_label); ?></label>
            <label class="id-upload-box" for="id_image_front">
                <div class="upload-placeholder" id="placeholder_front" style="<?php echo $front_placeholder_opacity; ?>">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Click to Upload</span>
                </div>
                <img id="preview_front" class="id-preview-image" src="<?php echo htmlspecialchars($front_src); ?>" style="<?php echo $show_front_preview; ?>">
                <input type="file" name="id_image_front" id="id_image_front" accept="image/*" onchange="previewImage(this, 'preview_front', 'placeholder_front')" <?php echo $front_required; ?>>
            </label>
        </div>

        <div class="id-upload-col">
            <label class="id-label-header"><i class="fas fa-id-card-alt"></i> <?php echo htmlspecialchars($back_label); ?></label>
            <label class="id-upload-box" for="id_image_back">
                <div class="upload-placeholder" id="placeholder_back" style="<?php echo $back_placeholder_opacity; ?>">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Click to Upload</span>
                </div>
                <img id="preview_back" class="id-preview-image" src="<?php echo htmlspecialchars($back_src); ?>" style="<?php echo $show_back_preview; ?>">
                <input type="file" name="id_image_back" id="id_image_back" accept="image/*" onchange="previewImage(this, 'preview_back', 'placeholder_back')" <?php echo $back_required; ?>>
            </label>
        </div>

    </div>
</div>
