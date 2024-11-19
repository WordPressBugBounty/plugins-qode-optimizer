<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

$utilities = new Qode_Optimizer_Utility();
$issue     = new Qode_Optimizer_Issue();

// Optimized images (media and folders).
$option_array['optimized_count'] = $utilities->optimization_history_count();

// Optimization history issue resolve.
$option_array['issue_check'] = $issue->optimization_history_issues_check();

// Optimization history cleanup.
$option_array['cleanup_check'] = $utilities->optimization_history_cleanup_check();

// WebP images removal.
$option_array['webp_images_check'] = $utilities->webp_images_check();

$qo_nonce = wp_create_nonce( 'qo-nonce' );
?>
<div class="qodef-utility-content">
	<div id="qodef-utility-forms">
		<form id="qodef-utility-start" class="qodef-utility-form" method="post" action="?page=utilities" data-qo-nonce="<?php echo esc_html( $qo_nonce ); ?>">
			<div class="qodef-utility-section">
				<h2 class="qodef-utility-section-title"><?php esc_html_e( 'Issues', 'qode-optimizer' ); ?></h2>
				<div class="qodef-utility-header-holder">
					<h3><?php esc_html_e( 'Invalid Optimization History', 'qode-optimizer' ); ?></h3>
					<p><?php esc_html_e( 'Changing already optimized images manually can potentially create certain issues and inconsistencies with information saved in the system, rendering it unusable. In order to resolve this, the information needs to be corrected. You can perform that action here', 'qode-optimizer' ); ?></p>
				</div>
				<?php
				$content_holder_classes   = array( 'qodef-utility-form-content-holder' );
				$content_holder_classes[] = $option_array['issue_check']['total_count'] > 0 ? 'qodef-warning' : 'qodef-success';
				?>
				<div <?php qode_optimizer_class_attribute( $content_holder_classes ); ?>>
					<div class="qodef-utility-form-content qodef-last">
						<?php if ( $option_array['issue_check']['total_count'] > 0 ) { ?>
							<?php if ( $option_array['issue_check']['media_fixable_count'] > 0 ) { ?>
								<div class="qodef-note">
									<?php
									esc_html_e( 'There are some fixable issue(s) with optimized images right now. Count: ', 'qode-optimizer' );
									echo esc_html( $option_array['issue_check']['media_fixable_count'] );
									?>
								</div>
							<?php } ?>
							<?php if ( $option_array['issue_check']['media_problematic_count'] > 0 ) { ?>
								<div class="qodef-note">
									<?php
									esc_html_e( 'There are some not fixable issue(s). Count: ', 'qode-optimizer' );
									echo esc_html( $option_array['issue_check']['media_problematic_count'] );
									?>
									<br />
									<?php
									esc_html_e( 'In order to resolve them you need to change the ORIGINAL (uploaded) image as well for regenerate thumbnails process to create valid images. Original images that need replacing are:', 'qode-optimizer' );
									?>
									<ul>
										<?php foreach ( $option_array['issue_check']['media_problematic'] as $image ) { ?>
											<li><?php echo esc_html( $image['path'] ); ?></li>
										<?php } ?>
									</ul>
								</div>
							<?php } ?>
						<?php } else { ?>
							<div class="qodef-note"><?php esc_html_e( 'No issues detected.', 'qode-optimizer' ); ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="qodef-utility-form-action-holder">
					<div class="qodef-utility-form-action">
						<input id="qodef-utility-resolve-optimization-history-issues" <?php echo esc_html( 0 === $option_array['issue_check']['total_count'] ? 'disabled' : '' ); ?> name="utility_resolve_optimization_history_issues" type="submit" class="qodef-btn qodef-btn-solid button-primary action" value="<?php esc_attr_e( 'Resolve', 'qode-optimizer' ); ?>" />
						<span class="qodef-spinner-loading qodef-hidden"><?php qode_optimizer_framework_svg_icon( 'spinner' ); ?></span>
						<span class="qodef-message qodef-hidden"></span>
					</div>
					<div class="qodef-utility-form-action">
						<a class="qodef-btn qodef-btn-solid button-primary action" <?php echo esc_html( 0 === $option_array['issue_check']['total_count'] ? 'disabled' : '' ); ?> href="<?php echo esc_url( admin_url( 'admin.php?page=issues' ) ); ?>" target="_blank"><?php esc_html_e( 'Details', 'qode-optimizer' ); ?></a>
					</div>
				</div>
			</div>

			<div class="qodef-utility-section">
				<div class="qodef-utility-header-holder">
					<h3><?php esc_html_e( 'Optimization History Cleanup', 'qode-optimizer' ); ?></h3>
					<p><?php esc_html_e( 'After intensive optimization processes (e.g. modifying images back & forth, altering original image sizes, regenerating thumbnails, deleting images), there is a possibility the optimization system still stores some outdated and presently irrelevant image information. An example of this would be optimization/backup info regarding manually deleted images still present in system database. Here you can clean up the optimization database and delete redundant information', 'qode-optimizer' ); ?></p>
				</div>
				<?php
				$content_holder_classes   = array( 'qodef-utility-form-content-holder' );
				$content_holder_classes[] = $option_array['cleanup_check']['total_ids_for_deletion_count'] > 0 ? 'qodef-warning' : 'qodef-success';
				?>
				<div <?php qode_optimizer_class_attribute( $content_holder_classes ); ?>>
					<div class="qodef-utility-form-content qodef-last">
						<?php if ( $option_array['cleanup_check']['total_ids_for_deletion_count'] > 0 ) { ?>
							<div class="qodef-note">
								<?php
								esc_html_e( 'There are some backup and/or optimization record(s) that need cleaning up right now. Count: ', 'qode-optimizer' );
								echo esc_html( $option_array['cleanup_check']['total_ids_for_deletion_count'] );
								?>
							</div>
						<?php } else { ?>
							<div class="qodef-note"><?php esc_html_e( 'No issues detected.', 'qode-optimizer' ); ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="qodef-utility-form-action-holder">
					<div class="qodef-utility-form-action">
						<input id="qodef-utility-clean-up-optimization-history" <?php echo esc_html( 0 === $option_array['cleanup_check']['total_ids_for_deletion_count'] ? 'disabled' : '' ); ?> name="utility_clean_up_optimization_history" type="submit" class="qodef-btn qodef-btn-solid  button-primary action" value="<?php esc_attr_e( 'Clean Up', 'qode-optimizer' ); ?>" />
						<span class="qodef-spinner-loading qodef-hidden"><?php qode_optimizer_framework_svg_icon( 'spinner' ); ?></span>
						<span class="qodef-message qodef-hidden"></span>
					</div>
				</div>
			</div>

			<div class="qodef-utility-section">
				<h2 class="qodef-utility-section-title"><?php esc_html_e( 'Utilities', 'qode-optimizer' ); ?></h2>
				<div class="qodef-utility-header-holder">
					<h3><?php esc_html_e( 'Optimization History Removal', 'qode-optimizer' ); ?></h3>
					<p><?php esc_html_e( 'The full image optimization history is saved in the system database storage. This history keeps track of all modifications made on each of the optimized images so that the efficiency of the optimization process can be observed. It also makes sure that the images are fully restorable to their original forms and prevents the cascading optimization of images. To perform additional image optimization after the initial optimization process, you can delete the optimization history here, effectively making the modified images ‘new’ originals', 'qode-optimizer' ); ?></p>
				</div>
				<?php $content_holder_classes = array( 'qodef-utility-form-content-holder' ); ?>
				<div <?php qode_optimizer_class_attribute( $content_holder_classes ); ?>>
					<div class="qodef-utility-form-content qodef-last">
						<?php if ( $option_array['optimized_count'] > 0 ) { ?>
							<div class="qodef-note">
								<?php
								esc_html_e( 'There are some optimized image(s) right now. Count: ', 'qode-optimizer' );
								echo esc_html( $option_array['optimized_count'] );
								?>
							</div>
						<?php } else { ?>
							<div class="qodef-note"><?php esc_html_e( 'There are no optimized images right now.', 'qode-optimizer' ); ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="qodef-utility-form-action-holder">
					<div class="qodef-utility-form-action">
						<input id="qodef-utility-delete-optimization-history" <?php echo esc_html( 0 === $option_array['optimized_count'] ? 'disabled' : '' ); ?> name="utility_delete_optimization_history" type="submit" class="qodef-btn qodef-btn-solid button-primary action" value="<?php esc_attr_e( 'Remove', 'qode-optimizer' ); ?>" />
						<span class="qodef-spinner-loading qodef-hidden"><?php qode_optimizer_framework_svg_icon( 'spinner' ); ?></span>
						<span class="qodef-message qodef-hidden"></span>
					</div>
				</div>
			</div>

			<div class="qodef-utility-section">
				<div class="qodef-utility-header-holder">
					<h3><?php esc_html_e( 'WebP Images Removal', 'qode-optimizer' ); ?></h3>
					<p><?php esc_html_e( 'If you don\'t need WebP images created by optimization processes, you remove them from the system here', 'qode-optimizer' ); ?></p>
				</div>
				<?php $content_holder_classes = array( 'qodef-utility-form-content-holder' ); ?>
				<div <?php qode_optimizer_class_attribute( $content_holder_classes ); ?>>
					<div class="qodef-utility-form-content qodef-last">
						<?php if ( $option_array['webp_images_check']['webp_total_image_count'] > 0 ) { ?>
							<div class="qodef-note">
								<?php
								esc_html_e( 'There are some optimized image(s) that include WebP images as well. Count: ', 'qode-optimizer' );
								echo esc_html( $option_array['webp_images_check']['webp_total_image_count'] );
								?>
							</div>
						<?php } else { ?>
							<div class="qodef-note"><?php esc_html_e( 'There are no optimized images that include WebP images as well.', 'qode-optimizer' ); ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="qodef-utility-form-action-holder">
					<div class="qodef-utility-form-action">
						<input id="qodef-utility-delete-webp-images" <?php echo esc_html( 0 === $option_array['webp_images_check']['webp_total_image_count'] ? 'disabled' : '' ); ?> name="utility_delete_webp_images" type="submit" class="qodef-btn qodef-btn-solid button-primary action" value="<?php esc_attr_e( 'Remove Created Ones Only', 'qode-optimizer' ); ?>" />
						<span class="qodef-spinner-loading qodef-hidden"><?php qode_optimizer_framework_svg_icon( 'spinner' ); ?></span>
						<span class="qodef-message qodef-hidden"></span>
					</div>
					<div class="qodef-utility-form-action">
						<input id="qodef-utility-delete-all-webp-images" name="utility_delete_all_webp_images" type="submit" class="qodef-btn qodef-btn-solid button-primary action" value="<?php esc_attr_e( 'Remove All On Server', 'qode-optimizer' ); ?>" />
						<span class="qodef-spinner-loading qodef-hidden"><?php qode_optimizer_framework_svg_icon( 'spinner' ); ?></span>
						<span class="qodef-message qodef-hidden"></span>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
