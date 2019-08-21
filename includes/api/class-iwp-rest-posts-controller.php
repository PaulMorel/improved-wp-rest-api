<?php

/**
 * Improved WP REST API Posts Controller Class
 * 
 * @package Improved_WP_REST_API
 * @since 1.0
 */
class IWP_REST_Posts_Controller extends WP_REST_Controller
{
   
    /**
     * The namespace of this controller's route.
     *
     * @since 1.0
     * @var string
     */
    protected $namespace;

    /**
     * The base of this controller's route.
     *
     * @since 1.0
     * @var string
     */
    protected $rest_base;

    /**
     * Post type
     *
     * @since 1.0
     * @var string
     */
    protected $post_type;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct($post_type)
    {
        $this->namespace = IWP_REST_NAMESPACE;

        $this->post_type = $post_type;

        $post_type_object = get_post_type_object($this->post_type);
        $this->rest_base = ! empty($post_type_object->rest_base) ? $post_type_object->rest_base : $post_type_object->name;
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 1.0
     *
     * @see register_rest_route()
     */
    public function register_routes()
    {
        
        // Archive
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => /*$this->get_collection_params()*/array(
                    'context'  => $this->get_context_param(),
                    'per_page' => array(
                        'description'       => __( 'Maximum number of items to be returned in result set.' ),
                        'type'              => 'integer',
                        'default'           => 10,
                        'minimum'           => -1,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => 'rest_validate_request_arg',
                    ),
                ),
            ),
            'schema' => array( $this, 'get_public_item_schema' )
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'iwpra' ),
						'type'        => 'integer',
					),
				),
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(
                    'context' => $this->get_context_param(array(
                        'default' => 'view',
                    )),
                ),
            )
        ));


        // Get 
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<slug>[\S]+)', array(
            array(
                'args' => array(
					'slug' => array(
						'description' => __( 'Unique slug for the object.', 'iwpra' ),
						'type'        => 'string',
					),
				),
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(
                    'context' => $this->get_context_param(array(
                        'default' => 'view',
                    )),
                ),
            )
        ));
    }
    
    /**
     * Retrieves a collection of items
     *
     * @since 1.0
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $posts_query = new WP_Query();
        $posts = $posts_query->query([
            'post_type'      => $this->post_type,
            'posts_per_page' => $request['per_page'],
            'page'           => $request['page'],
            'meta_query'     => $request['meta_query']
        ]);


        $data = array();
    
        // print_r($posts_query);


        if ($posts) {
            foreach ($posts as $post) {
                $itemdata = $this->prepare_item_for_response($post, $request);
                $data[] = $this->prepare_response_for_collection($itemdata);
            }
        }
    
        $data = rest_ensure_response($data);
        return $data;
    }

    public function get_items_permissions_check($request)
    {
        return true;
    }

    public function get_item($request)
    {
        //get parameters from request
        $params = $request->get_params();
    
        if ( key_exists('id', $params)) {
            $post = get_post($params['id']);
    
            if (! $post || $this->post_type != $post->post_type) {
                return new WP_Error(
                    'invalid_' . $this->post_type,
                    __('Invalid ' . $this->post_type . ' ID', 'iwpra'),
                    array(
                        'status' => 404,
                    )
                );
            }
            
        } elseif ( key_exists('slug', $params)) {

            $posts = get_posts(array(
                'name' => $params['slug'],
                'posts_per_page' => 1,
                'post_type' => $this->post_type,
                'post_status' => 'publish'
            ));

            if ( ! $posts ) {
                return new WP_Error(
                    "iwpra_invalid_{$this->post_type}_slug",
                    sprintf( __('Invalid %s slug.', 'improved-wp-rest-api'), $this->post_type ),
                    array(
                        'status' => 404,
                    )
                );
            }

            $post = $posts[0];

            if (! $post || $this->post_type != $post->post_type) {
                
                return new WP_Error(
                    "iwpra_invalid_{$this->post_type}_slug",
                    sprintf( __('Invalid %s slug.', 'improved-wp-rest-api'), $this->post_type ),
                    array(
                        'status' => 404,
                    )
                );

            }
            
        } else {
            return new WP_Error(
                "improved_wp_rest_api_bad_request",
                __('Bad request.', 'improved-wp-rest-api'),
                array(
                    'status' => 400,
                )
            );
        }

 

    
        $data = array(
            $this->prepare_response_for_collection(
                $this->prepare_item_for_response($post, $request)
            ),
        );
    
        $data = rest_ensure_response($data);
        return $data;
    }

    /**
     * Prepares restaurant data for return as an object.
     */
    public function prepare_item_for_response($post, $request)
    {
        // Set global post to $post to use "in-the-loop" functions
        $GLOBALS['post'] = $post;
 
        
        $data = array(
            'id'      => get_the_ID(),
            'title'    => get_the_title(),
            'content' => apply_filters('the_content', get_the_content(null, false, $post)),
            'slug'    => $post->post_name,
            'excerpt' => get_the_excerpt(),
            'link'    => get_the_permalink(),
            'status'  => get_post_status(),
            'date' => get_the_date('c'),
            'modified' => get_the_modified_date('c'),
            'order' => get_post_field('menu_order'),
            'author' => $this->get_author(),
            'acf' => $this->get_acf(),
            'comments' => $this->get_comments(),
            'seo' => $this->get_seo(),
            'media' => $this->get_media()
        );

        // Filter out null / falsy values
        $this->filter_false_values( $data );

        return $data;
    }

    
    /**
    * Returns Advanced Custom Fields data is available
    *
    * @param mixed $post_id
    * @return array|bool	associative array where field name => field value or false if ACF isn't activated or installed
    */
    protected function get_acf($post_id = false)
    {
        if (class_exists('ACF')) {
            if ($fields = get_fields($post_id)) {
                return $fields;
            }
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @param bool $post_id
     *
     * @return void
     */
    protected function get_comments($post_id = false)
    {
        if (post_type_supports(get_post_type(), 'comments')) {
            return [
                'status' => comments_open() ? 'open' : 'closed',
                'count' => get_comments_number(),
                'items' => get_comments()
            ];
        }

        return null;
    }


    protected function get_author($post_id = false)
    {
        $author_id = get_post_field('post_author', $post_id);

        if ($author_id) {
            return [
                'id' => $author_id,
                'name' => get_the_author_meta('display_name', $author_id)
            ];
        }

        return null;
    }

    protected function get_media($post_id = false) {

        if ( has_post_thumbnail() ) {
            $sizes = array();
            

            foreach ( array_merge(get_intermediate_image_sizes(), ['full']) as $size) {
                $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), $size );

                $sizes[$size] = array(
                   'link' => esc_url( $thumbnail[0] ),
                   'width' => $thumbnail[1],
                   'height' => $thumbnail[2]
                );
            }

            $media = array(
                'id' => get_post_thumbnail_id(),
                'sizes' => $sizes,
                'caption' => get_the_post_thumbnail_caption()
            );

            return $media;
        }

        return null;
    }

    protected function get_seo($post_id = false)
    {
        if (class_exists('WPSEO_Frontend')) {

            $wpseo_frontend = WPSEO_Frontend::get_instance();
            $yoast_fields = array(
                'yoast_wpseo_focuskw'               => get_post_meta($post_id, '_yoast_wpseo_focuskw', true),
                'yoast_wpseo_title'                 => $wpseo_frontend->get_content_title(get_post($post_id)),
                'yoast_wpseo_metadesc'              => get_post_meta($post_id, '_yoast_wpseo_metadesc', true),
                'yoast_wpseo_linkdex'               => get_post_meta($post_id, '_yoast_wpseo_linkdex', true),
                'yoast_wpseo_metakeywords'          => get_post_meta($post_id, '_yoast_wpseo_metakeywords', true),
                'yoast_wpseo_meta-robots-noindex'   => get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true),
                'yoast_wpseo_meta-robots-nofollow'  => get_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', true),
                'yoast_wpseo_meta-robots-adv'       => get_post_meta($post_id, '_yoast_wpseo_meta-robots-adv', true),
                'yoast_wpseo_canonical'             => get_post_meta($post_id, '_yoast_wpseo_canonical', true),
                'yoast_wpseo_redirect'              => get_post_meta($post_id, '_yoast_wpseo_redirect', true),
                'yoast_wpseo_opengraph-title'       => get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true),
                'yoast_wpseo_opengraph-description' => get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true),
                'yoast_wpseo_opengraph-image'       => get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true),
                'yoast_wpseo_twitter-title'         => get_post_meta($post_id, '_yoast_wpseo_twitter-title', true),
                'yoast_wpseo_twitter-description'   => get_post_meta($post_id, '_yoast_wpseo_twitter-description', true),
                'yoast_wpseo_twitter-image'         => get_post_meta($post_id, '_yoast_wpseo_twitter-image', true)
            );

            // if we have fields
            if ($yoast_fields) {
                return $yoast_fields;
            }
        }

        return null;

    }

    protected function filter_false_values(&$array)
    {

        foreach ( $array as $key => $value) {
         
            if ( is_array( $value ) && $array[$key] ) {
                $array[$key] = $this->filter_false_values($value);
            } elseif ( strlen($value) === 0 ) {
                unset( $array[$key] );
            }
        
        }        
        return $array;

    }
}