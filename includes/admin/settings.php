<?php
/**
 * Settings class.
 *
 * @since 1.0.0
 *
 * @package WP Proportion Image Maker
 * @author  Lugano
 */
class WpProportionSettings {

    /**
     * Holds main settings for process of creation proportion image
     * @var object
     */
     private $storage;


     /*
      * Global error
      * @var bool
      */
     private $error = false;


    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Holds the submenu pagehook.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $hook;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Add custom settings submenu.
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        
        add_action( 'admin_menu', array( $this, 'prowp_register_settings' ) );
        
        add_action ( 'admin_enqueue_scripts', function () {
                  if (is_admin ()) {
                       wp_enqueue_media ();
                   }
                 });

        if (!class_exists('Imagick', false)) {
            $this->error = true;
        }

        wp_register_script('wp_proportion_interact-script', plugins_url('js/interact.js', __FILE__), array( 'jquery'), '20171904', true );
        wp_register_script('wp_proportion_proportion-script', plugins_url('js/proportion.js', __FILE__), array( 'jquery'), '20171904', true );
        wp_enqueue_script('wp_proportion_interact-script');
        wp_enqueue_script('wp_proportion_proportion-script');
        wp_enqueue_style( 'wp_proportion_proportion-style', plugins_url('css/proportion.css', __FILE__),false,'1.1','all');

    }

    /**
     * Register the Settings submenu item.
     *
     * @since 1.0.0
     */
    public function admin_menu() {


        // Register the submenu.
        $this->hook = add_submenu_page(
            'upload.php',
            'Create proportion image',
            'Create proportion image',
            'manage_options',
            'setting_for_proportion',
            array($this, 'settings_page')
        );

    }
    
   function prowp_register_settings() {

    //register our settings
     register_setting( 'product_proportion-settings-group', 'wp_proportion_settings_product', array($this, 'product_proportion_sanitize_options') );
  
    }

    /**
     * Create temp image on time making proportion image
     * @input string $path
     */
    private function create_temp_image($path) {
        $thumb = new Imagick();
        $thumb->readImage($this->storage->productImage);
        $thumb->resizeImage($this->storage->productWidth, $this->storage->productHeight, Imagick::FILTER_LANCZOS,1);
        $thumb->writeImage($path);

        unset($thumb);
    }

    /**
     * Create temporary background image on time making proportion image
     * @input string $path
     */
    private function create_temp_background_image($path) {
        $thumb = new Imagick();
        $thumb->readImage($this->storage->backgroundImage);
        $thumb->resizeImage(500, 500, Imagick::FILTER_LANCZOS,1);
        $thumb->writeImage($path);

        unset($thumb);
    }

    /**
     * Save created file into WordPress media storage
     * @input string file
     */
    private function save_in_media_folder($file) {
        $filename = basename($file);
        $upload_file = wp_upload_bits($filename, null, file_get_contents($file));
        if (!$upload_file['error']) {
            $wp_filetype = wp_check_filetype($filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => 0,
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], 0 );
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id,  $attachment_data );
            }
        }


    }

    /*
     * Add scale for composite image
     * @input string $composite_image_temp
     * @return bool
     */
    private function add_product_scale($composite_image_temp) {

        try {

            $scale_prime = plugin_dir_path(__DIR__) . 'admin/images/scale.png';
            $scale_path = plugin_dir_path(__DIR__) . 'admin/images/temp_scale.png';

            $thumb = new Imagick();
            $thumb->readImage($scale_prime);
            $thumb->resizeImage($this->storage->scaleWidth, $this->storage->scaleHeight, Imagick::FILTER_LANCZOS, 1);
            $thumb->writeImage($scale_path);


            $image = new Imagick($scale_path);

            // Watermark text
            $text = $this->storage->scaleText;

            // Create a new drawing palette
            $draw = new ImagickDraw();

            $font = plugin_dir_path(__DIR__) . 'admin/css/arial.ttf';
            // Set font properties
            $draw->setFont($font);
            $draw->setFontSize(12);
            $draw->setFillColor('black');

            // Position text at the bottom-right of the image
            $draw->setGravity(Imagick::GRAVITY_CENTER);

            // Draw text on the image
            $image->annotateImage($draw, -5, 0, 0, $text);

            // Set output image format
            $image->writeImage($scale_path);

            unset($image);

            $first = new Imagick($composite_image_temp);
            $second = new Imagick($scale_path);

            $top = 500 - $this->storage->scaleHeight - $this->storage->scaleTop;

            if ($this->storage->scaleLeft < 12) {
                $left = $this->storage->scaleLeft;
            } else {
                $left = $this->storage->scaleLeft;
            }

            $first->compositeImage($second, $second->getImageCompose(), $left, $top);

            //new image is saved as final.jpg
            $first->writeImage($composite_image_temp);

            unset($first);

        } catch (Exception $e) {
            throw $e;
        } finally {
            unlink($scale_path);
        }
    }
    
    /*
     * Sanitize data received from frontend
     * @return void
     */
    public function product_proportion_sanitize_options() {

        if (isset($_POST['proportion_settings_product']) && class_exists('Imagick', false)) {
            $this->get_settings_from_request($_POST['proportion_settings_product']);

            try {
                //Create temporary image in time making composite image
                $value = rand(1000, 10000);

                $temp_image = plugin_dir_path( __DIR__ ) . 'admin/images/temp-product-' . $value . '.png';
                $temp_background = plugin_dir_path( __DIR__ ) . 'admin/images/temp-background-' . $value . '.png';

                $this->create_temp_image($temp_image);
                $this->create_temp_background_image($temp_background);

                //Save new proportion image
                 $first = new Imagick($temp_background);

                 $second = new Imagick($temp_image);

                 $top = 500 - $this->storage->productHeight - $this->storage->productTop;

                 if ($this->storage->productLeft < 12) {
                     $left = $this->storage->productLeft;
                 } else {
                     $left = $this->storage->productLeft;
                 }

                //Set image color space
                 $first->setImageColorspace($second->getImageColorspace() );

                 //Create composite image
                 $first->compositeImage($second, $second->getImageCompose(), $left, $top);

                 $path = plugin_dir_path( __DIR__ ) . 'admin/images/proportion-' . rand(1000, 10000) . '.png';

                //Save composite image into image directory
                $first->writeImage($path);

                if ($this->storage->showScale) {

                    $this->add_product_scale($path);
                }

                 unset($first);
                 unset($second);

                $this->save_in_media_folder($path);

                // Show success message
                add_settings_error('proportion_option_notice', 'proportion_option_notice', __('The image has been saved', 'wp_proportion' ), 'updated');

            } catch(Exception $e) {

                // Show error message
                add_settings_error('proportion_option_notice', 'proportion_option_notice', __('An error occurred while plugin was saving the image:', 'wp_proportion' ) . ' ' . $e->getMessage(), 'error');

            } finally {
                unlink($temp_image);
                unlink($temp_background);
                unlink($path);
            }
        }
    }

    /**
     * Callback to output the settings page.
     *
     * @since 1.0.0
     * @return html
     */
    public function settings_page() {

        $this->create_settings_storage();

        $path = plugin_dir_path( __DIR__ );

        $upload_dir = wp_upload_dir();

        $domain = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);

        $upload_dir = str_replace('\\', '\\\\', $upload_dir['basedir']);

        if ($this->error) {
            // Show if php_imagick.dll had not been installed
            add_settings_error('proportion_option_notice', 'proportion_option_notice', __('In order to use this plugin You must install php_imagick.dll', 'wp_proportion' ), 'error');
        }

        ?>
        <div id="proportion-settings" class="wrap">
        <?php settings_errors(); ?>
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
            <div class ="container" style="float: left; width: 500px; height: 500px;">
                    <div class ="container-for-background-image"><img src="<?php echo $this->storage->backgroundImage; ?>" style="width: 500px; height: 500px;" id ="show-image-background-main"/></div>
                    <img  class = "container-for-product-image" id="show-image-product-main" style="width: 100px; height: 100px;" src="<?php echo $this->storage->productImage; ?>"/>
            </div>
            <div style="width: 450px; float:left; margin-left: 50px;">
                    <div style ="width:450px;">
                    <form method="post" action="options.php">
                    <?php settings_fields( 'product_proportion-settings-group' ); ?>
                     <table class="form-table">
                         <tr>
                             <th scope="row"><?php echo __('Background image', 'wp_proportion' ); ?></th>
                             <td>
                                 <div><img style="width:100px; height: 100px;" id="show-image-background" src="<?php echo $this->storage->backgroundImage; ?>"/></div>
                                 <input type="hidden" name="proportion_settings_product[background_image]" data-imagepath="<?php echo $path . 'admin/images/small_sizing.png'; ?>" id="upload-image-field-background" value="<?php echo $path . 'admin/images/small_sizing.jpg'; ?>" />
                                 <input type="button" id="button-image-background" class="button-secondary" value="Upload Image">
                             </td>
                         </tr>
                         <tr>
                             <th scope="row"><?php echo __('Product image', 'wp_proportion' ); ?></th>
                             <td>
                                 <div><img style="width:100px; height: 100px;" id="show-image-product" src="<?php echo $this->storage->productImage; ?>"/></div>
                                 <input type="hidden" name="proportion_settings_product[product_image]" id="upload-image-field-product" data-imagepath="<?php echo $path . 'wp-proportion/includes/admin/images/product.png'; ?>" value="<?php echo $path . 'admin/images/product.png'; ?>" />
                                 <input type="button" id="button-image-product" class="button-secondary" value="Upload Image">
                             </td>
                         </tr>
                         <tr>
                               <th scope="row"><?php echo __('Image size(height)', 'wp_proportion' ); ?></th>
                               <td><input type="text" id="input-height-image" style="width: 250px;" name="proportion_settings_product[product_image_height]" value="" /></td>
                          </tr>
                          <tr>
                              <th scope="row"><?php echo __('Show product image size', 'wp_proportion' ); ?></th>
                              <td>
                                  <select name="proportion_settings_product[scale_image_show]" id="input-show-scale" style="width: 250px;">
                                      <option value="0"><?php echo __('No', 'wp_proportion' ); ?></option>
                                      <option value="1"><?php echo __('Yes', 'wp_proportion' ); ?></option>
                                  </select>
                              </td>
                          </tr>
                         <tr>
                             <th scope="row"><?php echo __('Select background image', 'wp_proportion' ); ?></th>
                             <td>
                                 <select id="input-image-background" style="width: 250px;">
                                     <option data-imagepath="<?php echo $path . 'admin/images/small_sizing.jpg'; ?>" value="<?php echo plugins_url('images/small_sizing.jpg', __FILE__); ?>"><?php echo __('Small', 'wp_proportion' ); ?></option>
                                     <option data-imagepath="<?php echo $path . 'admin/images/medium_sizing.jpg'; ?>" value="<?php echo plugins_url('images/medium_sizing.jpg', __FILE__); ?>"><?php echo __('Medium', 'wp_proportion' ); ?></option>
                                     <option data-imagepath="<?php echo $path . 'admin/images/large_sizing.jpg'; ?>" value="<?php echo plugins_url('images/large_sizing.jpg', __FILE__); ?>"><?php echo __('Large', 'wp_proportion' ); ?></option>
                                 </select>
                             </td>
                         </tr>
                         <tr>
                             <th scope="row"><?php echo __('Product image size', 'wp_proportion' ); ?></th>
                             <td><input type="text" style="width: 250px;" id="input-product-image-size" name="proportion_settings_product[product_image_size]" value="" /></td>
                         </tr>
                         <tr>
                             <th scope="row"><?php echo __('Product image position', 'wp_proportion' ); ?></th>
                             <td><input type="text" style="width: 250px;" id ="input-product-image-position" name="proportion_settings_product[product_image_position]" value="" /></td>
                         </tr>
                         <tr>
                             <th scope="row"><?php echo __('Product scale size', 'wp_proportion' ); ?></th>
                             <td><input type="text" style="width: 250px;" id="input-product-scale-size" name="proportion_settings_product[product_scale_size]" value="" /></td>
                         </tr>
                         <tr>
                             <th scope="row"><?php echo __('Product scale position', 'wp_proportion' ); ?></th>
                             <td><input type="text" id="input-product-scale-position" style="width: 250px;" name="proportion_settings_product[product_scale_position]" value="" /></td>
                         </tr>
                          <tr>
                              <td> <input type="submit"  class="button-primary" value="<?php echo __('Save', 'wp_proportion' ); ?>" /> </td>
                          </tr>
                     </table>
                  </form>
            </div> 
        </div>
        <script type="text/javascript">

            var system_image_path = "<?php echo $upload_dir; ?>";
            var system_domain = "<?php echo $domain; ?>";
        </script>
        <?php

    }

    /*
     * Create one StdObject as a storage for settings
     * @return void
     */
    private function create_settings_storage() {
        $this->storage = (object) array(
            'backgroundWidth'  => 500,
            'backgroundHeight' => 500,
            'productWidth'     => 100,
            'productHeight'    => 100,
            'productTop'       => 100,
            'productLeft'      => 100,
            'scaleWidth'       => 100,
            'scaleHeight'      => 100,
            'scaleTop'         => 100,
            'scaleLeft'        => 200,
            'scaleText'        => '',
            'showScale'        => false,
            'backgroundImage'  => plugins_url('images/small_sizing.jpg', __FILE__),
            'productImage'     => plugins_url('images/product.png', __FILE__)
        );
    }

    /*
     * Get setting from request and save them into Std - object
     * @input array $request
     * @return void
     */
    private function get_settings_from_request($request) {

        $this->create_settings_storage();

        $size = explode('x', $request['product_image_size'] );

        if (is_numeric($size[0]) || is_numeric($size[1])) {
            $this->storage->productWidth = $size[0];
            $this->storage->productHeight = $size[1];
        }

        $scale_size = explode('x', $request['product_scale_size']);

        if (is_numeric($scale_size[0]) || is_numeric($scale_size[1])) {
            $this->storage->scaleWidth = $scale_size[0];
            $this->storage->scaleHeight = $scale_size[1];
        }

        $position = explode('x', $request['product_image_position']);

        if (is_numeric($position[0]) || is_numeric($position[1])) {
            $this->storage->productTop = $position[0];
            $this->storage->productLeft = $position[1];
        }

        $scale_position = explode('x', $request['product_scale_position']);

        if (is_numeric($scale_position[0]) || is_numeric($scale_position[1])) {
            $this->storage->scaleTop = $scale_position[0];
            $this->storage->scaleLeft = $scale_position[1];
        }

        if (isset($request['background_image']) && (strlen($request['background_image']) > 5)) {
            $this->storage->backgroundImage = $request['background_image'];
        }

        if (isset($request['product_image']) && (strlen($request['product_image']) > 5)) {
            $this->storage->productImage = $request['product_image'];
        }

        if (isset($request['scale_image_show']) && $request['scale_image_show'] == 1) {
            $this->storage->showScale = $request['scale_image_show'];
        }

        if (isset($request['product_image_height']) && (strlen($request['product_image_height']) > 0)) {
            $this->storage->scaleText = $request['product_image_height'];
        }
    }
}

/*
 * Load language for plugin
 * @return void
 */
function load_texdomain_for_proportion(){
    $test = load_plugin_textdomain('wp_proportion', false, 'wp-proportion/includes/admin/i18n/language/' );
    $raw = $test;
}

// Load language for current locale
add_action('init', 'load_texdomain_for_proportion');

// Load the settings class.
return new WpProportionSettings();
