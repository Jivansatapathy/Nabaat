<?php
/**
 * NABAAT Store - Home Page Admin Controller
 *
 * Provides a management panel in WordPress Admin to view draft page status,
 * media upload status, and trigger re-generation.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_Home_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_regenerate_action' ) );
	}

	/**
	 * Register Admin Submenu
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=page',
			__( 'NABAAT Home Page Generator', 'nabaat-home-page-builder' ),
			__( 'NABAAT Home Page', 'nabaat-home-page-builder' ),
			'manage_options',
			'nabaat-home-page-builder',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Handle manual re-generation request
	 */
	public function handle_regenerate_action() {
		if ( ! isset( $_POST['nabaat_home_action'] ) || 'regenerate' !== $_POST['nabaat_home_action'] ) {
			return;
		}

		check_admin_referer( 'nabaat_home_regenerate_nonce', 'nabaat_home_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'nabaat-home-page-builder' ) );
		}

		$result = Nabaat_Home_Installer::install();

		$redirect_url = add_query_arg(
			array(
				'page'    => 'nabaat-home-page-builder',
				'status'  => $result['success'] ? 'success' : 'error',
				'message' => urlencode( $result['message'] ),
			),
			admin_url( 'edit.php?post_type=page' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render Admin Dashboard
	 */
	public function render_admin_page() {
		$page_id      = get_option( 'nabaat_home_page_id', 0 );
		$page         = $page_id ? get_post( $page_id ) : null;
		$media_urls   = get_option( 'nabaat_home_media_urls', array() );
		$installed_at = get_option( 'nabaat_home_installed_at', 'Never' );

		$status_msg = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
		$status_type = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		?>
		<div class="wrap" style="max-width: 960px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			
			<!-- Header Banner -->
			<div style="background: linear-gradient(135deg, #0d2719 0%, #163825 100%); color: #ffffff; padding: 28px 32px; border-radius: 14px; margin: 20px 0 24px 0; box-shadow: 0 10px 25px -5px rgba(13,39,25,0.3);">
				<div style="display: flex; align-items: center; justify-content: space-between;">
					<div>
						<h1 style="color: #ffffff; margin: 0 0 8px 0; font-size: 24px; font-weight: 700;">
							NABAAT Store — Luxury Home Page Generator
						</h1>
						<p style="margin: 0; color: #a7f3d0; font-size: 14px;">
							Automatically builds and synchronizes the high-converting luxury Home draft page with media sideloading into the WordPress Media Library.
						</p>
					</div>
					<span style="background: #f6c467; color: #0d2719; font-weight: 800; font-size: 11px; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;">
						v<?php echo esc_html( NABAAT_HOME_VERSION ); ?>
					</span>
				</div>
			</div>

			<?php if ( $status_msg ) : ?>
				<div class="notice notice-<?php echo 'success' === $status_type ? 'success' : 'error'; ?> is-dismissible">
					<p><?php echo esc_html( $status_msg ); ?></p>
				</div>
			<?php endif; ?>

			<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px;">
				
				<!-- Left Card: Draft Page Status -->
				<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
					<h2 style="font-size: 18px; margin: 0 0 16px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
						Draft Page Status
					</h2>

					<?php if ( $page ) : ?>
						<table class="widefat striped" style="border: none; margin-bottom: 24px;">
							<tbody>
								<tr>
									<td style="font-weight: 600; width: 140px;">Page Title:</td>
									<td><strong><?php echo esc_html( $page->post_title ); ?></strong></td>
								</tr>
								<tr>
									<td style="font-weight: 600;">Status:</td>
									<td>
										<span style="background: #fef3c7; color: #92400e; font-weight: 700; padding: 3px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase;">
											<?php echo esc_html( $page->post_status ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<td style="font-weight: 600;">Page ID:</td>
									<td><code><?php echo esc_html( $page->ID ); ?></code></td>
								</tr>
								<tr>
									<td style="font-weight: 600;">Last Synchronized:</td>
									<td><?php echo esc_html( $installed_at ); ?></td>
								</tr>
							</tbody>
						</table>

						<div style="display: flex; gap: 12px; margin-bottom: 24px;">
							<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>" class="button button-primary" style="background: #0d2719; border-color: #0d2719; padding: 4px 16px;">
								Edit Draft Page in WordPress
							</a>
							<a href="<?php echo esc_url( get_preview_post_link( $page->ID ) ); ?>" target="_blank" class="button button-secondary">
								Preview Draft Page ↗
							</a>
						</div>
					<?php else : ?>
						<p style="color: #64748b;">No draft page has been generated yet.</p>
					<?php endif; ?>

					<form method="post" action="">
						<?php wp_nonce_field( 'nabaat_home_regenerate_nonce', 'nabaat_home_nonce' ); ?>
						<input type="hidden" name="nabaat_home_action" value="regenerate" />
						<button type="submit" class="button button-large" style="background: #f6c467; color: #0d2719; border: 1px solid #e5b252; font-weight: 700;">
							↻ Re-generate / Update Draft Home Page
						</button>
						<p class="description" style="margin-top: 8px;">
							Re-applies the luxury home sections, checks image uploads, and updates the draft page content without altering WordPress headers or footers.
						</p>
					</form>
				</div>

				<!-- Right Card: Media Library Assets -->
				<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
					<h2 style="font-size: 18px; margin: 0 0 16px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
						Uploaded Media Assets
					</h2>
					<p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
						The following luxury images are imported into your WordPress Media Library and linked into the page content:
					</p>

					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php
						$labels = array(
							'hero_1'          => 'Hero Slide 1',
							'hero_2'          => 'Hero Slide 2',
							'hero_3'          => 'Hero Slide 3',
							'hero_4'          => 'Hero Slide 4',
							'ac_cover'        => 'AC Weather Shield (1.5T)',
							'door_seal'       => 'Acoustic Door Bottom Seal',
							'appliance_cover' => 'Appliance Utility Armor',
						);

						foreach ( $labels as $k => $label ) :
							$url = isset( $media_urls[ $k ] ) ? $media_urls[ $k ] : '';
							?>
							<div style="display: flex; align-items: center; gap: 12px; padding: 8px; border: 1px solid #f1f5f9; border-radius: 8px;">
								<?php if ( $url ) : ?>
									<img src="<?php echo esc_url( $url ); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;" alt="<?php echo esc_attr( $label ); ?>" />
								<?php else : ?>
									<div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 6px;"></div>
								<?php endif; ?>
								<div>
									<strong style="display: block; font-size: 13px; color: #1e293b;"><?php echo esc_html( $label ); ?></strong>
									<span style="font-size: 11px; color: #10b981;">✓ Uploaded & Linked</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

			</div>

		</div>
		<?php
	}
}
