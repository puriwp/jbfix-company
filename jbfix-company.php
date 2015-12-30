<?php

/**
 * @link              http://minimalthemes.net/
 * @since             1.0.0
 * @package           Jbfix_Company
 *
 * @wordpress-plugin
 * Plugin Name:       Jobboard Company Fix
 * Plugin URI:        http://minimalthemes.net/
 * Description:       Plugin to fix company database between backend and frontend for Jobboard theme.
 * Version:           1.0.2
 * Author:            MinimalThemes
 * Author URI:        http://minimalthemes.net/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 */
function activate_jbfix_company() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/activator.class.php';
	Jbfix_Company_Activator::activation_check();

}
register_activation_hook( __FILE__, 'activate_jbfix_company' );

class Jbfix_Company {

	var $menu_id;

	function __construct() {

		add_action( 'admin_menu', array( &$this, 'jbfix_company_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'jbfix_company_admin_enqueues' ) );
		add_action( 'wp_ajax_jbfix_company', array( &$this, 'jbfix_company_ajax_proccess' ) );
		add_filter( 'post_row_actions', array( &$this, 'jbfix_company_fixthis' ), 10, 2 );

		$this->capability = apply_filters( 'jbfix_company_cap', 'edit_theme_options' );
		$this->companypost = wp_count_posts('company','readable')->publish;
	}

	function jbfix_company_admin_menu() {
		$this->menu_id = add_management_page(
			'jBoard Company Fix',
			'jBoard Company Fix',
			$this->capability,
			'jbfix-company',
			array( &$this, 'jbfix_company_interface' )
		);
	}

	function jbfix_company_fixthis( $actions, $post ) {

		if ( $post->post_type == 'company' ) {
			if ( get_post_meta( $post->ID, '_jboard_company_mb_updated', true ) ) {
				return $actions;
			}
			$qwe = add_query_arg(
				array( 'page' => 'jbfix-company', 'goback' => true, 'ids' => $post->ID ),
				admin_url( 'tools.php' ) );
			$url = wp_nonce_url( $qwe, 'jbfix-company-post' );
			$actions['jbfix-company'] = '<a href="'.esc_url($url).'">Fix This</a>';
		}
		return $actions;
	}

	function jbfix_company_admin_enqueues( $hooks ) {
		if ( $hooks != $this->menu_id )
			return;

		wp_enqueue_script(
			'jquery-ui-progressbar',
			plugins_url( 'jquery-ui/jquery.ui.progressbar.min.js', __FILE__ ),
			array( 'jquery-ui-core', 'jquery-ui-widget' ),
			'1.8.6' );

		wp_enqueue_style(
			'jquery-ui-jbfix-company',
			plugins_url( 'jquery-ui/redmond/jquery-ui-1.7.2.custom.css', __FILE__ ),
			array(),
			'1.7.2' );
	}

	function jbfix_company_interface() {

		global $wpdb;

		?>

<?php if( $count = $this->jbfix_company_get_fixed() ): ?>
<div id="message" class="updated"><p>Total company fixed: <?php echo $count; ?> from <?php echo $this->companypost; ?></p></div>
<?php else: ?>
<div id="message" class="updated fade" style="display:none"></div>
<?php endif; ?>

<div class="wrap jbfix-company">
	<h2>JobBoard Fix Company Database</h2>

	<?php
		if ( isset( $_POST['jbfix-company'] ) || ! empty( $_REQUEST['ids'] ) ) {

			if ( ! current_user_can( $this->capability ) ) {
				wp_die( __( 'Cheatin&#8217; uh?' ) );
			}

			// Form nonce check
			check_admin_referer( 'jbfix-company-post' );

			if ( ! empty( $_REQUEST['ids'] ) ) {
				$ids = (int) $_REQUEST['ids'];
			}
			else {
				if ( ! $comps = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'company' ORDER BY ID DESC" ) ) {
					echo '<p>Unable to find any company post type.</p></div>';
					return;
				}

				$ids = array();
				foreach ( $comps as $comp )
					$ids[] = $comp->ID;
				$ids = implode( ',', $ids );
			}

			echo '<p>Please be patient while the company post database are fixed.</p>';

			$count = count( explode( ',', $ids ) );

			$nonce_url = wp_nonce_url( admin_url( 'tools.php?page=jbfix-company&goback=1' ), 'jbfix-company-post' );

			$text_goback = !empty($_GET['goback']) ?
				sprintf( 'To go back to the previous page, <a href="%s">click here</a>.', 'javascript:history.go(-1)' )
				: sprintf( '<a href="%s">Back</a>.', admin_url( 'tools.php?page=jbfix-company' ) );
			$text_failures = sprintf(
				'All done! %1$s post(s) were successfully fixed in %2$s seconds and there were %3$s failure(s). ' .
				'To try fix the failed posts again, <a href="%4$s">click here</a>. %5$s', "' + rt_successes + '",
				"' + rt_totaltime + '",
				"' + rt_errors + '",
				add_query_arg( 'ids', "' + rt_failedlist + '", $nonce_url ),
				$text_goback );

			$text_nofailures = sprintf(
				'All done! %1$s post(s) were successfully fixed in %2$s seconds and there were 0 failures. %3$s',
				"' + rt_successes + '",
				"' + rt_totaltime + '",
				$text_goback );
			?>

	<noscript><p><em>You must enable Javascript in order to proceed!</em></p></noscript>

	<div id="jbfix-bar" style="position:relative;height:25px;">
		<div id="jbfix-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="jbfix-stop" id="jbfix-stop" value="Abort Proccess" /></p>

	<h3 class="title">Debugging Report</h3>

	<p>
		<?php printf( 'Total Company: %s', $count ); ?><br />
		<?php printf( 'Company Fixed: %s', '<span id="jbfix-debug-successcount">0</span>' ); ?><br />
		<?php printf( 'Fix Failures: %s', '<span id="jbfix-debug-failurecount">0</span>' ); ?>
	</p>

	<ol id="jbfix-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var rt_images = [<?php echo $ids; ?>];
			var rt_total = rt_images.length;
			var rt_count = 1;
			var rt_percent = 0;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			// Create the progress bar
			$("#jbfix-bar").progressbar();
			$("#jbfix-bar-percent").html( "0%" );

			// Stop button
			$("#jbfix-stop").click(function() {
				rt_continue = false;
				$('#jbfix-stop').val("Stopping...");
			});

			$("#jbfix-debuglist li").remove();

			// Called after each fix. Updates debug information and the progress bar.
			function jbFixUpdateStatus( id, success, response ) {
				$("#jbfix-bar").progressbar( "value", ( rt_count / rt_total ) * 100 );
				$("#jbfix-bar-percent").html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
				rt_count = rt_count + 1;

				if ( success ) {
					rt_successes = rt_successes + 1;
					$("#jbfix-debug-successcount").html(rt_successes);
					$("#jbfix-debuglist").append("<li>" + response.success + "</li>");
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					$("#jbfix-debug-failurecount").html(rt_errors);
					$("#jbfix-debuglist").append("<li>" + response.error + "</li>");
				}
			}

			// Called when all images have been processed. Shows the results and cleans up.
			function jbFixFinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );

				$('#jbfix-stop').hide();

				if ( rt_errors > 0 ) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				$("#message").html("<p><strong>" + rt_resulttext + "</strong></p>");
				$("#message").show();
			}

			// Regenerate a specified image via AJAX
			function jbFixCompany( id ) {
				$("#message").hide();
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: { action: "jbfix_company", id: id },
					success: function( response ) {
						if ( response !== Object( response ) || ( typeof response.success === "undefined" && typeof response.error === "undefined" ) ) {
							response = new Object;
							response.success = false;
							response.error = "The fixing request was abnormally terminated (ID " + id + ").";
						}

						if ( response.success ) {
							jbFixUpdateStatus( id, true, response );
						}
						else {
							jbFixUpdateStatus( id, false, response );
						}

						if ( rt_images.length && rt_continue ) {
							jbFixCompany( rt_images.shift() );
						}
						else {
							jbFixFinishUp();
						}
					},
					error: function( response ) {
						jbFixUpdateStatus( id, false, response );

						if ( rt_images.length && rt_continue ) {
							jbFixCompany( rt_images.shift() );
						}
						else {
							jbFixFinishUp();
						}
					}
				});
			}

