<?php
/**
 * Post notifications.
 *
 * @package     SlackNotifications\Notifications
 * @subpackage  Post
 * @author      Dor Zuberi <webmaster@dorzki.co.il>
 * @link        https://www.dorzki.co.il
 * @since       2.0.0
 * @version     2.0.0
 */

namespace SlackNotifications\Notifications;

// Block direct access to the file via url.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Post
 *
 * @package SlackNotifications\Notifications
 */
class Post extends Notification_Type {

	/**
	 * Post constructor.
	 */
	public function __construct() {

		$this->object_type    = 'post';
		$this->object_label   = esc_html__( 'Posts', 'dorzki-notifications-to-slack' );
		$this->object_options = [
			'new_post'     => [
				'label'    => esc_html__( 'Post Published', 'dorzki-notifications-to-slack' ),
				'hooks'    => [
					'auto-draft_to_publish' => 'post_published',
					'draft_to_publish'      => 'post_published',
					'future_to_publish'     => 'post_published',
					'pending_to_publish'    => 'post_published',
				],
				'priority' => 10,
				'params'   => 1,
			],
			'future_post'  => [
				'label'    => esc_html__( 'Post Scheduled', 'dorzki-notifications-to-slack' ),
				'hooks'    => [
					'auto-draft_to_future' => 'post_scheduled',
					'draft_to_future'      => 'post_scheduled',
				],
				'priority' => 10,
				'params'   => 1,
			],
			'pending_post' => [
				'label'    => esc_html__( 'Post Pending', 'dorzki-notifications-to-slack' ),
				'hooks'    => [
					'auto-draft_to_pending' => 'post_pending',
					'draft_to_pending'      => 'post_pending',
				],
				'priority' => 10,
				'params'   => 1,
			],
			'update_post'  => [
				'label'    => esc_html__( 'Post Updated', 'dorzki-notifications-to-slack' ),
				'hooks'    => [
					'publish_to_publish' => 'post_updated',
				],
				'priority' => 10,
				'params'   => 1,
			],
			'trash_post'   => [
				'label'    => esc_html__( 'Post Moved to Trash', 'dorzki-notifications-to-slack' ),
				'hooks'    => [
					'trashed_post' => 'post_trashed',
				],
				'priority' => 10,
				'params'   => 1,
			],
		];

		parent::__construct();

	}


	/**
	 * Retrieve post terms.
	 *
	 * @param $post
	 * @param $term_type
	 *
	 * @return array|bool|mixed|string
	 */
	private function get_post_terms( $post, $term_type ) {

		if ( empty( $post ) || empty( $term_type ) ) {
			return [];
		}

		$terms_names = [];
		$terms       = get_the_terms( $post->ID, $term_type );

		if ( is_wp_error( $terms ) || false === $terms ) {
			return false;
		}

		foreach ( $terms as $term ) {
			$terms_names[] = $term->name;
		}

		if ( 1 === count( $terms_names ) ) {
			return $terms_names[ 0 ];
		}

		return implode( ', ', $terms_names );

	}


