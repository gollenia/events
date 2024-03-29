<?php

class EM_Speakers {

    public static function register() {
        $instance = new self;
        
        add_action( 'init', array($instance, 'register_post_type') );
        add_action( 'add_meta_boxes', array($instance, 'add_meta_boxes') );
		add_action( 'rest_api_init', array($instance, 'register_metadata'));
        add_action( 'save_post', array($instance, 'save'), 1, 2 );
        add_filter( 'manage_event-speaker_posts_columns', array($instance, 'set_custom_columns') );
        add_action( 'manage_event-speaker_posts_custom_column' , array($instance, 'custom_column'), 10, 2 );
        add_action( 'edit_form_advanced', [$instance, 'add_back_button'] );
    }

	public function register_post_type(){
		$args = apply_filters('em_cpt_speaker', [	
            'public' => false,
            'hierarchical' => false,
            'show_in_rest' => true,
            'show_in_admin_bar' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=event',
            'show_in_nav_menus'=>true,
            'can_export' => true,
            'publicly_queryable' => true,
            'rewrite' => ['slug' => 'event-speaker', 'with_front'=>false],
            'query_var' => false,
            'has_archive' => false,
            'supports' => ['title','thumbnail', 'editor', 'excerpt', 'custom-fields'],
            'label' => __('Speakers','events-manager'),
            'description' => __('Speakers for an event.','events-manager'),
            'labels' => [
                'name' => __('Speakers','events-manager'),
                'singular_name' => __('Speaker','events-manager'),
                'menu_name' => __('Speakers','events-manager'),
                'add_new' => __('Add Speaker','events-manager'),
                'add_new_item' => __('Add New Speaker','events-manager'),
                'edit' => __('Edit','events-manager'),
                'edit_item' => __('Edit Speaker','events-manager'),
                'new_item' => __('New Speaker','events-manager'),
                'view' => __('View','events-manager'),
                'view_item' => __('View Speaker','events-manager'),
                'search_items' => __('Search Speaker','events-manager'),
                'not_found' => __('No Speaker Found','events-manager'),
                'not_found_in_trash' => __('No Speaker Found in Trash','events-manager'),
                'parent' => __('Parent Speaker','events-manager'),
            ],
        ]);

		register_post_type( 'event-speaker', $args );     
    }

	public function add_meta_boxes() {
        add_meta_box(
                'person_details',
                __( 'Person Details', 'ctx-theme' ),
                [$this, 'metabox_callback'],
                'event-speaker',
                'normal'
            ); 
    }

    public function metabox_callback() {
        global $post;
        $email = get_post_meta( $post->ID, 'email', true );
		$gender = get_post_meta( $post->ID, 'gender', true );
		$role = get_post_meta( $post->ID, 'role', true );
        wp_nonce_field( basename( __FILE__ ), 'person_details' );
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . __( 'E-Mail', 'events-manager' ) . '</th><td><input name="email" type="email" value="' . $email . '"></td></tr>';
		echo '<tr><th>' . __( 'Role', 'events-manager' ) . '</th><td><input name="role" type="text" value="' . $role . '"></td><p>' . __("If the speaker has a special Role (e.g. Organizator, Leder, etc), type in here, else leave blank", 'events-manager') . '</p></tr>';
		echo '<tr><th>' . __( 'Gender', 'events-manager' ) . '</th><td><select name="gender"><option value="male" ' . ($gender == 'male' ? 'selected' : '') . '>' . __('Male', 'events-manager') . '</option><option value="female" ' . ($gender == 'female' ? 'selected' : '') . '>' . __('Female', 'events-manager') . '</option></select></td></tr>';
        echo '</tbody></table>';

    }

	public function register_metadata() {
		
		register_post_meta( 'event-speaker', '_email', [
			'type' => 'string',
			'show_in_rest' => [
				'schema' => [
					'default' => '',
					'style' => "string"
				]
				],
			'single'       => true,
			'default'      => '',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		]);

		register_post_meta('event-speaker', '_phone', [
			'type' => 'string',
			'show_in_rest' => [
				'schema' => [
					'default' => '',
					'style' => "string"
				]
				],
			'single'       => true,
			'default'      => '',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		]);

		register_post_meta('event-speaker', '_gender', [
			'type' => 'string',
			'show_in_rest' => [
				'schema' => [
					'default' => '',
					'style' => "string"
				]
				],
			'single'       => true,
			'default'      => 'male',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		]);

		register_post_meta('event-speaker', '_role', [
			'type' => 'string',
			'show_in_rest' => [
				'schema' => [
					'default' => '',
					'style' => "string"
				]
				],
			'single'       => true,
			'default'      => '',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		]);
	}

    public function save( $post_id, $post ) {

        if($post->post_type != "event-speaker" || ! current_user_can( 'edit_post', $post_id )) {
            return $post_id;
        }
    
        if ( ! isset( $_POST['email'] ) || ! wp_verify_nonce( $_POST['person_details'], basename(__FILE__) ) ) {
            return $post_id;
        }
        
        $meta = [
            "email" => sanitize_text_field( $_POST['email'] ),
			"gender" => sanitize_text_field( $_POST['gender'] ),
			"role" => sanitize_text_field( $_POST['role'] )
        ];
    
        foreach ( $meta as $key => $value ) {    
            if ( get_post_meta( $post_id, $key, false ) ) {
                update_post_meta( $post_id, $key, $value );
                continue;
            } 

            if ( ! $value ) {
                delete_post_meta( $post_id, $key );
                continue;
            }
            
            add_post_meta( $post_id, $key, $value); 
        }
    
    }
    
    
    public function set_custom_columns($columns) {
        $columns['email'] = __( 'E-Mail', 'ctx-theme' );

        return $columns;
    }

    public function custom_column( $column, $post_id ) {
        if($column == "email") {
            $email = get_post_meta( $post_id , 'email' , true );
            
            echo $email;
        }
    }

	public static function get($id) {
		if($id == 0) return false;
		$args = array(
			'p'         => $id, // ID of a page, post, or custom type
			'post_type' => 'event-speaker'
		  );
		$query = new WP_Query($args);
		$result = $query->get_posts();
		if(empty($result)) return false;
		$speaker = $result[0];
		
		return $speaker;
	}

    public function add_back_button( $post ) {
        if( $post->post_type == 'event-speaker' )
            echo "<a class='button button-primary button-large' href='edit.php?post_type=event-speaker' id='my-custom-header-link'>" . __('Back', 'ctx-theme') . "</a>";
    }

    
}
    
EM_Speakers::register();