<?php
/**
 * Template pagina di dettaglio del collegamento / bando.
 * Usa i componenti card di Bootstrap Italia (tema Design Scuola Italia).
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id  = get_the_ID();
	$link     = get_post_meta( $post_id, '_dbcu_external_link', true );
	$items    = get_post_meta( $post_id, '_dbcu_attachments', true );
	$items    = is_array( $items ) ? $items : array();
	$thumb_id = get_post_thumbnail_id( $post_id );
	?>
	<main class="dbcu-single container my-4" id="main">
		<article <?php post_class( 'dbcu-single-article' ); ?>>
			<header class="dbcu-single-header">
				<h1 class="dbcu-single-title"><?php the_title(); ?></h1>
			</header>

			<div class="row">
				<?php if ( $thumb_id ) : ?>
					<div class="col-12 col-md-4 dbcu-single-logo-col">
						<figure class="dbcu-single-logo">
							<?php
							echo wp_get_attachment_image(
								$thumb_id,
								'medium',
								false,
								array( 'class' => 'img-fluid', 'loading' => 'eager' )
							);
							?>
						</figure>
						<?php if ( $link ) : ?>
							<a class="btn btn-primary btn-sm w-100 mt-2" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Vai al sito ufficiale', 'db-collegamenti-utili' ); ?>
								<span class="visually-hidden"><?php esc_html_e( '(si apre in una nuova scheda)', 'db-collegamenti-utili' ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="<?php echo esc_attr( $thumb_id ? 'col-12 col-md-8' : 'col-12' ); ?> dbcu-single-body">
					<div class="dbcu-single-content">
						<?php the_content(); ?>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $items ) ) : ?>
				<section class="dbcu-attachments-section mt-4" aria-labelledby="dbcu-att-heading">
					<h2 id="dbcu-att-heading" class="dbcu-attachments-heading">
						<?php esc_html_e( 'Documenti allegati', 'db-collegamenti-utili' ); ?>
					</h2>
					<ul class="dbcu-attachments-list list-unstyled">
						<?php
						foreach ( $items as $item ) {
							$file_id = isset( $item['file_id'] ) ? (int) $item['file_id'] : 0;
							if ( ! $file_id ) {
								continue;
							}
							$file_url = wp_get_attachment_url( $file_id );
							if ( ! $file_url ) {
								continue;
							}
							$att_title = ! empty( $item['title'] ) ? $item['title'] : get_the_title( $file_id );
							$att_desc  = ! empty( $item['desc'] ) ? $item['desc'] : '';
							$att_date  = ! empty( $item['date'] ) ? $item['date'] : '';
							$mime      = get_post_mime_type( $file_id );
							$ext       = strtoupper( pathinfo( get_attached_file( $file_id ), PATHINFO_EXTENSION ) );
							$size      = size_format( (int) filesize( get_attached_file( $file_id ) ) );

							$meta_bits = array();
							if ( $ext ) {
								$meta_bits[] = $ext;
							}
							if ( $size ) {
								$meta_bits[] = $size;
							}
							if ( $att_date ) {
								$meta_bits[] = date_i18n( get_option( 'date_format' ), strtotime( $att_date ) );
							}
							?>
							<li class="dbcu-attachment-item">
								<a class="dbcu-attachment-link" href="<?php echo esc_url( $file_url ); ?>" download>
									<span class="dbcu-attachment-icon" aria-hidden="true">
										<svg class="icon icon-sm"><use xlink:href="#it-clip" href="#it-clip"></use></svg>
									</span>
									<span class="dbcu-attachment-text">
										<span class="dbcu-attachment-title"><?php echo esc_html( $att_title ); ?></span>
										<?php if ( $att_desc ) : ?>
											<span class="dbcu-attachment-desc"><?php echo esc_html( $att_desc ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $meta_bits ) ) : ?>
											<span class="dbcu-attachment-meta"><?php echo esc_html( implode( ' · ', $meta_bits ) ); ?></span>
										<?php endif; ?>
									</span>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
				</section>
			<?php endif; ?>
		</article>

		<p class="mt-4">
			<a href="<?php echo esc_url( get_post_type_archive_link( DBCU_CPT ) ); ?>" class="dbcu-back-link">
				&larr; <?php esc_html_e( 'Tutti i collegamenti utili', 'db-collegamenti-utili' ); ?>
			</a>
		</p>
	</main>
	<?php
endwhile;

get_footer();