	/**
	 * Post notification when a new post has been posted.
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function post_published( $post ) {

		if ( empty( $post ) || ! is_object( $post ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			return false;
		}

		// Build notification
		$message = __( ':memo: The post *<%s|%s>* was published right now!', 'dorzki-notifications-to-slack' );
		$message = sprintf( $message, get_permalink( $post->ID ), $post->post_title );

		$attachments = [
			[
				'title' => esc_html__( 'Post Author', 'dorzki-notifications-to-slack' ),
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Published Date', 'dorzki-notifications-to-slack' ),
				'value' => get_the_date( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Post Categories', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'category' ),
				'short' => false,
			],
			[
				'title' => esc_html__( 'Post Tags', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'post_tag' ),
				'short' => false,
			],
		];

		return $this->slack_bot->send_message( $message, $attachments, [
			'color' => '#3498db',
		] );

	}


	/**
	 * Post notification when a post is scheduled to be published.
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function post_scheduled( $post ) {

		if ( empty( $post ) || ! is_object( $post ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			return false;
		}

		// Build notification
		$message = __( ':clock3: The post *<%s|%s>* was scheduled to be published on *%s*.', 'dorzki-notifications-to-slack' );
		$message = sprintf( $message, get_permalink( $post->ID ), $post->post_title, get_the_date( null, $post->ID ) );

		$attachments = [
			[
				'title' => esc_html__( 'Post Author', 'dorzki-notifications-to-slack' ),
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Scheduled Date', 'dorzki-notifications-to-slack' ),
				'value' => get_the_date( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Scheduled Time', 'dorzki-notifications-to-slack' ),
				'value' => get_the_time( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Post Categories', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'category' ),
				'short' => false,
			],
			[
				'title' => esc_html__( 'Post Tags', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'post_tag' ),
				'short' => false,
			],
		];

		return $this->slack_bot->send_message( $message, $attachments, [
			'color' => '#2980b9',
		] );

	}


	/**
	 * Post notification when a post is pending approval.
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function post_pending( $post ) {

		if ( empty( $post ) || ! is_object( $post ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			return false;
		}

		// Build notification
		$message = __( ':eye: The post *<%s|%s>* is pending approval.', 'dorzki-notifications-to-slack' );
		$message = sprintf( $message, get_permalink( $post->ID ), $post->post_title );

		$attachments = [
			[
				'title' => esc_html__( 'Post Author', 'dorzki-notifications-to-slack' ),
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Pending Date', 'dorzki-notifications-to-slack' ),
				'value' => get_the_date( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Post Categories', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'category' ),
				'short' => false,
			],
			[
				'title' => esc_html__( 'Post Tags', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'post_tag' ),
				'short' => false,
			],
		];

		return $this->slack_bot->send_message( $message, $attachments, [
			'color' => '#2980b9',
		] );

	}


	/**
	 * Post notification when a post was updated.
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function post_updated( $post ) {

		if ( empty( $post ) || ! is_object( $post ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			return false;
		}

		// Build notification
		$message = __( ':pencil2: The post *<%s|%s>* has been updated right now.', 'dorzki-notifications-to-slack' );
		$message = sprintf( $message, get_permalink( $post->ID ), $post->post_title );

		$attachments = [
			[
				'title' => esc_html__( 'Post Author', 'dorzki-notifications-to-slack' ),
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Update Date', 'dorzki-notifications-to-slack' ),
				'value' => get_the_modified_date( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Post Categories', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'category' ),
				'short' => false,
			],
			[
				'title' => esc_html__( 'Post Tags', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'post_tag' ),
				'short' => false,
			],
		];

		return $this->slack_bot->send_message( $message, $attachments, [
			'color' => '#2980b9',
		] );

	}


	/**
	 * Post notification when a post was trashed.
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function post_trashed( $post_id ) {

		// Get comment
		$post = get_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			return false;
		}

		// Build notification
		$message = __( ':wastebasket: The post *<%s|%s>* was moved to trash.', 'dorzki-notifications-to-slack' );
		$message = sprintf( $message, get_permalink( $post->ID ), $post->post_title );

		$attachments = [
			[
				'title' => esc_html__( 'Post Author', 'dorzki-notifications-to-slack' ),
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Trashed Date', 'dorzki-notifications-to-slack' ),
				'value' => get_the_modified_date( null, $post->ID ),
				'short' => true,
			],
			[
				'title' => esc_html__( 'Post Categories', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'category' ),
				'short' => false,
			],
			[
				'title' => esc_html__( 'Post Tags', 'dorzki-notifications-to-slack' ),
				'value' => $this->get_post_terms( $post, 'post_tag' ),
				'short' => false,
			],
		];

		return $this->slack_bot->send_message( $message, $attachments, [
			'color' => '#e74c3c',
		] );

	}

}