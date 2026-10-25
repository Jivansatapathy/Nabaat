<?php
/**
 * NABAAT — Assign WooCommerce categories from SKUs
 *
 * Rules live in includes/category-rules.json (SKU prefix + optional name keyword -> category path).
 * Admin page WooCommerce > NABAAT Categories previews and applies them; categories are only added,
 * never removed (except "Uncategorized" once a real category is set), and Undo restores the old set.
 * New or edited products saved without a category are filed automatically.
 *
 * @package Nabaat_Luxury_Product_Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_Category_Assigner {

	const LOG = 'nabaat_cat_assign_log'; // product id => category ids before the first change

	/**
	 * Hooks
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_nabaat_cat_assign', array( $this, 'apply' ) );
		add_action( 'admin_post_nabaat_cat_undo', array( $this, 'undo' ) );
		add_action( 'woocommerce_new_product', array( $this, 'auto_assign' ) );
		add_action( 'woocommerce_update_product', array( $this, 'auto_assign' ) );
	}

	/**
	 * Rules from category-rules.json
	 *
	 * @return array
	 */
	public static function rules() {
		static $rules = null;
		if ( null === $rules ) {
			$json  = json_decode( (string) file_get_contents( NABAAT_LUXURY_DIR . 'includes/category-rules.json' ), true );
			$rules = isset( $json['rules'] ) && is_array( $json['rules'] ) ? $json['rules'] : array();
		}
		return $rules;
	}

	/**
	 * Category path (parent first) for a product, or null when no rule matches
	 *
	 * @param string $sku  SKU
	 * @param string $name Product name
	 * @return array|null
	 */
	public static function path_for( $sku, $name ) {
		foreach ( self::rules() as $rule ) {
			$by_sku  = ! empty( $rule['sku'] );
			$by_name = ! empty( $rule['name'] );
			if ( ! $by_sku && ! $by_name ) {
				continue;
			}
			if ( $by_sku && ! preg_match( '/' . str_replace( '/', '\/', $rule['sku'] ) . '/i', (string) $sku ) ) {
				continue;
			}
			if ( $by_name && false === stripos( (string) $name, $rule['name'] ) ) {
				continue;
			}
			return $rule['path'];
		}
		return null;
	}

	/**
	 * Category ids for a path. Matches existing categories by name under the right parent;
	 * with $create, makes the missing ones.
	 *
	 * @param array $path   Category names, parent first
	 * @param bool  $create Create missing categories
	 * @return array array( ids, missing names )
	 */
	public static function term_ids( $path, $create ) {
		$parent  = 0;
		$ids     = array();
		$missing = array();
		foreach ( $path as $name ) {
			$found = 0;
			$kids  = $parent || ! $missing ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $parent ) ) : array();
			foreach ( is_wp_error( $kids ) ? array() : $kids as $term ) {
				if ( 0 === strcasecmp( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ), $name ) ) { // names are stored with &amp;
					$found = (int) $term->term_id;
					break;
				}
			}
			if ( ! $found && $create ) {
				$made  = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) );
				$found = is_wp_error( $made ) ? 0 : (int) $made['term_id'];
			}
			if ( ! $found ) {
				$missing[] = $name;
				$parent    = 0;
				continue;
			}
			$ids[]  = $found;
			$parent = $found;
		}
		return array( $ids, $missing );
	}

	/**
	 * Every product: what it has, what the rules say, what would be added
	 *
	 * @return array array( todo, ok count, unmatched )
	 */
	public static function scan() {
		$default   = (int) get_option( 'default_product_cat', 0 );
		$todo      = array();
		$ok        = 0;
		$unmatched = array();
		$ids       = wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'return' => 'ids' ) );
		foreach ( $ids as $pid ) {
			$p = wc_get_product( $pid );
			if ( ! $p ) {
				continue;
			}
			$path = self::path_for( $p->get_sku(), $p->get_name() );
			if ( ! $path ) {
				$unmatched[ $pid ] = $p->get_name() . ( $p->get_sku() ? ' (' . $p->get_sku() . ')' : ' (no SKU)' );
				continue;
			}
			list( $want, $missing ) = self::term_ids( $path, false );
			$have = array_map( 'intval', wc_get_product_cat_ids( $pid ) );
			$add  = array_diff( $want, $have );
			if ( $add || $missing ) {
				$todo[ $pid ] = array( 'name' => $p->get_name(), 'path' => $path, 'missing' => $missing, 'only_default' => ! array_diff( $have, array( $default ) ) );
			} else {
				$ok++;
			}
		}
		return array( $todo, $ok, $unmatched );
	}

	/**
	 * Add the rule's categories to a product (dropping "Uncategorized" once it has a real one)
	 *
	 * @param int   $pid  Product id
	 * @param array $path Category path
	 */
	private static function assign( $pid, $path ) {
		list( $want ) = self::term_ids( $path, true );
		if ( ! $want ) {
			return;
		}
		$default = (int) get_option( 'default_product_cat', 0 );
		$have    = array_map( 'intval', wc_get_product_cat_ids( $pid ) );
		$new     = array_values( array_diff( array_unique( array_merge( $have, $want ) ), array( $default ) ) );
		wp_set_object_terms( $pid, $new, 'product_cat' );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $pid );
		}
	}

	/**
	 * Products saved with no category (or only "Uncategorized") are filed by their SKU
	 *
	 * @param int $pid Product id
	 */
	public function auto_assign( $pid ) {
		$default = (int) get_option( 'default_product_cat', 0 );
		$have    = array_map( 'intval', wc_get_product_cat_ids( $pid ) );
		if ( array_diff( $have, array( $default ) ) ) {
			return; // already categorised by hand: leave it alone
		}
		$p    = wc_get_product( $pid );
		$path = $p ? self::path_for( $p->get_sku(), $p->get_name() ) : null;
		if ( $path ) {
			self::assign( $pid, $path ); // wp_set_object_terms does not re-fire the save hooks
		}
	}

	/**
	 * Admin page
	 */
	public function menu() {
		add_submenu_page( 'woocommerce', 'NABAAT Categories', 'NABAAT Categories', 'manage_woocommerce', 'nabaat-categories', array( $this, 'page' ) );
	}

	public function page() {
		list( $todo, $ok, $unmatched ) = self::scan();
		$logged = count( (array) get_option( self::LOG, array() ) );
		$notice = get_transient( 'nabaat_cat_notice_' . get_current_user_id() );
		delete_transient( 'nabaat_cat_notice_' . get_current_user_id() );
		$button = function ( $action, $label, $primary ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px">';
			echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
			wp_nonce_field( $action );
			submit_button( $label, $primary ? 'primary' : 'secondary', 'submit', false );
			echo '</form>';
		};
		?>
		<div class="wrap">
			<h1>NABAAT Categories</h1>
			<?php if ( $notice ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>
			<p>Each product is filed by its SKU (for example <code>FC-</code> → Appliance Covers › Fridge Covers). Categories are only added, never removed. Products saved later without a category are filed automatically.</p>
			<p><strong><?php echo esc_html( $ok ); ?></strong> products already have the right categories.</p>

			<h2><?php echo esc_html( count( $todo ) ); ?> products to file</h2>
			<?php if ( $todo ) : ?>
				<table class="widefat striped" style="max-width:980px"><thead><tr><th>Product</th><th>Categories to add</th></tr></thead><tbody>
				<?php foreach ( $todo as $pid => $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a></td>
						<td><?php echo esc_html( implode( ' › ', $row['path'] ) ); ?><?php echo $row['missing'] ? esc_html( ' (creates: ' . implode( ', ', $row['missing'] ) . ')' ) : ''; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody></table>
				<p><?php $button( 'nabaat_cat_assign', sprintf( 'File %d products', count( $todo ) ), true ); ?></p>
			<?php endif; ?>

			<?php if ( $unmatched ) : ?>
				<h2><?php echo esc_html( count( $unmatched ) ); ?> products with no matching rule</h2>
				<p>Their SKU prefix is not in <code>includes/category-rules.json</code>; set their category by hand.</p>
				<?php foreach ( $unmatched as $pid => $label ) : ?>
					<div><a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( $label ); ?></a></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<h2>Undo</h2>
			<p><?php echo esc_html( $logged ? sprintf( '%d products can be put back to the categories they had before this tool changed them.', $logged ) : 'Nothing to undo.' ); ?></p>
			<?php if ( $logged ) : ?>
				<?php $button( 'nabaat_cat_undo', 'Undo category changes', false ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Guard for both actions
	 *
	 * @param string $action Action / nonce name
	 */
	private function guard( $action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'You are not allowed to change product categories.' );
		}
		check_admin_referer( $action );
	}

	private function done( $message ) {
		set_transient( 'nabaat_cat_notice_' . get_current_user_id(), $message, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=nabaat-categories' ) );
		exit;
	}

	public function apply() {
		$this->guard( 'nabaat_cat_assign' );
		list( $todo ) = self::scan();
		$log          = (array) get_option( self::LOG, array() );
		foreach ( $todo as $pid => $row ) {
			if ( ! isset( $log[ $pid ] ) ) {
				$log[ $pid ] = array_map( 'intval', wc_get_product_cat_ids( $pid ) );
			}
			self::assign( $pid, $row['path'] );
		}
		update_option( self::LOG, $log, false );
		$this->done( sprintf( 'Filed %d products into their categories.', count( $todo ) ) );
	}

	public function undo() {
		$this->guard( 'nabaat_cat_undo' );
		$log = (array) get_option( self::LOG, array() );
		foreach ( $log as $pid => $ids ) {
			wp_set_object_terms( (int) $pid, array_map( 'intval', $ids ), 'product_cat' );
		}
		delete_option( self::LOG );
		$this->done( sprintf( 'Put %d products back to their previous categories.', count( $log ) ) );
	}
}