			jbFixCompany( rt_images.shift() );
		});
	// ]]>
	</script>

		<?php
		} //if post fixed
		else {
?>
	<div class="wrap">
		<h3>Bug Fix For Older Company Database</h3>
		<p><strong>This tool is for:</strong></p>
		<ol>
			<li>Old jobboard user before version 2.5.1</li>
			<li>Have several Companies post inputted via frontend</li>
			<li>Never edited any user company post via backend</li>
		</ol>

		<p><strong>This tool is not for:</strong></p>
		<ol>
			<li>Newly user just bought JobBoard Theme on v2.5.1</li>
			<li>Old users who has never upgraded to JobBoard v2.5.1</li>
			<li>Old users who currently don't have any Companies post</li>
		</ol>

		<h3>Bug - Inaccurate backend company data with the data from the frontend</h3>
		<p><strong>Test case :</strong></p>
		<ol>
			<li> Create new company from frontend with user role Job Lister</li>
			<li> Add at least one company services, clients, and portofolio</li>
			<li> Edit post that you just added from the frontend via wp-admin</li>
			<li> The company data such as services, clients, portofolio has gone</li>
			<li> But the data is not actually gone</li>
		</ol>
		<p>With this plugin, company data inputted from frontend will be readable in the backend,<br>
		and <em>vice versa</em>.</p>
	</div>

	<form method="post" action="">

		<?php wp_nonce_field( 'jbfix-company-post' ); ?>

		<p><strong>IMPORTANT!</strong> before using this tool, make sure you already have backup file for companies post type.</p>

		<p>Backup your companies database, <a href="<?php echo admin_url('export.php') ?>">click here</a>.</p>

		<p><br>To begin, just press the button below.</p>

		<p><input type="submit" class="button hide-if-no-js" name="jbfix-company" id="jbfix-company" value="Fix All Company Posts" /></p>

		<noscript><p><em>You must enable Javascript in order to proceed!</em></p></noscript>

	</form>
<?php
		}
