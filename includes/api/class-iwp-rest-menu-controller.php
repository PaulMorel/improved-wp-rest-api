<?php

/**
 * Improved WP REST API Posts Controller Class
 * 
 * @package Improved_WP_REST_API
 * @since 1.0
 */
class IWP_REST_Menu_Controller extends WP_REST_Controller
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
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        $this->namespace = IWP_REST_NAMESPACE;
        $this->rest_base = 'menus';
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
                'args'                => $this->get_collection_params(), // @todo override get_collection_params()
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


        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<location>[\S]+)', array(
            array(
                'args' => array(
                    'location' => array(
                        'description' => __( 'Location for nav menu.', 'iwpra' ),
                        'type'        => 'string',
                        'enum' => array_keys( get_registered_nav_menus() )
                    ),
                    'context' => $this->get_context_param(array(
                        'default' => 'view',
                    )),
                ),
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item_by_location' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(
               
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
        

		// Get all menus
		$menus = get_terms('nav_menu');
        $data = array();


        // Verify if $menus is valid
		if ( ! empty( $menus ) && ! is_wp_error($menus) ) {
			foreach( $menus as $menu) {
                $itemdata = $this->prepare_item_for_response($menu, $request);
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

        $menu = get_term_by('id', $params['id'], 'nav_menu');

    
        if (! $menu ) {
            return new WP_Error(
                'invalid_menu',
                __('Invalid Menu ID', 'iwpra'),
                array(
                    'status' => 404,
                )
            );
        }
    
        $data = array(
            $this->prepare_response_for_collection(
                $this->prepare_item_for_response($menu, $request)
            ),
        );
    
        $data = rest_ensure_response($data);
        return $data;
    }

    public function get_item_by_location($request) {
        //get parameters from request
        $params = $request->get_params();

        $locations = get_nav_menu_locations();

        if ( ! key_exists( $params['location'], $locations) ) {
            return new WP_Error(
                'invalid_menu',
                __('Invalid Menu ID', 'iwpra'),
                array(
                    'status' => 404,
                )
            );
        }

        // ID
        $menu = wp_get_nav_menu_object($locations[$params['location']]);
        // var_dump($menu);

        if (! $menu ) {
            return new WP_Error(
                'invalid_menu',
                __('Invalid Menu ID', 'iwpra'),
                array(
                    'status' => 404,
                )
            );
        }
    
        $data = array(
            $this->prepare_response_for_collection(
                $this->prepare_item_for_response($menu, $request)
            ),
        );
    
        $data = rest_ensure_response($data);
        return $data;
    }
    /**
     * Prepares restaurant data for return as an object.
     */
    public function prepare_item_for_response($menu, $request)
    {

        $data = array(
            'id' => $menu->term_id,
            'title' => $menu->name,
            'slug' => $menu->slug,
            'items' => $this->get_menu_items($menu->term_id)
        );

        return $data;
    }

    
    /**
    * Returns Advanced Custom Fields data is available
    *
    * @param mixed $post_id
    * @return array|bool	associative array where field name => field value or false if ACF isn't activated or installed
    */
    protected function get_menu_items($term_id = false)
    {
        $menu_items = wp_get_nav_menu_items($term_id);
        
        $menu_item_list = array();

        // var_dump($menu_items );
        foreach( $menu_items as $menu_item) {
            $menu_item_list[] = array(
                'id' => $menu_item->ID ?: null,
                'title' => $menu_item->title ?: null,
                'order' => $menu_item->menu_order ?: null,
                'slug' => basename( $menu_item->url ) ?: null,
                'url' => $menu_item->url ?: null,
                'target' => $menu_item->target ?: null,
                'description' => $menu_item->description ?: null,
                'class' => $menu_item->classes ?: null,
                'parent' => $menu_item->menu_item_parent ?: null
            );
        }

        $menu_item_list = array_map('array_filter', $menu_item_list);

        return $menu_item_list;

        // return false;
    }

}