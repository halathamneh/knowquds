<?php global $post; ?>
<?php if ( ! empty( $post ) ) : ?>
  <input type="text" readonly value="[image_point id=&quot;<?php echo $post->ID; ?>&quot;]" />
<?php else : ?>
  <?php esc_html_e( 'Please click Publish to see the shortcode', 'image-point' ); ?>
<?php endif; ?>