?>
</div>

<?php
	}

	function jbfix_company_ajax_proccess() {
		@error_reporting( 0 ); // Don't break the JSON result

		header( 'Content-type: application/json' );

		$id = (int) $_REQUEST['id'];
		$comp = get_post( $id );

		if ( ! $comp || 'company' != $comp->post_type ) {
			die( json_encode(
				array( 'error' => 'FAILED: ' .esc_html( $_REQUEST['id'] ). ' is an invalid company post ID.' )
			));
		}

		if ( ! current_user_can( $this->capability ) ) {
			$this->die_json_error_msg( $comp->ID, "Your user account doesn't have permission to fix company post database" );
		}

		@set_time_limit( 900 ); // 5 minutes per image should be PLENTY

		if ( ! get_post_meta( $comp->ID, '_jboard_company_mb_updated', true ) ) {

			$add_field = $this->jbfix_add_company_field( $comp->ID );

			if ( $add_field ) {
				update_post_meta( $comp->ID, '_jboard_company_mb_updated', '1' );
			} else {
				$this->die_json_error_msg( $comp->ID, "Failed to update this database." );
			}
		}

		die( json_encode(
			array( 'success' =>
				sprintf( '&quot;%1$s&quot; (ID %2$s) was successfully fixed in %3$s seconds.',
					esc_html( get_the_title( $comp->ID ) ),
					$comp->ID,
					timer_stop() )
			)
		));
	}

	function jbfix_add_company_field( $comp_id ) {

		if ( ! $comp_id ) {
			return false;
		}

		update_post_meta( $comp_id, 'jobboard_company_mb_address_fields', array(
			'_jboard_company_address_gmap_latitude',
			'_jboard_company_address_gmap_longitude',
			'_jboard_company_address',
			'_jboard_company_phone',
			'_jboard_company_email'
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_portfolio_fields', array(
			'_jboard_company_portfolio_headline',
			'_jboard_company_portfolio_group_container'
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_testimonial_fields', array(
			'_jboard_company_testimonial_headline',
			'_jboard_company_testimonial_content',
			'_jboard_company_testimonial_author',
			'_jboard_company_testimonial_author_occupation',
			'_jboard_company_testimonial_author_url',
			'_jboard_company_testimonial_author_avatar',
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_expertise_fields', array(
			'_jboard_company_expertises_headline',
			'_jboard_company_expertises'
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_clients_fields', array(
			'_jboard_company_client_headline',
			'_jboard_company_client_group_container'
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_services_fields', array(
			'_jboard_company_service_headline',
			'_jboard_company_service_group_container'
		) );

		update_post_meta( $comp_id, 'jobboard_company_mb_fields', array(
			'_jboard_company_description',
			'_jboard_company_overview',
			'_jboard_company_web_address',
			'_jboard_company_social_facebook',
			'_jboard_company_social_twitter',
			'_jboard_company_social_googleplus',
		) );

		//Fix Company Services Data
		$company_service_data = ( get_post_meta( $comp_id, '_jboard_company_service_group_container', true ) )
			? get_post_meta( $comp_id, '_jboard_company_service_group_container', true )
			: array();

		if ( is_array( $company_service_data ) && !empty( $company_service_data ) ) {

			$companyservicedata = array();

			if ( array_key_exists( '_jboard_company_service_group', $company_service_data ) ) {
				$companyservicedata = $company_service_data['_jboard_company_service_group'];
			}
			elseif ( array_key_exists( 'company_service_group', $company_service_data[0] ) ) {
				$companyservicedata = $company_service_data[0]['company_service_group'];
			}

			if ( !empty( $companyservicedata ) ) {
				update_post_meta( $comp_id, '_jboard_company_service_group_container', array(
						array( 'company_service_group' => $companyservicedata )
				) );
			}
		}

		//Fix Company Clients Data
		$company_client_data = ( get_post_meta( $comp_id, '_jboard_company_client_group_container', true ) )
			? get_post_meta( $comp_id, '_jboard_company_client_group_container', true )
			: array();

		if ( is_array( $company_client_data ) && !empty( $company_client_data ) ) {

			$companyclientdata = array();

			if ( array_key_exists( '_jboard_company_client_group', $company_client_data ) ) {
				$companyclientdata = $company_client_data['_jboard_company_client_group'];
			}
			elseif ( array_key_exists( 'company_client_group', $company_client_data[0] ) ) {
				$companyclientdata = $company_client_data[0]['company_client_group'];
			}

			if ( !empty( $companyclientdata ) ) {
				update_post_meta( $comp_id, '_jboard_company_client_group_container', array(
						array( 'company_client_group' => $companyclientdata )
				) );
			}
		}

		//Fix Company Portofolio Data
		$company_portfolio_stored_img = ( get_post_meta( $comp_id, '_jboard_company_portfolio_stored_img', true ) )
			? get_post_meta( $comp_id, '_jboard_company_portfolio_stored_img', true )
			: array();

		$company_portfolio_stored_url = ( get_post_meta( $comp_id, '_jboard_company_portfolio_stored_url', true ) )
			? get_post_meta( $comp_id, '_jboard_company_portfolio_stored_url', true )
			: array();

		$company_portfolio_data = ( get_post_meta( $comp_id, '_jboard_company_portfolio_group_container', true ) )
			? get_post_meta( $comp_id, '_jboard_company_portfolio_group_container', true )
			: array();

		if ( !empty( $company_portfolio_data ) ) {

			$companyportfoliodata = array();

			if ( array_key_exists( '_jboard_company_portfolio_group', $company_portfolio_data ) ) {
				$companyportfoliodata = $company_portfolio_data['_jboard_company_portfolio_group'];
			}
			elseif ( array_key_exists( 'company_portfolio_group', $company_portfolio_data[0] ) ) {
				$companyportfoliodata = $company_portfolio_data[0]['company_portfolio_group'];
			}

			if ( !empty( $companyportfoliodata ) ) {

				foreach ( $companyportfoliodata as $key => $compfolio ) {
					if ( is_numeric( $companyportfoliodata[ $key ]['portfolio_image'] ) ) {
						$companyportfoliodata[ $key ]['portfolio_stored_image_id'] = $companyportfoliodata[ $key ]['portfolio_image'];
						$companyportfoliodata[ $key ]['portfolio_image'] = wp_get_attachment_url( $companyportfoliodata[ $key ]['portfolio_image'] );
					}
				}

				update_post_meta( $comp_id, '_jboard_company_portfolio_group_container', array(
						array( 'company_portfolio_group' => $companyclientdata )
				) );
			}
		} elseif ( !empty( $company_portfolio_stored_img ) ) {

			$companyclientdata = array();

			foreach ( $company_portfolio_stored_img as $key => $img ) {

				$portfolio_url = !empty( $company_portfolio_stored_url[ $key ] )
					? $company_portfolio_stored_url[ $key ] : '';

				$companyclientdata[ $key ] = array(
					'portfolio_image'			=> wp_get_attachment_url( (int)$img ),
					'portfolio_stored_image_id'	=> (int)$img,
					'portfolio_url'				=> $portfolio_url,
				);
			}
			update_post_meta( $comp_id, '_jboard_company_portfolio_group_container', array(
					array( 'company_portfolio_group' => $companyclientdata )
			) );
		}

		return true;
	}

	function die_json_error_msg( $id, $message ) {
		die( json_encode(
			array( 'error' =>
				  printf( '&quot;%1$s&quot; (ID %2$s) failed to fixed. The error message was: %3$s',
						 esc_html( get_the_title( $id ) ),
						 $id,
						 $message
					)
			)
		) );
	}

	function jbfix_company_get_fixed() {

		global $wpdb;

		$r = $wpdb->get_col( $wpdb->prepare( "
			SELECT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s'
			AND p.post_type = '%s'
		", '_jboard_company_mb_updated', 'company' ) );

		$r = is_array( $r ) ? $r : (array)$r;

		return count($r);
	}
}
//END Class

// Start up this plugin
add_action( 'init', 'Jbfix_Company' );
function Jbfix_Company() {
	global $Jbfix_Company;
	$Jbfix_Company = new Jbfix_Company();
}
