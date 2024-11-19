<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

$notes = array(
	'passed'     => esc_html__( 'passed', 'qode-optimizer' ),
	'not_passed' => esc_html__( 'not passed', 'qode-optimizer' ),
	'yes'        => esc_html__( 'yes', 'qode-optimizer' ),
	'no'         => esc_html__( 'no', 'qode-optimizer' ),
	'installed'  => esc_html__( 'installed', 'qode-optimizer' ),
	'executable' => esc_html__( 'executable', 'qode-optimizer' ),
);
?>

<h2><?php esc_html_e( 'Plugin status check', 'qode-optimizer' ); ?></h2>

<div class="qodef-status-section">
	<h3 class="qodef-status-section-title"><?php esc_html_e( 'General', 'qode-optimizer' ); ?></h3>
	<p class="qodef-status-item">
		<span class="qodef-title"><?php esc_html_e( 'Operating System', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value qodef-success">
			<span><?php echo esc_html( Qode_Optimizer_Support::get_system_param( 'operating_system' ) ); ?></span>
		</span>
	</p>
	<p class="qodef-status-item">
		<span class="qodef-title"><?php esc_html_e( 'CPU Architecture', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value qodef-success">
			<span><?php echo esc_html( Qode_Optimizer_Support::get_system_param( 'architecture' ) ); ?></span>
		</span>
	</p>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'php_min_version_exists' ) ) {
			$support_value = $notes['passed'] . ' (' . Qode_Optimizer_Support::get_system_param( 'php_version' ) . ')';
			$support_class = 'qodef-success';
		} else {
			$support_value = $notes['not_passed'] . ' (' . Qode_Optimizer_Support::get_system_param( 'php_version' ) . ')';
			$support_class = 'qodef-fail';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'PHP Version Requirement (PHP v5.5.0 or later)', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
		</span>
	</p>
</div>

<div class="qodef-status-section">
	<h3 class="qodef-status-section-title"><?php esc_html_e( 'PHP Libraries', 'qode-optimizer' ); ?></h3>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'gmagick_support_exists' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
			$support_link  = '';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
			$support_link  = 'https://www.php.net/manual/en/gmagick.installation.php';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'GraphicsMagick Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
			<?php if ( ! empty( $support_link ) ) { ?>
				<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><?php esc_html_e( 'How to install', 'qode-optimizer' ); ?></a>
			<?php } ?>
		</span>
	</p>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'imagick_support_exists' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
			$support_link  = '';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
			$support_link  = 'https://www.php.net/manual/en/imagick.installation.php';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'ImageMagick Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
			<?php if ( ! empty( $support_link ) ) { ?>
				<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><?php esc_html_e( 'How to install', 'qode-optimizer' ); ?></a>
			<?php } ?>
		</span>
	</p>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
			$support_link  = '';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
			$support_link  = 'https://www.php.net/manual/en/image.installation.php';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'GD Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
			<?php if ( ! empty( $support_link ) ) { ?>
				<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><?php esc_html_e( 'How to install', 'qode-optimizer' ); ?></a>
			<?php } ?>
		</span>
	</p>
</div>

<div class="qodef-status-section">
	<h3 class="qodef-status-section-title"><?php esc_html_e( 'PHP WebP Support', 'qode-optimizer' ); ?></h3>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'imagick_supports_webp' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
			$support_link  = '';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
			$support_link  = 'https://www.php.net/manual/en/imagick.installation.php';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'ImageMagick Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
			<?php if ( ! empty( $support_link ) ) { ?>
				<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><?php esc_html_e( 'How to install', 'qode-optimizer' ); ?></a>
			<?php } ?>
		</span>
	</p>
	<p class="qodef-status-item">
		<?php
		if ( Qode_Optimizer_Support::get_system_param( 'gd_supports_webp' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
			$support_link  = '';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
			$support_link  = 'https://www.php.net/manual/en/image.installation.php';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'GD Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
			<?php if ( ! empty( $support_link ) ) { ?>
				<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><?php esc_html_e( 'How to install', 'qode-optimizer' ); ?></a>
			<?php } ?>
		</span>
	</p>
</div>

<div class="qodef-status-section">
	<h3 class="qodef-status-section-title"><?php esc_html_e( 'Command Line Tools Support', 'qode-optimizer' ); ?></h3>
	<p class="qodef-status-item">
		<?php
		if ( function_exists( 'exec' ) ) {
			$support_value = $notes['yes'];
			$support_class = 'qodef-success';
		} else {
			$support_value = $notes['no'];
			$support_class = 'qodef-fail';
		}
		?>
		<span class="qodef-title"><?php esc_html_e( 'Support Exists', 'qode-optimizer' ); ?>:</span>
		<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
			<span><?php echo esc_html( $support_value ); ?></span>
		</span>
	</p>
</div>

<div class="qodef-status-section">
	<h3 class="qodef-status-section-title"><?php esc_html_e( 'Command Line Tools', 'qode-optimizer' ); ?></h3>
	<?php
	foreach ( Qode_Optimizer_Support::TOOLS_SUPPORTED as $tool ) {
		$tool_test = Qode_Optimizer_Support::get_tool_test( $tool );
		?>
		<div class="qodef-status-item-holder">
			<p class="qodef-status-item">
				<?php
				if ( $tool_test && array_key_exists( 'executable', $tool_test ) && $tool_test['executable'] ) {
					$support_value = $notes['yes'];
					$support_class = 'qodef-success';
				} else {
					$support_value = $notes['no'];
					$support_class = 'qodef-fail';
				}
				?>
				<span class="qodef-title"><?php echo esc_html( $tool . ' (' . $notes['executable'] . ')' ); ?>:</span>
				<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
					<span><?php echo esc_html( $support_value ); ?></span>
				</span>
			</p>
			<p class="qodef-status-item">
				<?php
				if ( $tool_test && array_key_exists( 'installed', $tool_test ) && $tool_test['installed'] ) {
					$support_value = $notes['yes'];
					$support_class = 'qodef-success';
				} else {
					$support_value = $notes['no'];
					$support_class = 'qodef-fail';
				}
				?>
				<span class="qodef-title"><?php echo esc_html( $tool . ' (' . $notes['installed'] . ')' ); ?>:</span>
				<span class="qodef-value <?php echo esc_attr( $support_class ); ?>">
					<span><?php echo esc_html( $support_value ); ?></span>
				</span>
			</p>
		</div>
	<?php } ?>
</div>
