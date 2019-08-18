<ul class="sip-point-list js-sip-point-list">
    <li class="js-point-warning"><?php esc_html_e('Please click on image above to add point.', 'image-point'); ?></li>
</ul>

<script type="text/html" id="tmpl-sip-point">
    <li class="sip-point-item js-sip-point-item" data-id="{{ data.id }}">
        <div class="sip-point-item-handle js-sip-point-item-handle">
            <span class="sip-point-item-title js-sip-point-item-title"><?php
                esc_html_e('Point', 'image-point'); ?> {{ data.id }} - {{ data.popup_title }}
            </span>
            <span class="sip-point-item-control-container">
                <a href="#remove" class="js-remove-point dashicons dashicons-trash"
                   title="<?php esc_attr_e('Remove', 'image-point'); ?>"></a>
            </span>
        </div>
        <div class="sip-point-item-detail">
            <div class="sip-point-group">
                <div class="sip-point-group-title"><?php esc_html_e('The building', 'image-point'); ?></div>
                <div class="sip-point-group-content">
                    <table class="form-table">
                        <tr class="js-popup-title {{ data.popup_type == 'product' ? 'hidden' : '' }}">
                            <th><?php esc_html_e('Title', 'image-point'); ?></th>
                            <td><input type="text" class="js-point-input" data-prop="popup_title"
                                       value="{{ data.popup_title }}"/></td>
                        </tr>
                        <tr class="js-popup-content">
                            <th><?php esc_html_e('Content', 'image-point'); ?></th>
                            <td><textarea class="js-point-input" data-prop="popup_content" cols="60" rows="5">{{ data.popup_content }}</textarea>
                            </td>
                        </tr>
                        <tr class="js-popup-link">
                            <th><?php esc_html_e('Images', 'image-point'); ?></th>
                            <td>
                                <button type="button"
                                        class="js-sip-add-point-images button button-primary"><?= esc_html_e('Add Image', 'image-point'); ?></button>
                                <div class="sip-point-images-container">
                                    <# if(data.description_images) { #>
                                    <# for(var i=0; i < data.description_images.length; i++) { #>
                                    <div data-index="{{ i }}" data-id="{{ data.description_images.id }}"
                                         class="point-image-item"><a href="#" class="js-sip-point-image-remove"><i
                                                    class="dashicons dashicons-trash"></i></a><img
                                                src="{{ data.description_images[i].thumb }}" alt=""></div>
                                    <# } #>
                                    <# } #>
                                </div>
                            </td>
                        </tr>
                        <tr class="js-popup-position">
                            <th><?php esc_html_e('Popup Position', 'image-point'); ?></th>
                            <td>
                                <select class="js-point-input" data-prop="popup_position">
                                    <option value="top" {{ data.popup_position===
                                    'top' ? ' selected="selected"' : '' }}>
                                    <?php esc_html_e('Top', 'image-point'); ?></option>
                                    <option value="bottom" {{ data.popup_position===
                                    'bottom' ? ' selected="selected"' : ''}}>
                                    <?php esc_html_e('Bottom', 'image-point'); ?></option>
                                    <option value="left" {{ data.popup_position===
                                    'left' ? ' selected="selected"' : '' }}>
                                    <?php esc_html_e('Left', 'image-point'); ?></option>
                                    <option value="right" {{ data.popup_position===
                                    'right' ? ' selected="selected"' : '' }}>
                                    <?php esc_html_e('Right', 'image-point'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="sip-point-group">
                <div class="sip-point-group-title"><?php esc_html_e('The Point', 'image-point'); ?></div>
                <div class="sip-point-group-content">
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Point Background Color', 'image-point'); ?></th>
                            <th><?php esc_html_e('Point Border Color', 'image-point'); ?></th>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" class="js-point-input js-sip-color" data-prop="icon_background"
                                       value="{{ data.icon_background }}"/>
                            </td>
                            <td>
                                <input type="text" class="js-point-input js-sip-color" data-prop="icon_color"
                                       value="{{ data.icon_color }}"/>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </li>
</script>